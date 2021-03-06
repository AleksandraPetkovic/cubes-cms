<?php

//fajl za klasu

class Application_Plugin_Admin extends Zend_Controller_Plugin_Abstract
{
    //vec imamo request objekat
    public function routeShutdown(Zend_Controller_Request_Abstract $request) {
        
        $controllerName = $request->getControllerName();
        
        $actionName = $request->getActionName();
        //mora da se uloguje da bi dosao do admin dela
        if(preg_match('/^admin_/', $controllerName)){
            
            Zend_Layout::getMvcInstance()->setLayout('admin'); //ova klasa renderuje objekat koji mi kazemo// setuje layout
            
            //proverava da li je korisnik ulogovan
            if (
                    !Zend_Auth::getInstance()->hasIdentity()
                     && $controllerName != 'admin_session'       
                            
                ){
                
                $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
                $flashMessenger->addMessage('You must login', 'errors');
                
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
                $redirector->setExit(true)
                        ->gotoRoute(array(

                            'controller' => 'admin_session',
                            'action'=> 'login',

                        ), 'default', true);

            }
        }
    }

}

