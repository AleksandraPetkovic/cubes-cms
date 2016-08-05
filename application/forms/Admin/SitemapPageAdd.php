<?php

class Application_Form_Admin_SitemapPageAdd extends Zend_Form
{
    
    protected $parentId;
    
    public function __construct($parentId, $options = null) {
        
        $this->parentId = $parentId;
        
        parent::__construct($options);
    }

    public function init(){
       
        //type
        //url_slug
        //short_title
        //title
        //description
        //body
        
        //Zend_Form_Element_Select;
        //Zend_Form_Element_Multiselect;
        //Zend_Form_Element_Multiselect;
        
        $type = new Zend_Form_Element_Select('type');
//        $type->setMultiOptions(array(
//            '' => '--- Select SiteMap Page Type ---')
//        );
//        no can do 
        
        $type->addMultiOption('', '--- Select SiteMap Page Type ---')
             ->addMultiOptions(array(
                 'StaticPage' => 'Static Page',
             ))->setRequired(true);
        
        //doddavanje na formu
        $this->addElement($type);
        
        
        $urlSlug = new Zend_Form_Element_Text('url_slug');
        $urlSlug->addFilter('StringTrim')
                ->addFilter(new Application_Model_Filter_UrlSlug())
                ->addValidator(new Zend_Validate_Db_NoRecordExists(array(
                    'table' => 'cms_sitemap_pages',
                    'field' => 'url_slug',
                    'exclude' => 'parent_id = ' . $this->parentId
                    )))
                ->addValidator('StringLength', false, array('min' => 2, 'max' => 255)) 
                ->setRequired(true);
        $this->addElement($urlSlug);
                
        
        $short_title = new Zend_Form_Element_Text('short_title');
        $short_title->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 2, 'max' => 255)) 
                ->setRequired(true);
        $this->addElement($short_title);
                
                
        $title = new Zend_Form_Element_Text('title');
        $title->addFilter('StringTrim')
                ->addValidator('StringLength', false, array('min' => 2, 'max' => 500)) 
                ->setRequired(true);
        $this->addElement($title) ;  
        
        
        $description = new Zend_Form_Element_Text('description');
        $description->addFilter('StringTrim')
                ->setRequired(false);
        $this->addElement($description) ;  
        
        
        $body = new Zend_Form_Element_Textarea('body');
        $body->setRequired(true);
        $this->addElement($body);
    }
}

