<?php

class Application_Form_Admin_MemberAdd extends Zend_Form
{
    public function init() {
        
        $firstName = new Zend_Form_Element_Text('first_name');
        //$firstName->addFilter(new Zend_Filter_StringTrim());   isto sto i ovo dole
        //$firstName->addValidator(new Zend_Validate_StringLength(array('min' => 3, 'max' => 255)));
        
        $firstName->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) //false znaci nemoj da prekidas akciju ostalih validatora ako ih ima vise od jednog
                ->setRequired(true); //ispituje da li je prazan string, da li je polje obavezno
        
        $this->addElement($firstName);
        
        
        $lastName = new Zend_Form_Element_Text('last_name');
        $lastName->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) 
                ->setRequired(true); 
        $this->addElement($lastName);
        
        
        $workTitle = new Zend_Form_Element_Text('work_title');
        $workTitle->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) 
                ->setRequired(false); 
        $this->addElement($workTitle);
        
        
        $email = new Zend_Form_Element_Text('email');
        $email->addFilter('StringTrim')
                ->addValidator('EmailAddress', false, array('domain' => false)) //proverava da li postoji domenski deo na netu, ta opcija treba da se iskljuci
                ->setRequired(true); 
        $this->addElement($email);
        
        
        $resume = new Zend_Form_Element_Textarea('resume');
        $resume->addFilter('StringTrim')
                ->setRequired(false);
        $this->addElement($resume);
        
        
        $memberPhoto = new Zend_Form_Element_File('member_photo');
        //ogranicava koliko fajlova sme da se okaci to znaci ovo 1, znaci sme max 1 fajl da se upload-uje
        //true znaci ako ima vise od jednog fajla prekida se izvrsavanje koda i ne ide dalje na MimeType
        //a da je false onda kod nastavlja da se izvrsava
        $memberPhoto->addValidator('Count', true, 1)
                ->addValidator('MimeType', true, array('image/jpeg', 'image/gif', 'image/png'))
                ->addValidator('ImageSize', false, array(
                    'minwidth' => 150,
                    'minheight' => 150,
                    'maxwidth' => 2000,
                    'maxheight' => 2000
                    ))
                ->addValidator('Size', false, array(
                    'max' => '10MB'
                    ))
                //disable move file to destination when calling method getValues
                ->setValueDisabled(true)
                ->setRequired(false);
        
        $this->addElement($memberPhoto);
    }

}

