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

        //select je objekat klase Zend_Db_Select
        $select = $cmsMembersDbTable->select();

        $select->order('order_number');

        //debug za  db select - vraca se sql upit
        //die($select->assemble());

        $members = $cmsMembersDbTable->fetchAll($select);

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

                //Insertujemo novi zapis u tabelu
                $cmsMembersTable = new Application_Model_DbTable_CmsMembers();


                $cmsMembersTable->insertMember($formData);



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
        $form = new Application_Form_Admin_MemberAdd();

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

}
