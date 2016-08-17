<?php

class Admin_PhotogalleriesController extends Zend_Controller_Action {

    public function indexAction() {

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        //prikaz svih photoGallery-a
        $cmsPhotoGalleriesDbTable = new Application_Model_DbTable_CmsPhotoGalleries();

//        $photoGalleries = $cmsPhotoGalleriesDbTable->search(array(
//            'filters' => array(
//                'status' => Application_Model_DbTable_CmsPhotoGalleries::STATUS_ENABLED,
//                'work_title' => array(
//                    'PHP Developer', 'lwvknrlbjnr'
//                    )
//            ),
//            'orders' => array(
//                'work_title' => 'ASC',
//                'first_name' => 'ASC',
//                'last_name' => 'ASC'
//            ),
//            //'limit' => 4,
//            //'page' => 2
//        ));
        
        $photoGalleries = $cmsPhotoGalleriesDbTable->search(array(
            'orders' => array(
                'order_number' => 'ASC'
            )
        ));
            

        $this->view->photoGalleries = $photoGalleries;
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

        $form = new Application_Form_Admin_PhotoGalleryAdd();

        //default form data
        $form->populate(array(
        ));



        if ($request->isPost() && $request->getPost('task') === 'save') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for new photoGallery'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();

                //remove key photo_gallery_leading_photo from form data because there is no column 'photo_gallery_leading_photo' in cms_photoGalleries
                unset($formData['photo_gallery_leading_photo']);

                //Insertujemo novi zapis u tabelu
                $cmsPhotoGalleriesTable = new Application_Model_DbTable_CmsPhotoGalleries();


                //insert photoGallery returns ID of the new photoGallery //insertovanje u bazu
                $photoGalleryId = $cmsPhotoGalleriesTable->insertPhotoGallery($formData);

                //da li je uloadovano, provera
                if ($form->getElement('photo_gallery_leading_photo')->isUploaded()) {
                    //photo is uploaded

                    $fileInfos = $form->getElement('photo_gallery_leading_photo')->getFileInfo('photo_gallery_leading_photo');
                    $fileInfo = $fileInfos['photo_gallery_leading_photo'];
                    //isto kao prethodne dve linije gore
                    //$fileInfos = $_FILES['photo_gallery_leading_photo']

                    try {
                        //make je putanja do slike
                        //open uploaded photo in temporary directory
                        $photoGalleryPhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);

                        $photoGalleryPhoto->fit(360, 270);

                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
                        $photoGalleryPhoto->save(PUBLIC_PATH . '/uploads/photo-galleries/' . $photoGalleryId . '.jpg');
                    } catch (Exception $ex) {
                        $flashMessenger->addMessage('Photo Gallery has beeen saved but error occured during image processing', 'success');

                        //redirect to same or another page
                        $redirector = $this->getHelper('Redirector');
                        $redirector->setExit(true)
                                ->gotoRoute(array(
                                    'controller' => 'admin_photogalleries',
                                    //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                                    'action' => 'index',
                                    'id' => $photoGalleryId
                                        ), 'default', true);
                    }
                }


                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('Photo Gallery has beeen saved', 'success');
                
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_photogalleries',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'edit',
                            'id' => $photoGalleryId
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
            throw new Zend_Controller_Router_Exception('Invalid photoGallery id: ' . $id, 404);
        }

        $cmsPhotoGalleriesTable = new Application_Model_DbTable_CmsPhotoGalleries();

        $photoGallery = $cmsPhotoGalleriesTable->getPhotoGalleryById($id);

        if (empty($photoGallery)) {
            throw new Zend_Controller_Router_Exception('No photoGallery is found with id: ' . $id, 404);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );
        //forma sluzi za filtriranje i validaciju polja
        $form = new Application_Form_Admin_PhotoGalleryEdit();

        //default form data
        $form->populate($photoGallery);



        if ($request->isPost() && $request->getPost('task') === 'update') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for photoGallery'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();
                unset($formData['photo_gallery_leading_photo']);

                if ($form->getElement('photo_gallery_leading_photo')->isUploaded()) {
                    //photo is uploaded

                    $fileInfos = $form->getElement('photo_gallery_leading_photo')->getFileInfo('photo_gallery_leading_photo');
                    $fileInfo = $fileInfos['photo_gallery_leading_photo'];
                    //isto kao prethodne dve linije gore
                    //$fileInfos = $_FILES['photo_gallery_leading_photo']

                    try {
                        //make je putanja do slike
                        //open uploaded photo in temporary directory
                        $photoGalleryPhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);

                        $photoGalleryPhoto->fit(360, 270);

                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
                        $photoGalleryPhoto->save(PUBLIC_PATH . '/uploads/photo-galleries/' . $photoGallery['id'] . '.jpg');
                    } catch (Exception $ex) {
                        //ne redirektujemo na neku drugu stranu nego ostajemo na toj strani 
                        throw new Application_Model_Exception_InvalidInput('Error occured during image processing');
                    }
                }

                //Radimo update postojeceg zapisa u tabeli
                $cmsPhotoGalleriesTable->updatePhotoGallery($photoGallery['id'], $formData);



                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('Photo Gallery has beeen updated', 'success');
                
                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_photogalleries',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'index',
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                $systemMessages['errors'][] = $ex->getMessage();
            }
        }

        $cmsPhotosDbTable = new Application_Model_DbTable_CmsPhotos();
        $photos = $cmsPhotosDbTable->search(array(
            'filters' => array(
                'photo_gallery_id' => $photoGallery['id']
            ),
            'orders' => array(
                'order_number' => 'ASC'
            )
        ));
        
        $this->view->systemMessages = $systemMessages;
        $this->view->form = $form;

        $this->view->photoGallery = $photoGallery;
        $this->view->photos = $photos;
    }

    public function deleteAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'delete') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
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
                throw new Zend_Controller_Router_Exception('Invalid photoGallery id: ' . $id);
            }

            $cmsPhotoGalleriesTable = new Application_Model_DbTable_CmsPhotoGalleries();

            $photoGallery = $cmsPhotoGalleriesTable->getPhotoGalleryById($id);


            if (empty($photoGallery)) {

                throw new Zend_Controller_Router_Exception('No photoGallery is found with id: ' . $id, 'errors');
            }

            $cmsPhotoGalleriesTable->deletePhotoGallery($id);


            $flashMessenger->addMessage('Photo Gallery ' . $photoGallery['title'] . 'has been deleted', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
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
                        'controller' => 'admin_photogalleries',
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
                throw new Zend_Controller_Router_Exception('Invalid photoGallery id: ' . $id);
            }

            $cmsPhotoGalleriesTable = new Application_Model_DbTable_CmsPhotoGalleries();

            $photoGallery = $cmsPhotoGalleriesTable->getPhotoGalleryById($id);


            if (empty($photoGallery)) {

                throw new Zend_Controller_Router_Exception('No photoGallery is found with id: ' . $id, 'errors');
            }

            $cmsPhotoGalleriesTable->disablePhotoGallery($id);


            $flashMessenger->addMessage('PhotoGallery ' . $photoGallery['first_name'] . ' ' . $photoGallery['last_name'] . 'has been disabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
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
                        'controller' => 'admin_photogalleries',
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
                throw new Zend_Controller_Router_Exception('Invalid photoGallery id: ' . $id);
            }

            $cmsPhotoGalleriesTable = new Application_Model_DbTable_CmsPhotoGalleries();

            $photoGallery = $cmsPhotoGalleriesTable->getPhotoGalleryById($id);


            if (empty($photoGallery)) {

                throw new Zend_Controller_Router_Exception('No photoGallery is found with id: ' . $id, 'errors');
            }

            $cmsPhotoGalleriesTable->enablePhotoGallery($id);


            $flashMessenger->addMessage('PhotoGallery ' . $photoGallery['first_name'] . ' ' . $photoGallery['last_name'] . 'has been enabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
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
                        'controller' => 'admin_photogalleries',
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

            $cmsPhotoGalleriesTable = new Application_Model_DbTable_CmsPhotoGalleries();

            $cmsPhotoGalleriesTable->updateOrderOfPhotoGalleries($sortedIds);

            $flashMessenger->addMessage('Order is successfully saved', 'success');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {

            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index',
                            ), 'default', true);
        }
    }

    public function dashboardAction(){
        
        $cmsPhotoGalleriesDbTable = new Application_Model_DbTable_CmsPhotoGalleries();
        
        $countOfEnabledPhotoGalleries = $cmsPhotoGalleriesDbTable->count(array(
            'status' => Application_Model_DbTable_CmsPhotoGalleries::STATUS_ENABLED,
        ));
        
        $countAllPhotoGalleries = $cmsPhotoGalleriesDbTable->count();
    
        $this->view->countOfEnabledPhotoGalleries = $countOfEnabledPhotoGalleries;
        $this->view->countAllPhotoGalleries = $countAllPhotoGalleries;
    }
    
    
}
