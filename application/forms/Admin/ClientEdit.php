<?php

class Application_Form_Admin_ClientEdit extends Zend_Form {

    public function init() {

        $name = new Zend_Form_Element_Text('name');

        $name->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) //false znaci nemoj da prekidas akciju ostalih validatora ako ih ima vise od jednog
                ->setRequired(true); //ispituje da li je prazan string, da li je polje obavezno

        $this->addElement($name);


        $description = new Zend_Form_Element_Textarea('description');

        $description->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) //false znaci nemoj da prekidas akciju ostalih validatora ako ih ima vise od jednog
                ->setRequired(false); //ispituje da li je prazan string, da li je polje obavezno

        $this->addElement($description);


        $clientPhoto = new Zend_Form_Element_File('client_photo');
        //ogranicava koliko fajlova sme da se okaci to znaci ovo 1, znaci sme max 1 fajl da se upload-uje
        //true znaci ako ima vise od jednog fajla prekida se izvrsavanje koda i ne ide dalje na MimeType
        //a da je false onda kod nastavlja da se izvrsava
        $clientPhoto->addValidator('Count', true, 1)
                ->addValidator('MimeType', true, array('image/jpeg', 'image/gif', 'image/png'))
                ->addValidator('ImageSize', false, array(
                    'minwidth' => 170,
                    'minheight' => 70,
                    'maxwidth' => 2000,
                    'maxheight' => 2000
                ))
                ->addValidator('Size', false, array(
                    'max' => '10MB'
                ))
                //disable move file to destination when calling method getValues
                ->setValueDisabled(true)
                ->setRequired(true);

        $this->addElement($clientPhoto);
    }

}
