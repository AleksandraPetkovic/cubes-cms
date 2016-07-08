<?php

class Admin_SessionController extends Zend_Controller_Action
{
    public function indexAction() {
        //proverava da li je korisnik ulogovan
        if (Zend_Auth::getInstance()->hasIdentity()){
            //ulogovan je
            
            //redirect na admin_dashboard kontroler i index akciju
            
        }else{
            //Ovde ide redirect na login stranu
        
        $redirector = $this->getHelper('Redirector');
        $redirector instanceof Zend_Controller_Action_Helper_Redirector;
        
        
        $redirector->setExit(true)
                ->gotoRoute(array(
                    
                    'controller' => 'admin_dashboard',
                    'action'=> 'login',
        
                ), 'default', true);
        }
        
        
    }
    
    
    public function loginAction() {
        
        //disejblovanje layout-a
        Zend_Layout::getMvcInstance()->disableLayout();
        
        $loginForm = new Application_Form_Admin_Login();
        
        //post ili get nece biti dostupno ako nema ovih linija dole
        $request = $this->getRequest();
        $request instanceof Zend_Controller_Request_Http;
        
        $flashMessenger = $this->getHelper('FlashMessenger');
        
        $systemMessages = array(
            'success' => $flashMessenger->getMessages('success'),
            'errors'=> $flashMessenger->getMessages('errors'),
        );
        
       if ($request->isPost() && $request->getPost('task') === 'login') {
           //forma za validaciju
           if ($loginForm->isValid($request->getPost())){
               
               $authAdapter =  new Zend_Auth_Adapter_DbTable();
               $authAdapter->setTableName('cms_users')
                       ->setIdentityColumn('username')
                       ->setCredentialColumn('password')
                       ->setCredentialTreatment('MD5(?) AND status !=0');
               
               $authAdapter->setIdentity($loginForm->getValue('username'));
               $authAdapter->setCredential($loginForm->getValue('password'));
               
               $auth = Zend_Auth::getInstance();
                       
               $result = $auth->authenticate($authAdapter);
               
               if($result->isValid()) {
                   //Smestanje kompletnog reda iz tabele cms_users kao i identifikator da je korisnik ulogovan
                   //po defaultu se smesta samo username,a ovako smestamo asocijativni niz tj row iz tabele
                   //Asocijativni niz $user ima kljuceve koji su nazivi kolona u tabeli cms_users
                   
                   $user = (array)$authAdapter->getResultRowObject();
                   //kod za upisivanje row-ova u sesiju
                   $auth->getStorage()->write($user);
                   
                   
                   $redirector = $this->getHelper('Redirector');
                    $redirector instanceof Zend_Controller_Action_Helper_Redirector;


                    $redirector->setExit(true)
                            ->gotoRoute(array(
                                'controller' => 'admin_dashboard',
                                'action' => 'index',
                                    ), 'default', true);
                    
                    
                }else {
                   $systemMessages['errors'][] ='Wrong username or password';
               }
               
           }else {
              $systemMessages['errors'][] = 'Username and password are required'; 
           }
       }
       
       $this->view->systemMessages = $systemMessages;
        
    }
    
    
    
    public function logoutAction() {
        
        $auth = Zend_Auth::getInstance();
        
        //brise indikator da je neko ulogovan
        $auth->clearIdentity();
        
        
        $flashMessenger = $this->getHelper('FlashMessenger');
        
        $flashMessenger->addMessage('You have been logged out', 'success');
        //Ovde ide redirect na login stranu
        
        $redirector = $this->getHelper('Redirector');
        $redirector instanceof Zend_Controller_Action_Helper_Redirector;
        
        
        $redirector->setExit(true)
                ->gotoRoute(array(
                    
                    'controller' => 'admin_session',
                    'action'=> 'login',
        
                ), 'default', true);
        
        
        
//        //go to simple samo ako hocemo da redirektujemo akciju nekog kontrolera, samo ako imamo kontroler i akciju
//        $redirector->setExit(true)
//                ->gotoSimple('login', 'admin_session');
//        
//        //isto sto i ovo gore// redirekt za spoljni link
//        $redirector->setExit(true)
//               ->setPrependBase(false)
//               ->gotoUrl('https://www.facebook.com');
        
    }
    
    
}

