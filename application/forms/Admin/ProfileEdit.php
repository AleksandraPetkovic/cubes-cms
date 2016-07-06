<?php

class Application_Form_Admin_ProfileEdit extends Zend_Form
{
    public function init() {
        
        $firstName = new Zend_Form_Element_Text('first_name');
        
        $firstName->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) //false znaci nemoj da prekidas akciju ostalih validatora ako ih ima vise od jednog
                ->setRequired(true); //ispituje da li je prazan string, da li je polje obavezno
        
        $this->addElement($firstName);
        
        
        
        $lastName = new Zend_Form_Element_Text('last_name');
        $lastName->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) 
                ->setRequired(true); 
        $this->addElement($lastName);
        
        
        
        $email = new Zend_Form_Element_Text('email');
        $email->addFilter('StringTrim')
                ->addValidator('EmailAddress', false, array('domain' => false)) //proverava da li postoji domenski deo na netu, ta opcija treba da se iskljuci
                ->setRequired(true); 
        $this->addElement($email);
    }

}

