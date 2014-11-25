<?php
$f3=require('lib/base.php');
$f3->mset(array(
    'AUTOLOAD'=>'usr/',
    'UI'=>'usr/',
    'TEMP'=>'usr/tmp/',
    'PREFIX'=>'DICT.',
    'DEBUG'=>3,
));
/*$f3->config('cfg/routes-all.ini');

$f3->route('GET @home: /',function($f3){
    echo 'this is home';
    echo '<br>'.print_r($f3->ALIASES,true);
});

$f3->route('GET @contact: /contact',function($f3){
    echo 'contact form';
});*/

//$f3->multilang=new Multilang();
/*$ml=Multilang::instance();
$ml->config('cfg/multilang.ini');
//$ml->set('de','de-DE');
//$ml->set('dk','dk,dk_DA');
$ml->detect();
$ml->rewrite();
foreach(array_keys($ml->available) as $lang)
    echo sprintf('<hr>contact [%s]: %s<hr>',$lang,$ml->alias('contact',NULL,$lang));
echo '<pre>'.print_r($f3->ROUTES,true).'</pre>';die();
*/

$f3->route('GET /','Tests->run');
$f3->run();
