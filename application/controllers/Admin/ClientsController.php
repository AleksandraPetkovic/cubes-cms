<?php

class Admin_ClientsController extends Zend_Controller_Action {

    public function indexAction() {

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        //prikaz svih member-a
        $cmsClientsDbTable = new Application_Model_DbTable_CmsClients();
        //select je objekat klase Zend_Db_Select
        $select = $cmsClientsDbTable->select();
        $select->order('order_number');
        //debug za  db select - vraca se sql upit
        //die($select->assemble());
        $clients = $cmsClientsDbTable->fetchAll($select);

        $this->view->clients = $clients;
        $this->view->systemMessages = $systemMessages;
    }

    public function addAction() {

        $request = $this->getRequest();

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        $form = new Application_Form_Admin_ClientAdd();

        //default form data
        $form->populate(array(
        ));

        if ($request->isPost() && $request->getPost('task') === 'save') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for new client'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();

                //remove key member_photo from form data because there is no column 'member_photo' in cms_members
                unset($formData['client_photo']);

                //Insertujemo novi zapis u tabelu
                $cmsClientsTable = new Application_Model_DbTable_CmsClients();


                //insert member returns ID of the new member //insertovanje u bazu
                $clientId = $cmsClientsTable->insertClient($formData);

                //da li je uloadovano, provera
                if ($form->getElement('client_photo')->isUploaded()) {
                    //photo is uploaded

                    $fileInfos = $form->getElement('client_photo')->getFileInfo('client_photo');
                    $fileInfo = $fileInfos['client_photo'];
                    //isto kao prethodne dve linije gore
                    //$fileInfos = $_FILES['client_photo']

                    try {
                        //make je putanja do slike
                        //open uploaded photo in temporary directory
                        $clientPhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);
                        $clientPhoto->fit(170, 70);
                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
                        $clientPhoto->save(PUBLIC_PATH . '/uploads/clients/' . $clientId . '.jpg');
                    } catch (Exception $ex) {
                        $flashMessenger->addMessage('Client has beeen saved but error occured during image processing', 'success');
                        //redirect to same or another page
                        $redirector = $this->getHelper('Redirector');
                        $redirector->setExit(true)
                                ->gotoRoute(array(
                                    'controller' => 'admin_clients',
                                    //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                                    'action' => 'index',
                                    'id' => $clientId
                                        ), 'default', true);
                    }
                }


                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('Client has beeen saved', 'success');
                
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_clients',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                $systemMessages['errors'][] = $ex->getMessage();
            }
        }

        $this->view->systemMessages = $systemMessages;
        $this->view->form = $form;
    }

    public function editAction() {

        $request = $this->getRequest();

        //(int) sve sto nije integer pretvara u nulu :)
        $id = (int) $request->getParam('id');

        if ($id <= 0) {
            //prekida se izvrsavanje programa i prikazuje se "Page not found"
            throw new Zend_Controller_Router_Exception('Invalid client id: ' . $id, 404);
        }

        $cmsClientsTable = new Application_Model_DbTable_CmsClients();

        $client = $cmsClientsTable->getClientById($id);

        if (empty($client)) {
            throw new Zend_Controller_Router_Exception('No client is found with id: ' . $id, 404);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );
        //forma sluzi za filtriranje i validaciju polja
        $form = new Application_Form_Admin_ClientEdit();

        //default form data
        $form->populate($client);

        if ($request->isPost() && $request->getPost('task') === 'update') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for client'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();
                unset($formData['client_photo']);

                if ($form->getElement('client_photo')->isUploaded()) {
                    //photo is uploaded

                    $fileInfos = $form->getElement('client_photo')->getFileInfo('client_photo');
                    $fileInfo = $fileInfos['client_photo'];
                    //isto kao prethodne dve linije gore
                    //$fileInfos = $_FILES['member_photo']

                    try {
                        //make je putanja do slike
                        //open uploaded photo in temporary directory
                        $clientPhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);

                        $clientPhoto->fit(170, 70);

                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
                        $clientPhoto->save(PUBLIC_PATH . '/uploads/clients/' . $client['id'] . '.jpg');
                    } catch (Exception $ex) {
                        //ne redirektujemo na neku drugu stranu nego ostajemo na toj strani 
                        throw new Application_Model_Exception_InvalidInput('Error occured during image processing');
                    }
                }

                //Radimo update postojeceg zapisa u tabeli
                $cmsClientsTable->updateClient($client['id'], $formData);

                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('Client has beeen updated', 'success');
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_clients',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                $systemMessages['errors'][] = $ex->getMessage();
            }
        }

        $this->view->systemMessages = $systemMessages;
        $this->view->form = $form;


        $this->view->client = $client;
    }

    public function deleteAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'delete') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        try {
            //(int) sve sto nije integer pretvara u nulu :)
            //read $_POST['id']
            $id = (int) $request->getPost('id');

            if ($id <= 0) {

                //prekida se izvrsavanje programa i prikazuje se "Page not found"
                throw new Zend_Controller_Router_Exception('Invalid client id: ' . $id);
            }

            $cmsClientsTable = new Application_Model_DbTable_CmsClients();

            $client = $cmsClientsTable->getClientById($id);


            if (empty($client)) {

                throw new Zend_Controller_Router_Exception('No client is found with id: ' . $id, 'errors');
            }

            $cmsClientsTable->deleteClient($id);


            $flashMessenger->addMessage('Client ' . $client['first_name'] . ' ' . $client['last_name'] . 'has been deleted', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }

    public function disableAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'disable') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        try {
            //(int) sve sto nije integer pretvara u nulu :)
            //read $_POST['id']
            $id = (int) $request->getPost('id');

            if ($id <= 0) {

                //prekida se izvrsavanje programa i prikazuje se "Page not found"
                throw new Zend_Controller_Router_Exception('Invalid client id: ' . $id);
            }

            $cmsClientsTable = new Application_Model_DbTable_CmsClients();

            $client = $cmsClientsTable->getClientById($id);


            if (empty($client)) {

                throw new Zend_Controller_Router_Exception('No client is found with id: ' . $id, 'errors');
            }

            $cmsClientsTable->disableClient($id);

            $flashMessenger->addMessage('Client ' . $client['first_name'] . ' ' . $client['last_name'] . 'has been disabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }

    public function enableAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'enable') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        try {
            //(int) sve sto nije integer pretvara u nulu :)
            //read $_POST['id']
            $id = (int) $request->getPost('id');

            if ($id <= 0) {

                //prekida se izvrsavanje programa i prikazuje se "Page not found"
                throw new Zend_Controller_Router_Exception('Invalid client id: ' . $id);
            }

            $cmsClientsTable = new Application_Model_DbTable_CmsClients();

            $client = $cmsClientsTable->getClientById($id);


            if (empty($client)) {

                throw new Zend_Controller_Router_Exception('No client is found with id: ' . $id, 'errors');
            }

            $cmsClientsTable->enableClient($id);

            $flashMessenger->addMessage('Client ' . $client['first_name'] . ' ' . $client['last_name'] . 'has been enabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }

    public function updateorderAction() {
        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'saveOrder') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        try {

            $sortedIds = $request->getPost('sorted_ids');

            if (empty($sortedIds)) {
                throw new Application_Model_Exception_InvalidInput('Sorted ids are not sent');
            }

            //trimujemo po spejsu i po zarezu
            $sortedIds = trim($sortedIds, ' ,');

            //proveravamo da li je od nula do devet i zarez i mora da ima vise od jednog karaktera
            //zvezda znaci da ono sto je u zagradi da moze vise puta da se nadje
            if (!preg_match('/^[0-9]+(,[0-9]+)*$/', $sortedIds)) {
                throw new Application_Model_Exception_InvalidInput('Invalid sorted ids: ' . $sortedIds);
            }

            $sortedIds = explode(',', $sortedIds);
            
            $cmsClientsTable = new Application_Model_DbTable_CmsClients();
            $cmsClientsTable->updateOrderOfClients($sortedIds);
            
            $flashMessenger->addMessage('Order is successfully saved', 'success');
            
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {

            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_clients',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }

}
