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
        
        if ($id != 0) {
            $sitemapPage = $cmsSitemapPagesDbTable->getSitemapPageById($id);
        
            if(!$sitemapPage) {
                throw new Zend_Controller_Router_Exception ('No sitemap page is found' , 404);
        }
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
        
        $this->view->currentSitemapPageId = $id;
        $this->view->childSitemapPages = $childSitemapPages;
        $this->view->sitemapPageBreadcrumbs = $sitemapPageBreadcrumbs;
        $this->view->systemMessages = $systemMessages;
    }
    
    public function addAction(){
        
        $request = $this->getRequest();
        
        $parentId = $request->getParam('parent_id', 0);
        
        if($parentId < 0) {
            throw new Zend_Controller_Router_Exception ('Invalid id for sitemap pages' , 404);
        }
        
        $parentType = '';
        
        $cmsSitemapPagesDbTable = new Application_Model_DbTable_CmsSitemapPages();
        
        if ($parentId != 0) {
            //check if parent page exist
            $parentSitemapPage = $cmsSitemapPagesDbTable->getSitemapPageById($parentId);
            
            if (!$parentSitemapPage) {
                throw new Zend_Controller_Router_Exception ('No sitemap page is found for id:' . $parentId , 404);
            }
            
            $parentType = $parentSitemapPage['type'];
        }
        

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        $form = new Application_Form_Admin_SitemapPageAdd($parentId, $parentType);

        //default form data
        $form->populate(array(
        ));



        if ($request->isPost() && $request->getPost('task') === 'save') { //if se izvrsava ako je pokrenuta forma za dodavanje
            
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for new sitemapPage'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();
                
                //set parent_id for new page
                $formData['parent_id'] = $parentId;

                //remove key sitemap_page_photo from form data because there is no column 'sitemap_page_photo' in cms_sitemapPages
                //unset($formData['sitemap_page_photo']);

                


                //insert sitemapPage returns ID of the new sitemapPage //insertovanje u bazu
                $sitemapPageId = $cmsSitemapPagesDbTable->insertSitemapPage($formData);

                //da li je uloadovano, provera
//                if ($form->getElement('sitemap_page_photo')->isUploaded()) {
//                    //photo is uploaded
//
//                    $fileInfos = $form->getElement('sitemap_page_photo')->getFileInfo('sitemap_page_photo');
//                    $fileInfo = $fileInfos['sitemap_page_photo'];
//                    //isto kao prethodne dve linije gore
//                    //$fileInfos = $_FILES['sitemap_page_photo']
//
//                    try {
//                        //make je putanja do slike
//                        //open uploaded photo in temporary directory
//                        $sitemapPagePhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);
//
//                        $sitemapPagePhoto->fit(150, 150);
//
//                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
//                        $sitemapPagePhoto->save(PUBLIC_PATH . '/uploads/sitemapPages/' . $sitemapPageId . '.jpg');
//                    } catch (Exception $ex) {
//                        $flashMessenger->addMessage('SitemapPage has beeen saved but error occured during image processing', 'success');
//
//                        //redirect to same or another page
//                        $redirector = $this->getHelper('Redirector');
//                        $redirector->setExit(true)
//                                ->gotoRoute(array(
//                                    'controller' => 'admin_sitemapPages',
//                                    //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
//                                    'action' => 'index',
//                                    'id' => $sitemapPageId
//                                        ), 'default', true);
//                    }
//                }
//

                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('SitemapPage has beeen saved', 'success');
                
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_sitemap',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                            'id' => $parentId
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                $systemMessages['errors'][] = $ex->getMessage();
            }
        }

        $sitemapPageBreadcrumbs = $cmsSitemapPagesDbTable->getSitemapPageBreadcrumbs($parentId);
        
        $this->view->parentId = $parentId;
        $this->view->systemMessages = $systemMessages;
        $this->view->sitemapPageBreadcrumbs = $sitemapPageBreadcrumbs;
        $this->view->form = $form;
    }
    
    public function editAction() {
        
        $request = $this->getRequest();

        //(int) sve sto nije integer pretvara u nulu :)
        $id = (int) $request->getParam('id');

        if ($id <= 0) {
            //prekida se izvrsavanje programa i prikazuje se "Page not found"
            throw new Zend_Controller_Router_Exception('Invalid sitemapPage id: ' . $id, 404);
        }

        $cmsSitemapPagesTable = new Application_Model_DbTable_CmsSitemapPages();

        $sitemapPage = $cmsSitemapPagesTable->getSitemapPageById($id);

        if (empty($sitemapPage)) {
            throw new Zend_Controller_Router_Exception('No sitemapPage is found with id: ' . $id, 404);
        }
        
        $parentType = '';
        if ($sitemapPage['parent_id'] != 0 ) {
            
            $parentSitemapPage = $cmsSitemapPagesTable->getSitemapPageById($sitemapPage['parent_id']);
            
            $parentType = $parentSitemapPage['type'];
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );
        //forma sluzi za filtriranje i validaciju polja
        $form = new Application_Form_Admin_SitemapPageEdit($sitemapPage['id'], $sitemapPage['parent_id'], $parentType);

        //default form data
        $form->populate($sitemapPage);



        if ($request->isPost() && $request->getPost('task') === 'update') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for sitemapPage'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();
//                unset($formData['sitemapPage_photo']);
//
//                if ($form->getElement('sitemapPage_photo')->isUploaded()) {
//                    //photo is uploaded
//
//                    $fileInfos = $form->getElement('sitemapPage_photo')->getFileInfo('sitemapPage_photo');
//                    $fileInfo = $fileInfos['sitemapPage_photo'];
//                    //isto kao prethodne dve linije gore
//                    //$fileInfos = $_FILES['sitemapPage_photo']
//
//                    try {
//                        //make je putanja do slike
//                        //open uploaded photo in temporary directory
//                        $sitemapPagePhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);
//
//                        $sitemapPagePhoto->fit(150, 150);
//
//                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
//                        $sitemapPagePhoto->save(PUBLIC_PATH . '/uploads/sitemapPages/' . $sitemapPage['id'] . '.jpg');
//                    } catch (Exception $ex) {
//                        //ne redirektujemo na neku drugu stranu nego ostajemo na toj strani 
//                        throw new Application_Model_Exception_InvalidInput('Error occured during image processing');
//                    }
//                }


                //Radimo update postojeceg zapisa u tabeli
                $cmsSitemapPagesTable->updateSitemapPage($sitemapPage['id'], $formData);



                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('SitemapPage has beeen updated', 'success');
                
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_sitemap',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                            'id' => $sitemapPage['parent_id']
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                $systemMessages['errors'][] = $ex->getMessage();
            }
        }

        $sitemapPageBreadcrumbs = $cmsSitemapPagesTable->getSitemapPageBreadcrumbs($sitemapPage['parent_id']);
        
        $this->view->systemMessages = $systemMessages;
        $this->view->sitemapPageBreadcrumbs = $sitemapPageBreadcrumbs;
        $this->view->form = $form;
        $this->view->sitemapPage = $sitemapPage;
    }
    
    
    public function disableAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'disable') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                            'id' => $sitemapPage['parent_id']
                                ), 'default', true);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        try {
            //(int) sve sto nije integer pretvara u nulu :)
            //read $_POST['id']
            $id = (int) $request->getPost('id');

            if ($id <= 0) {
                 //prekida se izvrsavanje programa i prikazuje se "Page not found"
                throw new Zend_Controller_Router_Exception('Invalid SitemapPage id: ' . $id);
            }

            $cmsSitemapPagesTable = new Application_Model_DbTable_CmsSitemapPages();

            $sitemapPage = $cmsSitemapPagesTable->getSitemapPageById($id);


            if (empty($sitemapPage)) {

                throw new Zend_Controller_Router_Exception('No SitemapPage is found with id: ' . $id, 'errors');
            }

            $cmsSitemapPagesTable->disableSitemapPage($id);


            $flashMessenger->addMessage('SitemapPage with type ' . $sitemapPage['type'] . ' and title ' . $sitemapPage['title'] . ' has been disabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                            'id' => $sitemapPage['parent_id']
                                ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                            'action' => 'index',
                            'id' => $sitemapPage['parent_id']
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
                        'controller' => 'admin_sitemap',
                            'action' => 'index',
                            'id' => $sitemapPage['parent_id']
                                ), 'default', true);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        try {
            //(int) sve sto nije integer pretvara u nulu :)
            //read $_POST['id']
            $id = (int) $request->getPost('id');

            if ($id <= 0) {
                //prekida se izvrsavanje programa i prikazuje se "Page not found"
                throw new Zend_Controller_Router_Exception('Invalid Sitemap Page id: ' . $id);
            }

            $cmsSitemapPagesTable = new Application_Model_DbTable_CmsSitemapPages();

            $sitemapPage = $cmsSitemapPagesTable->getSitemapPageById($id);

            if (empty($sitemapPage)) {

                throw new Zend_Controller_Router_Exception('No Sitemap Page is found with id: ' . $id, 'errors');
            }

            $cmsSitemapPagesTable->enableSitemapPage($id);


            $flashMessenger->addMessage('SitemapPage with type ' . $sitemapPage['type'] . ' and title ' . $sitemapPage['title'] . ' has been enabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                            'action' => 'index',
                            'id' => $sitemapPage['parent_id']
                                ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                            'action' => 'index',
                            'id' => $sitemapPage['parent_id']
                                ), 'default', true);
        }
    }

    public function updateorderAction() {
        
        $request = $this->getRequest();

        $parentId = $this->getParam('id');
        
        if (!$request->isPost() || $request->isPost('task') != 'saveOrder') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                            'action' => 'index',
                            'id' => $parentId
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

            $cmsSitemapPagesTable = new Application_Model_DbTable_CmsSitemapPages();

            $cmsSitemapPagesTable->updateOrderOfSitemapPages($sortedIds);

            $flashMessenger->addMessage('Order is successfully saved', 'success');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                            'action' => 'index',
                            'id' => $parentId
                                ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {

            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                            'action' => 'index',
                            'id' => $parentId
                                ), 'default', true);
        }
    }
    
    
    public function deleteAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'delete') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
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
                throw new Zend_Controller_Router_Exception('Invalid sitemapPage id: ' . $id);
            }

            $cmsSitemapPagesTable = new Application_Model_DbTable_CmsSitemapPages();

            $sitemapPage = $cmsSitemapPagesTable->getSitemapPageById($id);


            if (empty($sitemapPage)) {

                throw new Zend_Controller_Router_Exception('No sitemap page is found with id: ' . $id, 'errors');
            }

            $cmsSitemapPagesTable->deleteSitemapPage($id);


            $flashMessenger->addMessage('Sitemap page '  . $sitemapPage['short_title'] .  ' ' . 'has been deleted', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemap',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                        'id' => $sitemapPage['parent_id']
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_sitemapPages',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }
        
}

