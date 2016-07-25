

<?php

class Application_Model_DbTable_CmsUsers extends Zend_Db_Table_Abstract {

    const DEFAULT_PASSWORD = 'cubesphp';
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    protected $_name = 'cms_users'; //pravi naziv tabele u bazi

    /**
     * 
     * @param int $id
     * @return null|array Associative array with keys as cms_users table columns or NULL if not found
     */

    public function getUserById($id) {
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

    /**
     * 
     * @param array $user Associative array with keys as column names and values as column new values
     * @return int ID of new user
     */
    public function insertUser($user) {

        //set default password for new user
        $user['password'] = md5(self::DEFAULT_PASSWORD);

        return $this->insert($user);
    }

    /**
     * 
     * @param int $id
     * @param array $user Associative array with keys as column names and values as column new values
     */
    public function updateUser($id, $user) {
        //proverava da li je setovan id 
        if (isset($user['id'])) {
            //Forbid changing user id
            unset($user['id']);
        }

        $this->update($user, 'id = ' . $id);
    }

    /**
     * 
     * @param int $id
     * @param string $newPassword Plain password, not hashed
     */
    public function changeUserPassword($id, $newPassword) {
        //update "password" column, set md5 values of new password, for user with id = $id
        //array('password' =>md5($newPassword)) = $data
        //'id = ' .$id = $where
        $this->update(array(
            'password' => md5($newPassword)), 'id = ' . $id);
    }

    /*
     * @param int $id ID of user to delete
     */

    public function deleteUser($id) {

        $this->delete('id = ' . $id);
    }
    
    
    /**
     * 
     * @param int $id ID of user to disable
     */
    public function disableUser($id) {

        $this->update(array(
            'status' => self::STATUS_DISABLED
                ), 'id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of user to enable
     */
    public function enableUser($id) {

        $this->update(array(
            'status' => self::STATUS_ENABLED
                ), 'id = ' . $id);
    }

    public function resetUserPassword($id) {

        $this->update(array(
            'password' => self::DEFAULT_PASSWORD
                ), 'id = ' . $id);
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
                    case 'username':
                    case 'first_name':
                    case 'last_name':
                    case 'email':
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
        $select->columns('COUNT(*) as total');
        
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
                    case 'username':
                    case 'first_name':
                    case 'last_name':
                    case 'email':
                    case 'status':
                    
                        if (is_array($value)){
                            $select->where($field . ' IN (?)', $value);
                        }else {
                            
                            $select->where($field . ' = ?', $value);
                        }

                        break;
                        
                    case 'password':    
                        if (is_array($value)){
                            //menja neki niz vraca true ili false
                            // ,oze i array_map
                            array_walk($value, function(&$element, $key) {
                                //apply md5 on each element in $value array
                                $element = md5($element);
                            });
                                
                            $select->where($field . ' IN (?)', $value);
                        }else {
                            $select->where($field . ' = ?', md5($value));
                        }
                        break;

                    case 'username_search': 
                        $select->where('username LIKE ?', '%' . $value . '%');
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
                    
                        
                    case 'username_exclude':
                        
                        if (is_array($value)) {
                            $select->where('username NOT IN (?)', $value);
                        } else {
                            $select->where('username != ?', $value);
                        }
                        break;    
                }
                
            }
    }

}
