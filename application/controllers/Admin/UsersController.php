<?php

class Admin_UsersController extends Zend_Controller_Action {

    public function indexAction() {

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );



        $this->view->users = array();
        $this->view->systemMessages = $systemMessages;
    }

    public function addAction() {

        $request = $this->getRequest();

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        $form = new Application_Form_Admin_UserAdd();

        //default form data
        $form->populate(array(
        ));



        if ($request->isPost() && $request->getPost('task') === 'save') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for new user'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();


                //Insertujemo novi zapis u tabelu
                $cmsUsersTable = new Application_Model_DbTable_CmsUsers();


                //insert member returns ID of the new member //insertovanje u bazu
                $userId = $cmsUsersTable->insertUser($formData);


                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('User has beeen saved', 'success');
                //ovo su primera dva dole da vidimo kako izgleda
                //$flashMessenger->addMessage('Or not maybe something is wrong', 'errors');
                //$flashMessenger->addMessage('success message 2', 'success');
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_users',
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
            throw new Zend_Controller_Router_Exception('Invalid user id: ' . $id, 404);
        }

        $cmsUsersTable = new Application_Model_DbTable_CmsUsers();

        $user = $cmsUsersTable->getUserById($id);

        if (empty($user)) {
            //redirect user to edit profile page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_profile',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'edit',
                            ), 'default', true);
        }

        $loggedinUser = Zend_Auth::getInstance()->getIdentity();

        if ($id == $loggedinUser['id']) {
            //
            throw new Zend_Controller_Router_Exception('Go to edit profile page!', 404);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );
        //forma sluzi za filtriranje i validaciju polja
        $form = new Application_Form_Admin_UserEdit($user['id']);

        //default form data
        $form->populate($user);



        if ($request->isPost() && $request->getPost('task') === 'update') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for user'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();


                //Radimo update postojeceg zapisa u tabeli
                $cmsUsersTable->updateUser($user['id'], $formData);


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
                            'controller' => 'admin_users',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                $systemMessages['errors'][] = $ex->getMessage();
            }
        }

        $this->view->systemMessages = $systemMessages;
        $this->view->form = $form;

        $this->view->user = $user;
    }

    public function deleteAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'delete') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_users',
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
                throw new Zend_Controller_Router_Exception('Invalid user id: ' . $id);
            }

            $cmsUsersTable = new Application_Model_DbTable_CmsMembers();

            $user = $cmsUsersTable->getMemberById($id);


            if (empty($user)) {

                throw new Zend_Controller_Router_Exception('No user is found with id: ' . $id, 'errors');
            }

            $cmsUsersTable->deleteUser($id);


            $flashMessenger->addMessage('User ' . $user['first_name'] . ' ' . $user['last_name'] . 'has been deleted', 'success');
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
                        'controller' => 'admin_users',
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
                        'controller' => 'admin_users',
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
                throw new Application_Model_Exception_InvalidInput('Invalid user id: ' . $id);
            }

            $cmsUsersTable = new Application_Model_DbTable_CmsUsers();

            $user = $cmsUsersTable->getUserById($id);


            if (empty($user)) {

                throw new Zend_Controller_Router_Exception('No user is found with id: ' . $id, 'errors');
            }

            $cmsUsersTable->disableUser($id);


            $request instanceof Zend_Controller_Request_Http;
            //ispitivanje da li je ajax zahtev
            if ($request->isXmlHttpRequest()) {
                //request is ajax request
                //send response as json
                
                $resposneJson = array (
                    'status' => 'ok',
                    'statusMessage' => 'User ' . $user['first_name'] . ' ' . $user['last_name'] . ' has been disabled'
                );
                
                //send json as response
                $this->getHelper('Json')->sendJson($resposneJson);
                
            } else {
                //request is not ajax
                //send message over session (flashMessenger)
                //and do redirect
                
                $flashMessenger->addMessage('User ' . $user['first_name'] . ' ' . $user['last_name'] . ' has been disabled', 'success');
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_users',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                                ), 'default', true);
            }
            //sluzi za dohvatanje greske
        } catch (Application_Model_Exception_InvalidInput $ex) {
            
            if ($request->isXmlHttpRequest())  {
                
                
                $responseJson = array (
                    'status' => 'error',
                    'statusMessage' => $ex->getMessage()
                );
                
                //send json as response
                $this->getHelper('Json')->sendJson($resposneJson);
                
            } else {
                
                //request is not ajax
                //send message over session (flashMessenger)
                //and do redirect
                $flashMessenger->addMessage($ex->getMessage(), 'errors');

                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_users',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                                ), 'default', true); 
            }
            
            
        }
    }

    public function enableAction() {

        $request = $this->getRequest();
        //ispituje se POST zahtev
        if (!$request->isPost() || $request->isPost('task') != 'enable') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_users',
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
                throw new Application_Model_Exception_InvalidInput('Invalid user id: ' . $id);
            }

            $cmsUsersTable = new Application_Model_DbTable_CmsUsers();
            $user = $cmsUsersTable->getUserById($id);


            if (empty($user)) {

                throw new Application_Model_Exception_InvalidInput('No user is found with id: ' . $id, 'errors');
            }

            $cmsUsersTable->enableUser($id);

            $flashMessenger->addMessage('User ' . $user['first_name'] . ' ' . $user['last_name'] . ' has been enabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_users',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_users',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }

    public function resetpasswordAction() {

        $request = $this->getRequest();
        //ispituje se POST zahtev
        if (!$request->isPost() || $request->isPost('task') != 'resetpassword') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_users',
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
                throw new Application_Model_Exception_InvalidInput('Invalid user id: ' . $id);
            }

            $cmsUsersTable = new Application_Model_DbTable_CmsUsers();
            $user = $cmsUsersTable->getUserById($id);


            if (empty($user)) {

                throw new Application_Model_Exception_InvalidInput('No user is found with id: ' . $id, 'errors');
            }

            $cmsUsersTable->resetUserPassword($id);

            $flashMessenger->addMessage('Password has been reset successfully for ' . $user['first_name'] . ' ' . $user['last_name'], 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_users',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_users',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }

    public function datatableAction() {

        $request = $this->getRequest();

        $datatableParameters = $request->getParams();

//        print_r($datatableParameters);
//        die();

        /*
         * Array
          (
          [controller] => admin_users
          [action] => datatable
          [module] => default
          //obavezan
          [draw] => 1

          [order] => Array
          (
          [0] => Array
          (
          [column] => 2
          [dir] => asc
          )
          )
          [start] => 0
          [length] => 3
          [search] => Array
          (
          [value] =>
          [regex] => false
          )
          )
         */

        $cmsUsersTable = new Application_Model_DbTable_CmsUsers();

        $loggedInUser = Zend_Auth::getInstance()->getIdentity();

        $filters = array(
            'id_exclude' => $loggedInUser
        );
        $orders = array();
        $limit = 5;
        $page = 1;
        $draw = 1;

        //actions cuvamo dugmice u toj koloni i nema je u bazi
        $columns = array('status', 'username', 'first_name', 'last_name', 'email', 'actions');

        //Process datatable parameters

        if (isset($datatableParameters['draw'])) {

            $draw = $datatableParameters['draw'];

            if (isset($datatableParameters['length'])) {
                // limit rows per page
                $limit = $datatableParameters['length'];

                if (isset($datatableParameters['start'])) {

                    $page = floor($datatableParameters['start'] / $datatableParameters['length']) + 1;
                }
            }
        }

        if (
                isset($datatableParameters['order']) && is_array($datatableParameters['order'])
        ) {
            foreach ($datatableParameters['order'] as $datatableOrder) {
                $columnIndex = $datatableOrder ['column'];
                $orderDirection = strtoupper($datatableOrder['dir']);

                if (isset($columns[$columnIndex])) {
                    $orders[$columns[$columnIndex]] = $orderDirection;
                }
            }
        }


        if (
                isset($datatableParameters['search']) && is_array($datatableParameters['search']) && isset($datatableParameters['search'] ['value'])
        ) {
            $filters['username_search'] = $datatableParameters['search']['value'];
        }

        $users = $cmsUsersTable->search(array(
            'filters' => $filters,
            'orders' => $orders,
            'limit' => $limit,
            'page' => $page
        ));

        $usersFilteredCount = $cmsUsersTable->count($filters);
        $usersTotal = $cmsUsersTable->count();

        //prosledjivanje parametara prez logici
        $this->view->users = $users;
        $this->view->usersFilteredCount = $usersFilteredCount;
        $this->view->usersTotal = $usersTotal;
        $this->view->draw = $draw;
        $this->view->columns = $columns;
    }

}
