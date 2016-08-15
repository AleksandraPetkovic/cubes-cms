<?php

class Zend_View_Helper_IndexSlideImgUrl extends Zend_View_Helper_Abstract
{
    public function indexSlideImgUrl($indexSlide){
        
        $indexSlideImgFileName = $indexSlide['id'] . '.jpg';
        //putanja do fajla
        $indexSlideImgFilePath = PUBLIC_PATH . '/uploads/index-slides/' . $indexSlideImgFileName;
        //Helper ima property view koji je Zend_View
        //i preko kojeg pozivamo ostale view helpere
        //npr $this->view->baseUrl()
        
        if(is_file($indexSlideImgFilePath)) {
            return $this->view->baseUrl('/uploads/index-slides/' . $indexSlideImgFileName);
        }else{
            //slika je obavezna za slajder
            return '';
        }
    }
}

