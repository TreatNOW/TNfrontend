<?php

class Dictionary {

    //internals
    private $db;
    private $code;
    private $keyFilter;
    private $items = array();

    /*****************************************/
    /* CONSTRUCTION                          */
    /*****************************************/
    function __Construct($code, $keyFilter = null) {
        $this->code = strtoupper($code);
        $this->keyFilter = $keyFilter;
        $this->db = self::GetPrimaryDb();
        $this->load();
    }

    /*****************************************/
    /* DATABASE HANDLERS                     */
    /*****************************************/
    private static function GetPrimaryDb() {
        return Database::GetInstance(Database::ZIDMI);
    }
    function load() {
        $sql = "SELECT 		item_key,
                            get_content_item(".Database::SqlString($this->code).", item_key) AS content
                FROM 		content_item
                WHERE		1 = 1 ";
        if (!is_null($this->keyFilter)) {
            $sql .= "AND    item_key LIKE '".$this->keyFilter."%' ";
        }
        foreach ($this->db->getResultset($sql) as $row) {
            $this->items[$row['item_key']] = $row['content'];
        }
    }

    /*****************************************/
    /* DOMAIN FUNCTIONS                      */
    /*****************************************/
    //returns a single value from the array
    function get($itemKey) {
        $value = null;
        if (array_key_exists($itemKey, $this->items)) {
            $value = $this->items[$itemKey];
        }
        return $value;
    }
    //returns all items matching the filter as an array
    function getList($filter, $trimKey = false) {
        $list = array();
        foreach ($this->items as $key => $value) {
            if (substr($key, 0, strlen($filter)) == $filter) {
                if ($trimKey) {
                    $key = substr($key, strlen($filter));
                }
                $list[$key] = $value;
            }
        }
        return $list;
    }

    /*****************************************/
    /* GETTERS AND SETTERS                   */
    /*****************************************/
    function getCode() {
        return $this->code;
    }

    /*****************************************/
    /* STATIC FUNCTIONS                      */
    /*****************************************/
    static function ValidLanguage($code) {
        $valid = false;
        switch ($code) {
            case 'en': case 'fr': case 'es':
            case 'de': case 'it': case 'nl':
                $valid = true;
        }
        return $valid;
    }

}
?>