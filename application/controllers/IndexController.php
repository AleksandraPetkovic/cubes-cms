<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $cmsClientsDbTable = new Application_Model_DbTable_CmsClients();
       
        $select = $cmsClientsDbTable->select();
        $select->where('status = ?', Application_Model_DbTable_CmsClients::STATUS_ENABLED);
                
        $clients = $cmsClientsDbTable->fetchAll($select);
        $this->view->clients = $clients;
    }


    public function testAction()
    {
        
    }
}

