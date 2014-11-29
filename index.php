<?php
$f3=require('lib/base.php');
$f3->mset(array(
    'AUTOLOAD'=>'tests/',
    'UI'=>'tests/',
    'TEMP'=>'var/tmp/',
    'PREFIX'=>'DICT.',
    'DEBUG'=>3,
));
$f3->route('GET /','Tests->run');
$f3->run();
