<?php

class Application_Model_DbTable_CmsMembers extends Zend_Db_Table_Abstract
{
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
       }else {
           //row is not found
           return null;
       }
    }
    
    
    
    public function updateMember($id, $member) {
        //proverava da li je setovan id 
        if(isset($member['id'])){
            //Forbid changing user id
            unset($member['id']);
        }
        
        $this->update($member, 'id = ' .$id);
    }
    
    /**
     * 
     * @param array $member Associative array with keys as column names and values as column new values
     * @return int The ID of new member (autoincrement)
     */
    public function insertMember($member){
        //fetch order number for new member
        $id = $this->insert($member);
        
        return $id;
    }
}

