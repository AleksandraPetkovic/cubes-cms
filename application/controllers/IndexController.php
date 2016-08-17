<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $cmsIndexSlidesDbTable = new Application_Model_DbTable_CmsIndexSlides();
		
		$indexSlides = $cmsIndexSlidesDbTable->search(array(
			'filters' => array(
				'status' => Application_Model_DbTable_CmsIndexSlides::STATUS_ENABLED
			),
			'orders' => array(
				'order_number' => 'ASC'
			)
		));
		
                
        $cmsServicesDbTable = new Application_Model_DbTable_CmsServices();
		
		$services = $cmsServicesDbTable->search(array(
			'filters' => array(
				'status' => Application_Model_DbTable_CmsServices::STATUS_ENABLED
			),
			'orders' => array(
				'order_number' => 'ASC'
			),
			'limit' => 4
		));
		
		$this->view->indexSlides = $indexSlides;        
		$this->view->services = $services;
    }


    public function testAction()
    {
        
    }
}

