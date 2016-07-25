<?php

class Admin_TestController extends Zend_Controller_Action {

    public function indexAction() {
        die();
    }

    public function jsintroAction() {
        
    }

    public function jqueryAction() {
        
    }

    public function ajaxintroAction() {
        
    }

    public function ajaxbrandsAction() {

        $brands = array(
            'fiat' => array(
                'punto' => 'Punto',
                'stilo' => 'Stilo',
                '500l' => '500 L'
            ),
            'opel' => array(
                'corsa' => 'Corsa',
                'astra' => 'Astra',
                'vectra' => 'Vectra',
                'insignia' => 'Insignia'
            ),
            'renault' => array(
                'twingo' => 'Twingo',
                'clio' => 'Clio',
                'megane' => 'Megane',
                'scenic' => 'Scenic'
            )
        );
        
        $brandsJson = array();
        
        //pravljenje niza
        foreach ($brands as $brand => $models) {
            
            $brandsJson[] = array(
                'value' => $brand,
                'label' => ucfirst($brand)
            );
        }
        
        //disable layout
        //Zend_Layout::getMvcInstance()->disableLayout();
        
        //disable view script rendering
        //$this->getHelper('ViewRenderer')->setNoRender(true);
        
        //set conetnt type as json instead of html
        //header('Content-Type: application/json');
        
        //echo json_encode($brandsJson);
        
        //isto kao ovo gore samo preko helpera
        $this->getHelper('Json')->sendJson($brandsJson);
    }

    public function ajaxmodelsAction() {

        $brands = array(
            'fiat' => array(
                'punto' => 'Punto',
                'stilo' => 'Stilo',
                '500l' => '500 L'
            ),
            'opel' => array(
                'corsa' => 'Corsa',
                'astra' => 'Astra',
                'vectra' => 'Vectra',
                'insignia' => 'Insignia'
            ),
            'renault' => array(
                'twingo' => 'Twingo',
                'clio' => 'Clio',
                'megane' => 'Megane',
                'scenic' => 'Scenic'
            )
        );
        
        $request = $this->getRequest();
        
        $brand = $request->getParam('brand');
        
        //proveravamo da li postoji taj kljuc
        
        if(!isset($brands[$brand])) {
            
            throw new Zend_Controller_Router_Exception('Unknown brand', 404);
        }
    }

}
