<?php

class Admin_SitemapController extends Zend_Controller_Action 
{
    public function indexAction(){
        
        $request = $this->getRequest();
        
        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );
        
        //if no id parameter, than $parameterId will be 0
        //getParam moze da ima samo 1 argument, a drugi ce da bude difoltni
        $id = (int) $request->getParam('id', 0);
        
        if($id < 0) {
            throw new Zend_Controller_Router_Exception ('Invalid id for sitemap pages' , 404);
        }
        
        $cmsSitemapPagesDbTable = new Application_Model_DbTable_CmsSitemapPages();
        
        $sitemapPage = $cmsSitemapPagesDbTable->getSitemapPageById($id);
        
        if(!$sitemapPage && $id !=0) {
            throw new Zend_Controller_Router_Exception ('No sitemap page is found' , 404);
        }
        
        $childSitemapPages = $cmsSitemapPagesDbTable->search(array(
            'filters' => array(
                'parent_id' => $id
            ),
            'orders' => array(
                'order_number' => 'ASC'
            ),
            //'limit' => 50,
            //'page' => 3
        ));
        
        $sitemapPageBreadcrumbs = $cmsSitemapPagesDbTable->getSitemapPageBreadcrumbs($id);
        
        $this->view->childSitemapPages = $childSitemapPages;
        $this->view->sitemapPageBreadcrumbs = $sitemapPageBreadcrumbs;
        $this->view->systemMessages = $systemMessages;
    }
}

