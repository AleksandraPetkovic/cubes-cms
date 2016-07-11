<?php

class ServiceController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        //prikaz svih member-a
        $cmsServicesDbTable = new Application_Model_DbTable_CmsServices();

        //select je objekat klase Zend_Db_Select
        $select = $cmsServicesDbTable->select();


        $services = $cmsServicesDbTable->fetchAll($select);

        $this->view->services = $services;
        $this->view->systemMessages = $systemMessages;
    }


}

