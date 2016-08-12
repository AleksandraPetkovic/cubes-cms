<?php

class Application_Model_DbTable_CmsSitemapPages extends Zend_Db_Table_Abstract
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    protected $_name = 'cms_sitemap_pages';
    
    protected static $sitemapPagesMap;  
    
    
    /**
     * 
     * @return array with keys as sitemap page ids and values as assoc, array with keys url and type
     */
    public static function getSitemapPageMap() {
        //staticka promenljiva
        //lazy loading
        if (!self::$sitemapPagesMap) {
            
                $sitemapPageMap = array();

            //$cmsSitemapPagesDbTable = new Application_Model_DbTable_CmsSitemapPages();
            //isto sto i ovo gore
            $cmsSitemapPagesDbTable = new self();

            $sitemapPages = $cmsSitemapPagesDbTable->search(array(
                'orders' => array(
                    'parent_id' => 'ASC',
                    'order_number' => 'ASC'
                )
            ));

            foreach($sitemapPages as $sitemapPage) {

                $type = $sitemapPage['type'];
                $url = $sitemapPage['url_slug'];

                if(isset($sitemapPagesMap[$sitemapPage['parent_id']])) {

                    $url = $sitemapPagesMap[$sitemapPage['parent_id']]['url'] . '/' . $url;
                }

                $sitemapPagesMap[$sitemapPage['id']] = array(
                    'url' => $url,
                    'type' => $type
                );

            }

            self::$sitemapPagesMap = $sitemapPagesMap;
        }
        
        return self::$sitemapPagesMap;
        
    }
    
    /**
     * 
     * @param int $id
     * @return null|array Associative array with keys as cms_sitemap_pages table columns or NULL if not found
     */

    public function getSitemapPageById($id) {
        //preko ovoga dobijamo kveri bilder
        $select = $this->select();
        $select->where('id = ?', $id);

        //vraca zapise iz tabele // fetchRow vraca 1 red iz tabele
        $row = $this->fetchRow($select);

        //proverava da li je $row instanca Zend_Db_Table_Row
        if ($row instanceof Zend_Db_Table_Row) {
            //vrati mi svoje atribute kao asocijativni niz
            return $row->toArray();
        } else {
            //row is not found
            return null;
        }
    }

    public function updateSitemapPage($id, $sitemapPage) {
        //proverava da li je setovan id 
        if (isset($sitemapPage['id'])) {
            //Forbid changing user id
            unset($sitemapPage['id']);
        }

        $this->update($sitemapPage, 'id = ' . $id);
    }

    /**
     * 
     * @param array $sitemapPage Associative array with keys as column names and values as column new values
     * @return int The ID of new sitemapPage (autoincrement)
     */
    public function insertSitemapPage($sitemapPage) {
        
        $select = $this->select();
        
        //Sort rows by order_number DESCENDING and fetch one row from the top
        //with biggest 'order_number'
        $select->where('parent_id = ?', $sitemapPage['parent_id'])
                ->order('order_number DESC');

        //fetchRow dohvati mi prvi red
        $sitemapPageWithBiggestOrderNumber = $this->fetchRow($select);
        
        if ($sitemapPageWithBiggestOrderNumber instanceof Zend_Db_Table_Row){
            
            
            $sitemapPage['order_number'] = $sitemapPageWithBiggestOrderNumber['order_number'] + 1;
        } else {
            //table was empty, we are inserting first sitemapPage
            $sitemapPage['order_number'] = 1;
            
        }
        

    //fetch order number for new sitemapPage
        $id = $this->insert($sitemapPage);

        return $id;
    }

    /*
     * @param int $id ID of sitemapPage to delete
     */

    public function deleteSitemapPage($id) {
        
        
        //sitemapPage who is going to be deleted
        $sitemapPage = $this->getSitemapPageById($id);
        
        $sitemapPageChildren = $this->search(array(
            'filters' => array(
                'parent_id' => $sitemapPage['id']
            )
        ));
        
        //delete children recursively
        foreach ($sitemapPageChildren as $sitemapPageChild) {
            
            $this->deleteSitemapPage($sitemapPageChild['id']);
        }
        
        
        $this->update(array(
            //ovako se u zendu naglasava
            'order_number' => new Zend_Db_Expr('order_number - 1')
        ),
            'order_number > ' . $sitemapPage['order_number'] . ' AND parent_id = ' .  $sitemapPage['parent_id']);
        
        $this->delete('id = ' . $id);
        
        
    }

    /**
     * 
     * @param int $id ID of sitemapPage to disable
     */
    public function disableSitemapPage($id) {

        $this->update(array(
            'status' => self::STATUS_DISABLED
                ), 'id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of sitemapPage to enable
     */
    public function enableSitemapPage($id) {

        $this->update(array(
            'status' => self::STATUS_ENABLED
                ), 'id = ' . $id);
    }

    public function updateOrderOfSitemapPages($sortedIds) {

        foreach ($sortedIds as $orderNumber => $id) {
            $this->update(array(
                'order_number' => $orderNumber + 1 //+1 because order_number starts from 1, not from 0
                    ), 'id = ' . $id);
        }
    }
    
    
        /**
     * Array parameters is keeeping search parameters.
     * Array $parameters must be in following format:
     *      array(
     *          'filters' array (
     *              'status' => 1,
     *              'id' => array(3, 8, 11) //vracamo tri usera sa id-jevima 3,8,11
     *          )
     *      )
     *          'orders' => array(
     *              'username' => ASC, // ket is column, if value is TRUE then ORDER BY ASC,
     *              'first_name' => DECS, //key is column, if value is FALSE then ORDER BY DESC
     *          )
     *          'limit' => 50, //limit result set to 50 rows
     *          'page' => 3 // start from page 3. If no limits is set, page is ignored// ako nije ogranicen limit page se ne uzima u obzir
     * //a preko f-je count racunamo koliko ima strana
     * @param array $parameters Asoc array with keys "filters", "orders", "limit" and "page"
     */
    public function search(array $parameters = array()){
        //znaci vraca sve redove iz tabele
        //->search();
        $select = $this->select();
        // ovo nije obavezno
        if(isset($parameters['filters'])){
            
            $filters = $parameters['filters'];
            
            $this->processFilters($filters, $select);
        }
        
        if(isset($parameters['orders'])){
            
            $orders = $parameters['orders'];
            
            foreach ($orders as $field => $orderDirection){
                
                switch($field) {
                    case 'id':
                    case 'short_title':
                    case 'url_slug':
                    case 'title':
                    case 'parent_id':
                    case 'type':
                    case 'order_number':    
                    case 'status':    
                        
                        if($orderDirection === 'DESC'){
                            
                            $select->order($field . ' DESC');
                        } else {
                            
                            $select->order($field);
                        }
                        break;
                }
            }
        }
        
        if(isset($parameters['limit'])){
            
            if (isset($parameters['page'])){
                //page is set do limit by page
                $select->limitPage($parameters['page'], $parameters['limit']);
            } else {
                //page is not set, just do regular limit
                $select->limit($parameters['limit']);
            }
        }
        //da vidimo koji bi se kveri izvrsio ukoliko bi doslo do ove f-je
        //die($select->assemble());
        
        return $this->fetchAll($select)->toArray();
        
        
        
    }
    
    /**
     * 
     * @param array $filters See function search $parameters['filters']
     * @return int Count of rows that match filters
     */
    public function count(array $filters = array()) {
        
        $select = $this->select();
        
        $this->processFilters($filters, $select);
        
        //resetujemo vec setovane kolone
        $select->reset('columns');
        //set one column/field to fetch and it is COUNT function
        $select->from($this->_name, 'COUNT(*) as total');
        
        $row =  $this->fetchRow($select);
        
        return $row['total'];
    }
    
    /**
     * Fill select
     * @param array $filters
     * @param Zend_Db_Select $select
     */
    protected function processFilters(array $filters, Zend_Db_Select $select) {
        
        //select object will be modified outside this function
        //object are always passed by reference
        
        foreach ($filters as $field => $value) {
                
                switch ($field) {
                    case 'id':
                    case 'short_title':
                    case 'url_slug':
                    case 'title':
                    case 'parent_id':
                    case 'type':
                    case 'order_number':    
                    case 'status': 
                    
                        if (is_array($value)){
                            $select->where($field . ' IN (?)', $value);
                        }else {
                            
                            $select->where($field . ' = ?', $value);
                        }

                        break;
                        
                    case 'short_title_search': 
                        $select->where('short_title LIKE ?', '%' . $value . '%');
                        break;
                    
                    case 'title_search':
                        $select->where('title LIKE ?', '%' . $value . '%');
                        break;
                    
                    case 'description_search':
                        $select->where('description LIKE ?', '%' . $value . '%');
                        break;
                    
                    case 'body_search':
                        $select->where('body LIKE ?', '%' . $value . '%');
                        break;
                    
                    //iskljucivanje tog id-ja
                    case 'id_exclude':
                        
                        if (is_array($value)) {
                            $select->where('id NOT IN (?)', $value);
                        } else {
                            $select->where('id != ?', $value);
                        }
                        break;
                      
                }
                
            }
    }
    
    /**
     * 
     * @param int $id The ID of sitemap page
     * @return array Sitemap page rows in path
     */
    public function getSitemapPageBreadcrumbs($id) {
        
        $sitemapPagesBreadcrumbs = array();
        
        
        // news/ sport / football
        //  1   /   3   /   5
        
        //5 (football)
        //3 (sport, football)
        //1 (news, sport, football)
        
        //array(
        //  array(
        //      id => 1,
        //      parent_id => 0,
        //      title = > news
        //  ),
        //  array(
        //      id => 3,
        //      parent_id => 1,
        //      title = > news
        //  ),
        //  array(
        //      id => 5,
        //      parent_id => 3,
        //      title = > news
        //  ),
        //)
        while ($id > 0) {
            
            $sitemapPageInPath = $this->getSitemapPageById($id);
            
            if($sitemapPageInPath) {
                
                $id = $sitemapPageInPath['parent_id'];
                
                //add curent page at the beggining of breadcrumbs array
                array_unshift($sitemapPagesBreadcrumbs, $sitemapPageInPath);
                
            } else {
                $id = 0;
            }
            
        }
        
        return $sitemapPagesBreadcrumbs;
    }
    
    /**
     * Returns count type example:
     * array(
     *  'StaticPage' => 3,
        'AboutUsPage' => 1,
        'ContactPage' => 1,
     *      ...
     * )
     *  
     * @param type $filters
     * @return array Count by type
     */
    public function countByTypes($filters = array()){
        $select = $this->select();
        
        $this->processFilters($filters, $select);
        
        //resetujemo vec setovane kolone
        $select->reset('columns');
        //set one column/field to fetch and it is COUNT function
        $select->from($this->_name, array(
            'type',
            'COUNT(*) as total_by_type'
        ));
        $select->group('type');
        
        //die($select->assemble());
        
        $rows =  $this->fetchAll($select);
        
        $countByTypes = array();
        
        foreach ($rows as $row) {
            $countByTypes[$row['type']] = $row['total_by_type'];
        }
        
        return $countByTypes;
    }
            

}

