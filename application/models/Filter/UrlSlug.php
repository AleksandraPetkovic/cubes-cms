<?php

class Application_Model_Filter_UrlSlug implements Zend_Filter_Interface
{
    public function filter($value) {
        //^(tilda) zameni sve sem toga sto sam navela 
        //sve sto nije slovo ili broj zameni sa - (crticom)
        //p{L} za sva cirilicna slova nemacka umalute
        //p{N} za sve brojeve 
        $value = preg_replace('/[^\p{L}\p{N}]/u', '-', $value);
        //1 ili vise spejsova sa -
        $value = preg_replace('/(\s+)/', '-', $value);
        //jedna ili vise crtica da se zameni jednom
        $value = preg_replace('/(\-+)/', '-', $value);
        $value = trim($value, '-');
       return $value; 
    }

}

