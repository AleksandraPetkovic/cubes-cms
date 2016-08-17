<?php

class Application_Model_DbTable_CmsPhotoGalleries extends Zend_Db_Table_Abstract {

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    protected $_name = 'cms_photo_galleries'; //pravi naziv tabele u bazi

    /**
     * 
     * @param int $id
     * @return null|array Associative array with keys as cms_photoGalleries table columns or NULL if not found
     */

    public function getPhotoGalleryById($id) {
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

    public function updatePhotoGallery($id, $photoGallery) {
        //proverava da li je setovan id 
        if (isset($photoGallery['id'])) {
            //Forbid changing user id
            unset($photoGallery['id']);
        }

        $this->update($photoGallery, 'id = ' . $id);
    }

    /**
     * 
     * @param array $photoGallery Associative array with keys as column names and values as column new values
     * @return int The ID of new photoGallery (autoincrement)
     */
    public function insertPhotoGallery($photoGallery) {
        
        $select = $this->select();
        
        //Sort rows by order_number DESCENDING and fetch one row from the top
        //with biggest 'order_number'
        $select->order('order_number DESC');

        $photoGalleryWithBiggestOrderNumber = $this->fetchRow($select);
        
        if ($photoGalleryWithBiggestOrderNumber instanceof Zend_Db_Table_Row){
            
            
            $photoGallery['order_number'] = $photoGalleryWithBiggestOrderNumber['order_number'] + 1;
        } else {
            //table was empty, we are inserting first photoGallery
            $photoGallery['order_number'] = 1;
            
        }
        

    //fetch order number for new photoGallery
        $id = $this->insert($photoGallery);

        return $id;
    }

    /*
     * @param int $id ID of photoGallery to delete
     */

    public function deletePhotoGallery($id) {
        
        $photoGalleryPhotoFilePath = PUBLIC_PATH . '/uploads/photo-galleries' . $id . '.jpg';
        //provera da li je fajl
        if (is_file($photoGalleryPhotoFilePath)){
            
            //delete photoGallery photo file
            unlink($photoGalleryPhotoFilePath);
        }
        //photoGallery who is going to be deleted
        $photoGallery = $this->getPhotoGalleryById($id);
        
        $cmsPhotosDbTable = new Application_Model_DbTable_CmsPhotos();
        $childPhotos = $cmsPhotosDbTable->search(array(
            'filters' => array (
                'photo_gallery_id' => $photoGallery['id']
            )
        ));
        
        foreach($childPhotos as $childPhoto) {
            $cmsPhotosDbTable->deletePhoto($childPhoto['id']);
        }
        
        
        $this->update(array(
            //ovako se u zendu naglasava
            'order_number' => new Zend_Db_Expr('order_number - 1')
        ),
            'order_number > ' . $photoGallery['order_number']);
        
        $this->delete('id = ' . $id);
        
        
    }

    /**
     * 
     * @param int $id ID of photoGallery to disable
     */
    public function disablePhotoGallery($id) {

        $this->update(array(
            'status' => self::STATUS_DISABLED
                ), 'id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of photoGallery to enable
     */
    public function enablePhotoGallery($id) {

        $this->update(array(
            'status' => self::STATUS_ENABLED
                ), 'id = ' . $id);
    }

    public function updateOrderOfPhotoGalleries($sortedIds) {

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
                    case 'title':
                    case 'description':
                    case 'status':
                    case 'order_number':
                        
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
                    case 'title':
                    case 'description':
                    case 'status':
                    
                        if (is_array($value)){
                            $select->where($field . ' IN (?)', $value);
                        }else {
                            
                            $select->where($field . ' = ?', $value);
                        }

                        break;
                        

                    
                    case 'title_search':
                        $select->where('title LIKE ?', '%' . $value . '%');
                        break;
                    
                    case 'description_search':
                        $select->where('description LIKE ?', '%' . $value . '%');
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

}
