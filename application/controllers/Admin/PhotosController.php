<?php

class Admin_PhotosController extends Zend_Controller_Action {

    public function addAction() {

        $request = $this->getRequest();

        //provera da li postoji photo gallery
        $photoGalleryId = (int) $request->getParam('photo_gallery_id');

        if ($photoGalleryId <= 0) {
            //prekida se izvrsavanje programa i prikazuje se "Page not found"
            throw new Zend_Controller_Router_Exception('Invalid photoGallery id: ' . $photoGalleryId, 404);
        }

        $cmsPhotoGalleriesTable = new Application_Model_DbTable_CmsPhotoGalleries();

        $photoGallery = $cmsPhotoGalleriesTable->getPhotoGalleryById($photoGalleryId);

        if (empty($photoGallery)) {
            throw new Zend_Controller_Router_Exception('No photoGallery is found with id: ' . $photoGalleryId, 404);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );

        $form = new Application_Form_Admin_PhotoAdd();

        //default form data
        $form->populate(array(
        ));



        if ($request->isPost() && $request->getPost('task') === 'save') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for new photo'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();

                //remove key photo_upload from form data because there is no column 'photo_upload' in cms_photos
                unset($formData['photo_upload']);
                $formData['photo_gallery_id'] = $photoGallery['id'];

                //Insertujemo novi zapis u tabelu
                $cmsPhotosTable = new Application_Model_DbTable_CmsPhotos();


                //insert photo returns ID of the new photo //insertovanje u bazu
                $photoId = $cmsPhotosTable->insertPhoto($formData);

                //da li je uloadovano, provera
                if ($form->getElement('photo_upload')->isUploaded()) {
                    //photo is uploaded

                    $fileInfos = $form->getElement('photo_upload')->getFileInfo('photo_upload');
                    $fileInfo = $fileInfos['photo_upload'];
                    //isto kao prethodne dve linije gore
                    //$fileInfos = $_FILES['photo_upload']

                    try {
                        //make je putanja do slike
                        //open uploaded photo in temporary directory
                        $photoPhoto = Intervention\Image\ImageManagerStatic::make($fileInfo['tmp_name']);

                        $photoPhoto->fit(660, 495);

                        //kakvu god ekstenziju da ubacimo on je konvertuje u jpg// 
                        $photoPhoto->save(PUBLIC_PATH . '/uploads/photo-galleries/photos/' . $photoId . '.jpg');
                    } catch (Exception $ex) {
                        
                        $flashMessenger->addMessage('Photo has beeen saved but error occured during image processing', 'success');

                        //redirect to same or another page
                        $redirector = $this->getHelper('Redirector');
                        $redirector->setExit(true)
                                ->gotoRoute(array(
                                    'controller' => 'admin_photogalleries',
                                    //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                                    'action' => 'edit',
                                    'id' => $photoGallery['id']
                                        ), 'default', true);
                    }
                }


                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('Photo has beeen saved', 'success');

                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_photogalleries',
                            'action' => 'edit',
                            'id' => $photoGallery['id']
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                
                $systemMessages['errors'][] = $ex->getMessage();
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_photogalleries',
                            'action' => 'edit',
                            'id' => $photoGallery['id']
                                ), 'default', true);
            }
        }
        
        $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_photogalleries',
                            'action' => 'edit',
                            'id' => $photoGallery['id']
                                ), 'default', true);
    }

    public function editAction() {

        $request = $this->getRequest();

        //(int) sve sto nije integer pretvara u nulu :)
        $id = (int) $request->getParam('id');

        if ($id <= 0) {
            //prekida se izvrsavanje programa i prikazuje se "Page not found"
            throw new Zend_Controller_Router_Exception('Invalid photo id: ' . $id, 404);
        }

        $cmsPhotosTable = new Application_Model_DbTable_CmsPhotos();

        $photo = $cmsPhotosTable->getPhotoById($id);

        if (empty($photo)) {
            throw new Zend_Controller_Router_Exception('No photo is found with id: ' . $id, 404);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors' => $flashMessenger->getMessages('errors'),
        );
        //forma sluzi za filtriranje i validaciju polja
        $form = new Application_Form_Admin_PhotoEdit();


        //default form data
        $form->populate($photo);



        if ($request->isPost() && $request->getPost('task') === 'update') { //if se izvrsava ako je pokrenuta forma
            try {

                //check form is valid
                if (!$form->isValid($request->getPost())) { //ako su svi validatori prosli forma je validna
                    throw new Application_Model_Exception_InvalidInput('Invalid data was sent for photo'); //trazimo gresku bacamo exception
                }

                //get form data
                $formData = $form->getValues();


                //Radimo update postojeceg zapisa u tabeli
                $cmsPhotosTable->updatePhoto($photo['id'], $formData);

                // do actual task
                //save to database etc
                //set system message
                //ovde redjamo sistemske poruke
                $flashMessenger->addMessage('Photo has beeen updated', 'success');

                //redirect to same or another page
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_photogalleries',
                            //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                            'action' => 'edit',
                            'id' => $photo['photo_gallery_id']
                                ), 'default', true);
            } catch (Application_Model_Exception_InvalidInput $ex) { //hvatamo gresku i ispisujemo je :)
                $flashMessenger->addMessage($ex->getMessage(), 'error');
                $redirector = $this->getHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(
                            'controller' => 'admin_photogalleries',
                            'action' => 'edit',
                            'id' => $photo['photo_gallery_id']
                                ), 'default', true);
            }
        }

        $redirector = $this->getHelper('Redirector');
        $redirector->setExit(true)
                ->gotoRoute(array(
                    'controller' => 'admin_photogalleries',
                    'action' => 'index',
                        ), 'default', true);
    }

    public function deleteAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || $request->isPost('task') != 'delete') {
            //request is not post redirect to index page
            //redirect to same or another page
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogallery',
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
                throw new Zend_Controller_Router_Exception('Invalid photo id: ' . $id);
            }

            $cmsPhotosTable = new Application_Model_DbTable_CmsPhotos();

            $photo = $cmsPhotosTable->getPhotoById($id);


            if (empty($photo)) {

                throw new Zend_Controller_Router_Exception('No photo is found with id: ' . $id, 'errors');
            }

            $cmsPhotosTable->deletePhoto($id);


            $flashMessenger->addMessage('Photo has been deleted', 'success');
            $redirector = $this->getHelper('Redirector');
        $redirector->setExit(true)
                ->gotoRoute(array(
                    'controller' => 'admin_photogalleries',
                    'action' => 'edit',
                    'id' => $photo['photo_gallery_id']
                        ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
        $redirector->setExit(true)
                ->gotoRoute(array(
                    'controller' => 'admin_photogalleries',
                    'action' => 'index'
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
                            'action' => 'edit',
                            'id' => $photoGallery['photo_gallery_id']
                                ), 'default', true);
        }

        $flashMessenger = $this->getHelper('FlashMessenger');

        try {
            //(int) sve sto nije integer pretvara u nulu :)
            //read $_POST['id']
            $id = (int) $request->getPost('id');

            if ($id <= 0) {


                //prekida se izvrsavanje programa i prikazuje se "Page not found"
                throw new Zend_Controller_Router_Exception('Invalid photo id: ' . $id);
            }

            $cmsPhotosTable = new Application_Model_DbTable_CmsPhotos();

            $photo = $cmsPhotosTable->getPhotoById($id);


            if (empty($photo)) {

                throw new Zend_Controller_Router_Exception('No photo is found with id: ' . $id, 'errors');
            }

            $cmsPhotosTable->disablePhoto($id);


            $flashMessenger->addMessage('Photo has been disabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        'action' => 'edit',
                        'id' => $photo['photo_gallery_id']
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
                throw new Zend_Controller_Router_Exception('Invalid photo id: ' . $id);
            }

            $cmsPhotosTable = new Application_Model_DbTable_CmsPhotos();

            $photo = $cmsPhotosTable->getPhotoById($id);


            if (empty($photo)) {

                throw new Zend_Controller_Router_Exception('No photo is found with id: ' . $id, 'errors');
            }

            $cmsPhotosTable->enablePhoto($id);


            $flashMessenger->addMessage('Photo has been enabled', 'success');
            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'edit',
                        'id' => $photo['photo_gallery_id']
                            ), 'default', true);
        } catch (Application_Model_Exception_InvalidInput $ex) {
            $flashMessenger->addMessage($ex->getMessage(), 'errors');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'index'
                            ), 'default', true);
        }
    }

    public function updateorderAction() {
        $request = $this->getRequest();
        
        
        $photoGalleryId = (int) $request->getParam('photo_gallery_id');

        if ($photoGalleryId <= 0) {
            //prekida se izvrsavanje programa i prikazuje se "Page not found"
            throw new Zend_Controller_Router_Exception('Invalid photoGallery id: ' . $photoGalleryId, 404);
        }

        $cmsPhotoGalleriesTable = new Application_Model_DbTable_CmsPhotoGalleries();

        $photoGallery = $cmsPhotoGalleriesTable->getPhotoGalleryById($photoGalleryId);

        if (empty($photoGallery)) {
            throw new Zend_Controller_Router_Exception('No photo Gallery is found with id: ' . $photoGalleryId, 404);
        }
        

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

            $cmsPhotosTable = new Application_Model_DbTable_CmsPhotos();

            $cmsPhotosTable->updateOrderOfPhotos($sortedIds);

            $flashMessenger->addMessage('Order is successfully saved', 'success');

            $redirector = $this->getHelper('Redirector');
            $redirector->setExit(true)
                    ->gotoRoute(array(
                        'controller' => 'admin_photogalleries',
                        //ako se ne stavi action onda se podrazumeva index, ovo je stavljeno radi jasnoce :)
                        'action' => 'edit',
                        'id' => $photoGallery['id']
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
}
