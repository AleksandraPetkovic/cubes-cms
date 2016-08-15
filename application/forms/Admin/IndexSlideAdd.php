<?php

class Application_Form_Admin_IndexSlideAdd extends Zend_Form
{
    public function init() {
        
        $title = new Zend_Form_Element_Text('title');
        //$title->addFilter(new Zend_Filter_StringTrim());   isto sto i ovo dole
        //$title->addValidator(new Zend_Validate_StringLength(array('min' => 3, 'max' => 255)));
        
        $title->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 3, 'max' => 255)) //false znaci nemoj da prekidas akciju ostalih validatora ako ih ima vise od jednog
                ->setRequired(true); //ispituje da li je prazan string, da li je polje obavezno
        
        $this->addElement($title);
        
        
        $linkType = new Zend_Form_Element_Select('link_type');
        $linkType->addMultiOption('NoLink', 'No link is displayed in slide')
            ->addMultiOption('SitemapPage', 'Link to sitemap page')
            ->addMultiOption('InternalLink', 'Link to internal url')    
            ->addMultiOption('ExternalLink', 'Link to external site')  
            ->setRequired(true);
        $this->addElement($linkType);
        
        
        $linkLabel = new Zend_Form_Element_Text('link_label');
        $linkLabel->setRequired(false);
        $this->addElement($linkLabel);
        
        
        $sitemapPageId = new Zend_Form_Element_Text('sitemap_page_id');
        $sitemapPageId->setRequired(false);
        $this->addElement($sitemapPageId);
        
        
        $internalLinkUrl = new Zend_Form_Element_Text('internal_link_url');
        $internalLinkUrl->setRequired(false);
        $this->addElement($internalLinkUrl);
        
        
        $externalLinkUrl = new Zend_Form_Element_Text('external_link_url');
        $externalLinkUrl->setRequired(false);
        $this->addElement($externalLinkUrl);
        
        
        $description = new Zend_Form_Element_Textarea('description');
        $description->addFilter('StringTrim')
                ->setRequired(false);
        $this->addElement($description);
        
        
        $indexSlidePhoto = new Zend_Form_Element_File('index_slide_photo');
        //ogranicava koliko fajlova sme da se okaci to znaci ovo 1, znaci sme max 1 fajl da se upload-uje
        //true znaci ako ima vise od jednog fajla prekida se izvrsavanje koda i ne ide dalje na MimeType
        //a da je false onda kod nastavlja da se izvrsava
        $indexSlidePhoto->addValidator('Count', true, 1)
                ->addValidator('MimeType', true, array('image/jpeg', 'image/gif', 'image/png'))
                ->addValidator('ImageSize', false, array(
                    'minwidth' => 600,
                    'minheight' => 400,
                    'maxwidth' => 2000,
                    'maxheight' => 2000
                    ))
                ->addValidator('Size', false, array(
                    'max' => '10MB'
                    ))
                //disable move file to destination when calling method getValues
                ->setValueDisabled(true)
                ->setRequired(false);
        
        $this->addElement($indexSlidePhoto);
    }

}

