<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    //bitno je da pocinje _init
    protected function _initRouter() {
        //ensure if database is configured
        $this->bootstrap('db');
        
        $sitemapPageTypes = array(
            //static page uvek ima u cms-u
            'StaticPage' => array(
                'title' => 'Static Page',
                'subtypes' => array(
                    //0 means unlimited number
                    'StaticPage' => 0
                )
            ),
            
            'AboutUsPage' => array(
                'title' => 'About Us Page',
                'subtypes' => array(
                    
                )
            ),
            
            'ServicesPage' => array(
                'title' => 'Services Page',
                'subtypes' => array(
                    
                )
            ),
            
            'ContactPage' => array(
                'title' => 'Contact Page',
                'subtypes' => array(
                    
                )
            )
        );
        
        //neogranicen br staticnih strana u rutu 0
        //definisemo sta sve moze da se nadje u rutu sajta i koliko
        //tipovi stranica u rutu sajta
        $rootSitemapPageTypes = array(
            'StaticPage' => 0,
            'AboutUsPage' => 1,
            'ServicesPage' => 1,
            'ContactPage' => 1
        );
        
        //klasa koja implementira singleton pattern
        Zend_Registry::set('sitemapPageTypes', $sitemapPageTypes);
        Zend_Registry::set('rootSitemapPageTypes', $rootSitemapPageTypes);
        
        //ruter dobijamo iz Zend_Controller_Front on poziva sve ostale controllere
        $router = Zend_Controller_Front::getInstance()->getRouter();

        // i ima metodu
        $router instanceof Zend_Controller_Router_Rewrite;

        //svaka ruta mora da stoji pod kljucem
        $router->addRoute('contact-us-route', new Zend_Controller_Router_Route(
                'contact-us', 
            array(
            'controller' => 'contact',
            'action' => 'index'
                )
        ))->addRoute('ask-member-route', new Zend_Controller_Router_Route(
                'contact-us/ask-member/member/:id/:member_slug', 
            array(
            'controller' => 'contact',
            'action' => 'askmember'
                )        
        ));  
        
        
        $sitemapPagesMap = Application_Model_DbTable_CmsSitemapPages::getSitemapPageMap();
        
        foreach ($sitemapPagesMap as $sitemapPageId => $sitemapPageMap){
            
            if($sitemapPageMap['type'] == 'StaticPage') {
                
                $router->addRoute('static-page-route-' . $sitemapPageId, new Zend_Controller_Router_Route_Static(
                    $sitemapPageMap['url'], 
                    array(
                        'controller' => 'staticpage',
                        'action' => 'index',
                        'sitemap_page_id' => $sitemapPageId
                    )
                ));
            }
            
            if($sitemapPageMap['type'] == 'AboutUsPage') {
                
                $router->addRoute('static-page-route-' . $sitemapPageId, new Zend_Controller_Router_Route_Static(
                    $sitemapPageMap['url'], 
                    array(
                        'controller' => 'aboutus',
                        'action' => 'index',
                        'sitemap_page_id' => $sitemapPageId
                    )
                ));
                
                $router->addRoute('member-route', new Zend_Controller_Router_Route(
                    $sitemapPageMap['url'] . '/member/:id/:member_slug', 
                    array(
                        'controller' => 'aboutus',
                        'action' => 'member',
                        'member_slug' => ''
                )
            ));
            }
            
            if($sitemapPageMap['type'] == 'ContactPage') {
                
                $router->addRoute('static-page-route-' . $sitemapPageId, new Zend_Controller_Router_Route_Static(
                    $sitemapPageMap['url'], 
                    array(
                        'controller' => 'contact',
                        'action' => 'index',
                        'sitemap_page_id' => $sitemapPageId
                    )
                ));
            }
        }
    }

}
