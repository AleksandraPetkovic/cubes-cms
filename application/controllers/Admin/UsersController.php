<?php

class Admin_UsersController extends Zend_Controller_Action
{
    public function indexAction(){
        
        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );
        
        $cmsUsersDbTable = new Application_Model_DbTable_CmsUsers();
        
        //dobijamo ulogovanog user-a
        $loggedInUser = Zend_Auth::getInstance()->getIdentity();
        
        $users = $cmsUsersDbTable->search(array(
            'filters' =>array (
                //bitno je da se ovaj tekst poklapa sa switch-om
                //on ne mora celi da se poklapa
                //'username_search' => 'vic',
                //daje praznu tabelu jer ne postoji username alek
                //'username' => 'alek'
                'id_exclude' => $loggedInUser['id']
            ),
            'orders' => array (
                'first_name' => 'ASC'
            ),
//            'limit' => 3,
//            'page' => 2
        ));
        
        $this->view->users = $users;
        $this->view->systemMessages = $systemMessages;
    }
    
    public function addAction(){
        
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
    
    public function editAction(){
        
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
        
        if ($id == $loggedinUser['id']){
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

            $flashMessenger->addMessage('User ' . $user['first_name'] . ' ' . $user['last_name'] . ' has been disabled', 'success');
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
    
    
    public function resetpasswordAction(){
        
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

            $flashMessenger->addMessage('Password has been reset successfully for '. $user['first_name'] . ' ' . $user['last_name'], 'success');
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
}
