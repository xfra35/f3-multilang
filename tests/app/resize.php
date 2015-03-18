<?php
namespace App;
class Resize {

	function get($f3,$params) {
		$f3->set('LOCALES',__DIR__.'/dict/');
		echo $f3->get('DICT.bonjour').' '.$params['file'];
	}

}
