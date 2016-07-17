<?php

class Application_Model_DbTable_CmsServices extends Zend_Db_Table_Abstract {

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    protected $_name = 'cms_services'; //pravi naziv tabele u bazi

    /**
     * 
     * @param int $id
     * @return null|array Associative array with keys as cms_services table columns or NULL if not found
     */

    public function getServiceById($id) {
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

    public function updateService($id, $service) {
        //proverava da li je setovan id 
        if (isset($service['id'])) {
            //Forbid changing user id
            unset($service['id']);
        }

        $this->update($service, 'id = ' . $id);
    }

    /**
     * 
     * @param array $service Associative array with keys as column names and values as column new values
     * @return int The ID of new service (autoincrement)
     */
    public function insertService($service) {
        //fetch order number for new member
        $id = $this->insert($service);

        return $id;
    }

    /*
     * @param int $id ID of member to delete
     */

    public function deleteService($id) {

        $this->delete('id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of member to disable
     */
    public function disableService($id) {

        $this->update(array(
            'status' => self::STATUS_DISABLED
                ), 'id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of member to enable
     */
    public function enableService($id) {

        $this->update(array(
            'status' => self::STATUS_ENABLED
                ), 'id = ' . $id);
    }
    
    public function updateOrderOfServices($sortedIds) {

        foreach ($sortedIds as $orderNumber => $id) {
            $this->update(array(
                'order_number' => $orderNumber + 1 //+1 because order_number starts from 1, not from 0
                    ), 'id = ' . $id);
        }
    }

    
}
