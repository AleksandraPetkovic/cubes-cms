<?php

class Admin_MembersController extends Zend_Controller_Action {

    public function indexAction() {

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        //prikaz svih member-a
        $cmsMembersDbTable = new Application_Model_DbTable_CmsMembers();

//        $members = $cmsMembersDbTable->search(array(
//            'filters' => array(
//                'status' => Application_Model_DbTable_CmsMembers::STATUS_ENABLED,
//                'work_title' => array(
//                    'PHP Developer', 'lwvknrlbjnr'
//                    )
//            ),
//            'orders' => array(
//                'work_title' => 'ASC',
//                'first_name' => 'ASC',
//                'last_name' => 'ASC'
//            ),
//            //'limit' => 50,
//            //'page' => 3
//        ));
        
        $members = $cmsMembersDbTable->search(array(
            'orders' => array(
                'order_number' => 'ASC'
            )
        ));
            

        $this->view->members = $members;
        $this->view->systemMessages = $systemMessages;
    }

    public function addAction() {

        //
        $request = $this->getRequest();

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        $form = new Application_Form_Admin_MemberAdd();

        //default form data
        $form->populate(array(
        ));



        if ($request->isPost() && $request->getPost('task') === 'save') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for new member'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();

                //remove key member_photo from form data because there is no column 'member_photo' in cms_members
                unset($formData['member_photo']);

                //Insertujemo novi zapis u tabelu
                $cmsMembersTable = new Application_Model_DbTable_CmsMembers();


                //insert member returns ID of the new member //insertovanje u bazu
                $memberId = $cmsMembersTable->insertMember($formData);

                //da li je uloadovano, provera
                if ($form->getElement('member_photo')->isUploaded()) {
                    //photo is uploaded

                    $fileInfos = $form->getElement('member_photo')->getFileInfo('member_photo');
                    $fileInfo = $fileInfos['member_photo'];
                    //isto kao prethodne dve linije gore
                    //$fileInfos = $_FILES['member_photo']

                    try {
                        //make je putanja do slike
                        //open uploaded photo in temporary directory
                        $memberPhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);

                        $memberPhoto->fit(150, 150);

                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
                        $memberPhoto->save(PUBLIC_PATH . '/uploads/members/' . $memberId . '.jpg');
                    } catch (Exception $ex) {
                        $flashMessenger->addMessage('Member has beeen saved but error occured during image processing', 'success');

                        //redirect to same or another page
                        $redirector = $this->getHelper('Redirector');
                        $redirector->setExit(true)
                                ->gotoRoute(array(
                                    'controller' => 'admin_members',
                                    //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                                    'action' => 'index',
                                    'id' => $memberId
                                        ), 'default', true);
                    }
                }


                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('Member has beeen saved', 'success');
                //ovo su primera dva dole da vidimo kako izgleda
                //$flashMessenger->addMessage('Or not maybe something is wrong', 'errors');
                //$flashMessenger->addMessage('success message 2', 'success');
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_members',
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
            throw new Zend_Controller_Router_Exception('Invalid member id: ' . $id, 404);
        }

        $cmsMembersTable = new Application_Model_DbTable_CmsMembers();

        $member = $cmsMembersTable->getMemberById($id);

        if (empty($member)) {
            throw new Zend_Controller_Router_Exception('No member is found with id: ' . $id, 404);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );
        //forma sluzi za filtriranje i validaciju polja
        $form = new Application_Form_Admin_MemberEdit();

        //default form data
        $form->populate($member);



        if ($request->isPost() && $request->getPost('task') === 'update') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for member'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();
                unset($formData['member_photo']);

                if ($form->getElement('member_photo')->isUploaded()) {
                    //photo is uploaded

                    $fileInfos = $form->getElement('member_photo')->getFileInfo('member_photo');
                    $fileInfo = $fileInfos['member_photo'];
                    //isto kao prethodne dve linije gore
                    //$fileInfos = $_FILES['member_photo']

                    try {
                        //make je putanja do slike
                        //open uploaded photo in temporary directory
                        $memberPhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);

                        $memberPhoto->fit(150, 150);

                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
                        $memberPhoto->save(PUBLIC_PATH . '/uploads/members/' . $member['id'] . '.jpg');
                    } catch (Exception $ex) {
                        //ne redirektujemo na neku drugu stranu nego ostajemo na toj strani 
                        throw new Application_Model_Exception_InvalidInput('Error occured during image processing');
                    }
                }




                //Radimo update postojeceg zapisa u tabeli
                $cmsMembersTable->updateMember($member['id'], $formData);



                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('Member has beeen updated', 'success');
                //ovo su primera dva dole da vidimo kako izgleda
                //$flashMessenger->addMessage('Or not maybe something is wrong', 'errors');
                //$flashMessenger->addMessage('success message 2', 'success');
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_members',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                $systemMessages['errors'][] = $ex->getMessage();
            }
        }

        $this->view->systemMessages = $systemMessages;
        $this->view->form = $form;


        $this->view->member = $member;
    }

    public function deleteAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'delete') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
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
                throw new Zend_Controller_Router_Exception('Invalid member id: ' . $id);
            }

            $cmsMembersTable = new Application_Model_DbTable_CmsMembers();

            $member = $cmsMembersTable->getMemberById($id);


            if (empty($member)) {

                throw new Zend_Controller_Router_Exception('No member is found with id: ' . $id, 'errors');
            }

            $cmsMembersTable->deleteMember($id);


            $flashMessenger->addMessage('Member ' . $member['first_name'] . ' ' . $member['last_name'] . 'has been deleted', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
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
                        'controller' => 'admin_members',
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
                throw new Zend_Controller_Router_Exception('Invalid member id: ' . $id);
            }

            $cmsMembersTable = new Application_Model_DbTable_CmsMembers();

            $member = $cmsMembersTable->getMemberById($id);


            if (empty($member)) {

                throw new Zend_Controller_Router_Exception('No member is found with id: ' . $id, 'errors');
            }

            $cmsMembersTable->disableMember($id);


            $flashMessenger->addMessage('Member ' . $member['first_name'] . ' ' . $member['last_name'] . 'has been disabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
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
                        'controller' => 'admin_members',
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
                throw new Zend_Controller_Router_Exception('Invalid member id: ' . $id);
            }

            $cmsMembersTable = new Application_Model_DbTable_CmsMembers();

            $member = $cmsMembersTable->getMemberById($id);


            if (empty($member)) {

                throw new Zend_Controller_Router_Exception('No member is found with id: ' . $id, 'errors');
            }

            $cmsMembersTable->enableMember($id);


            $flashMessenger->addMessage('Member ' . $member['first_name'] . ' ' . $member['last_name'] . 'has been enabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
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
                        'controller' => 'admin_members',
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

            $cmsMembersTable = new Application_Model_DbTable_CmsMembers();

            $cmsMembersTable->updateOrderOfMembers($sortedIds);

            $flashMessenger->addMessage('Order is successfully saved', 'success');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {

            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_members',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }

    public function dashboardAction(){
        
        $cmsMembersDbTable = new Application_Model_DbTable_CmsMembers();
        
        $countOfEnabledMembers = $cmsMembersDbTable->count(array(
            'status' => Application_Model_DbTable_CmsMembers::STATUS_ENABLED,
        ));
        
        $countAlldMembers = $cmsMembersDbTable->count();
    
        $this->view->countOfEnabledMembers = $countOfEnabledMembers;
        $this->view->countAlldMembers = $countAlldMembers;
    }
    
    
}
