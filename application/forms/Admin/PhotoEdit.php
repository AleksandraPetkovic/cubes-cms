<?php

class Application_Form_Admin_PhotoEdit extends Zend_Form
{
    public function init() {
        
        $title = new Zend_Form_Element_Text('title');
        //$firstName->addFilter(new Zend_Filter_StringTrim());   isto sto i ovo dole
        //$firstName->addValidator(new Zend_Validate_StringLength(array('min' => 3, 'max' => 255)));
        
        $title->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) //false znaci nemoj da prekidas akciju ostalih validatora ako ih ima vise od jednog
                ->setRequired(false); //ispituje da li je prazan string, da li je polje obavezno
        
        $this->addElement($title);
        
        
        
        $description = new Zend_Form_Element_Textarea('description');
        $description->addFilter('StringTrim')
                ->setRequired(false);
        $this->addElement($description);
        
    }

}

