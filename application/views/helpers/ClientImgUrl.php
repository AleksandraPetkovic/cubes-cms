<?php

class Zend_View_Helper_ClientImgUrl extends Zend_View_Helper_Abstract
{
    public function clientImgUrl($client){
        
        $clientImgFileName = $client['id'] . '.jpg';
        //putanja do fajla
        $clientImgFilePath = PUBLIC_PATH . '/uploads/clients/' . $clientImgFileName;
        //Helper ima property view koji je Zend_View
        //i preko kojeg pozivamo ostale view helpere
        //npr $this->view->baseUrl()
        
        if(is_file($clientImgFilePath)) {
            return $this->view->baseUrl('/uploads/clients/' . $clientImgFileName);
        }else{
            return $this->view->baseUrl('/uploads/clients/no-image.jpg');
        }
    }
}



