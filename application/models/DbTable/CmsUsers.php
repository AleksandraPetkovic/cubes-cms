

<?php

class Application_Model_DbTable_CmsUsers extends Zend_Db_Table_Abstract
{
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
       }else {
           //row is not found
           return null;
       }
    }
    
    
    /**
     * 
     * @param int $id
     * @param array $user Associative array with keys as column names and values as column new values
     */
    public function updateUser($id, $user) {
        //proverava da li je setovan id 
        if(isset($user['id'])){
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
    public function changeUserPassword($id, $newPassword){
        //update "password" column, set md5 values of new password, for user with id = $id
        //array('password' =>md5($newPassword)) = $data
        //'id = ' .$id = $where
        $this->update(array('password' =>md5($newPassword)), 'id = ' . $id);
    }
}



