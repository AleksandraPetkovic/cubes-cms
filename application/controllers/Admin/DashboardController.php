<?php

class Admin_DashboardController extends Zend_Controller_Action
{
    public function indexAction(){
        
        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        $this->view->users = array();
        $this->view->systemMessages = $systemMessages;
        
    }
}

