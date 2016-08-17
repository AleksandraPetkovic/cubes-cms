<?php

class Zend_View_Helper_PhotoGalleryLeadingPhotoUrl extends Zend_View_Helper_Abstract
{
    public function photoGalleryLeadingPhotoUrl($photoGallery){
        
        $photoGalleryLeadingPhotoFileName = $photoGallery['id'] . '.jpg';
        //putanja do fajla
        $photoGalleryLeadingPhotoFilePath = PUBLIC_PATH . '/uploads/photo-galleries/' . $photoGalleryLeadingPhotoFileName;
        //Helper ima property view koji je Zend_View
        //i preko kojeg pozivamo ostale view helpere
        //npr $this->view->baseUrl()
        
        if(is_file($photoGalleryLeadingPhotoFilePath)) {
            return $this->view->baseUrl('/uploads/photo-galleries/' . $photoGalleryLeadingPhotoFileName);
        }else{
            //vodeca slika je obavezna
            return '';
        }
    }
}

