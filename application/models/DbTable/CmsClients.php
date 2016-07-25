<?php

class Application_Model_DbTable_CmsClients extends Zend_Db_Table_Abstract {

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    protected $_name = 'cms_clients'; //pravi naziv tabele u bazi

    /**
     * 
     * @param int $id
     * @return null|array Associative array with keys as cms_clients table columns or NULL if not found
     */

    public function getClientById($id) {
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

    public function updateClient($id, $client) {
        //proverava da li je setovan id 
        if (isset($client['id'])) {
            //Forbid changing user id
            unset($client['id']);
        }

        $this->update($client, 'id = ' . $id);
    }

    /**
     * 
     * @param array $client Associative array with keys as column names and values as column new values
     * @return int The ID of new client (autoincrement)
     */
    public function insertClient($client) {
        
        $select = $this->select();
        
        //Sort rows by order_number DESCENDING and fetch one row from the top
        //with biggest 'order_number'
        $select->order('order_number DESC');

        $clientWithBiggestOrderNumber = $this->fetchRow($select);
        
        if ($clientWithBiggestOrderNumber instanceof Zend_Db_Table_Row){
            
            $client['order_number'] = $clientWithBiggestOrderNumber['order_number'] + 1;
        } else {
            //table was empty, we are inserting first member
            $client['order_number'] = 1;
            
        }
        
    //fetch order number for new member
        $id = $this->insert($client);

        return $id;
    }

    /*
     * @param int $id ID of client to delete
     */

    public function deleteClient($id) {

        //member who is going to be deleted
        $client = $this->getClientById($id);
        
        $this->update(array(
            //ovako se u zendu naglasava
            'order_number' => new Zend_Db_Expr('order_number - 1')
        ),
            'order_number > ' . $client['order_number']);
        
        $this->delete('id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of client to disable
     */
    public function disableClient($id) {

        $this->update(array(
            'status' => self::STATUS_DISABLED
                ), 'id = ' . $id);
    }

    /**
     * 
     * @param int $id ID of client to enable
     */
    public function enableClient($id) {

        $this->update(array(
            'status' => self::STATUS_ENABLED
                ), 'id = ' . $id);
    }

    public function updateOrderOfClients($sortedIds) {

        foreach ($sortedIds as $orderNumber => $id) {
            $this->update(array(
                'order_number' => $orderNumber + 1 //+1 because order_number starts from 1, not from 0
                    ), 'id = ' . $id);
        }
    }

}


