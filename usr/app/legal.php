<?php
namespace App;
class Legal {

    function get($f3) {
        $f3->set('LOCALES',__DIR__.'/dict/');
        echo $f3->get('DICT.bonjour').' legal';
    }

}