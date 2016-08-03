<?php

class Application_Model_DbTable_CmsMembers extends Zend_Db_Table_Abstract {

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    protected $_name = 'cms_members'; //pravi naziv tabele u bazi

    /**
     * 
     * @param int $id
     * @return null|array Associative array with keys as cms_members table columns or NULL if not found
     */

    public function getMemberById($id) {
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

    public function updateMember($id, $member) {
        //proverava da li je setovan id 
        if (isset($member['id'])) {
            //Forbid changing user id
            unset($member['id']);
        }

        $this->update($member, 'id = ' . $id);
    }

    /**
     * 
     * @param array $member Associative array with keys as column names and values as column new values
     * @return int The ID of new member (autoincrement)
     */
    public function insertMember($member) {
        
        $select = $this->select();
        
        //Sort rows by order_number DESCENDING and fetch one row from the top
        //with biggest 'order_number'
        $select->order('order_number DESC');

        $memberWithBiggestOrderNumber = $this->fetchRow($select);
        
        if ($memberWithBiggestOrderNumber instanceof Zend_Db_Table_Row){
            
            
            $member['order_number'] = $memberWithBiggestOrderNumber['order_number'] + 1;
        } else {
            //table was empty, we are inserting first member
            $member['order_number'] = 1;
            
        }
        

    //fetch order number for new member
        $id = $this->insert($member);

        return $id;
    }

    /*
     * @param int $id ID of member to delete
     */

    public function deleteMember($id) {
        
        $memberPhotoFilePath = PUBLIC_PATH . '/uploads/members' . $id . '.jpg';
        //provera da li je fajl
        if (is_file($memberPhotoFilePath)){
            
            //delete member photo file
            unlink($memberPhotoFilePath);
        }
        //member who is going to be deleted
        $member = $this->getMemberById($id);
        
        $this->update(array(
            //ovako se u zendu naglasava
            'order_number' => new Zend_Db_Expr('order_number - 1')
        ),
            'order_number > ' . $member['order_number']);
        
        $this->delete('id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of member to disable
     */
    public function disableMember($id) {

        $this->update(array(
            'status' => self::STATUS_DISABLED
                ), 'id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of member to enable
     */
    public function enableMember($id) {

        $this->update(array(
            'status' => self::STATUS_ENABLED
                ), 'id = ' . $id);
    }

    public function updateOrderOfMembers($sortedIds) {

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
                    case 'first_name':
                    case 'last_name':
                    case 'email':
                    case 'status':
                    case 'order_number':
                    case 'work_title':
                        
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
                    case 'first_name':
                    case 'last_name':
                    case 'email':
                    case 'status':
                    case 'work_title':
                    
                        if (is_array($value)){
                            $select->where($field . ' IN (?)', $value);
                        }else {
                            
                            $select->where($field . ' = ?', $value);
                        }

                        break;
                        

                    
                    case 'first_name_search':
                        $select->where('first_name LIKE ?', '%' . $value . '%');
                        break;
                    
                    case 'last_name_search':
                        $select->where('last_name LIKE ?', '%' . $value . '%');
                        break;
                    
                    case 'email_search':
                        $select->where('email LIKE ?', '%' . $value . '%');
                        break;
                    
                    //iskljucivanje tog id-ja
                    case 'id_exclude':
                        
                        if (is_array($value)) {
                            $select->where('id NOT IN (?)', $value);
                        } else {
                            $select->where('id != ?', $value);
                        }
                        break;
                        
                        
                   case 'work_title_exclude':
                        
                        if (is_array($value)) {
                            $select->where('work_title NOT IN (?)', $value);
                        } else {
                            $select->where('work_title != ?', $value);
                        }
                        break;     
                    
                      
                }
                
            }
    }

}
