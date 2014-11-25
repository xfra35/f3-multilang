<?php

function getRoutes($nb,$fix) {
    $routes=array();
    for($i=0;$i<$nb/3;$i++)
        $routes+=array(
            $fix?str_replace('@',"\x00".'@',"/test-$i"):"/test-$i"=>array(3=>array('GET'=>array('foo'.$i,0,0,'alias'))),
            $fix?str_replace('@',"\x00".'@',"/test-$i/2014"):"/test-$i/2014"=>array(3=>array('GET'=>array('bar'.$i,0,0,'alias'))),
            $fix?str_replace('@',"\x00".'@',"/test-$i/@year"):"/test-$i/@year"=>array(3=>array('GET'=>array('baz'.$i,0,0,'alias'))),
        );
    return $routes;
}

function benchmark($nb,$type) {
    $t1=microtime(true);
    $m1=memory_get_usage();
    $routes=getRoutes($nb,$type=='krsort');
    if ($type=='krsort')
        krsort($routes);
    elseif ($type=='uksort')
        uksort($routes,function($k1,$k2){return str_replace('@',"\x00".'@',$k1)<str_replace('@',"\x00".'@',$k2);});
    elseif ($type=='multisort') {
        //$keys=array_keys($routes);
        //$values=array_values($routes);
        //$ref=array_map(function($str){return str_replace('@',"\x00".'@',$str);},$keys=array_keys($routes));
        $ref=array();
        foreach($keys=array_keys($routes) as $k)
            $ref[]=str_replace('@',"\x00",$k);
        array_multisort($ref,SORT_DESC,$keys,$values=array_values($routes));
        $routes=array_combine($keys,$values);
    }
    return array(microtime(true)-$t1,memory_get_usage()-$m1);
}

$nb_runs=1000;
$nb_routes=120;
foreach(array('krsort','uksort','multisort') as $type) {
    $spent=0;
    $mem=0;
    for($j=0;$j<$nb_runs;$j++) {
        $res=benchmark($nb_routes,$type);
        $spent+=$res[0];
        $mem+=$res[1];
    }
    echo sprintf('%s x %s: %s routes processed in %.2f ms using %d kB of RAM<br>',$nb_runs,$type,$nb_routes,1000*$spent/$nb_runs,$mem/(1024*$nb_runs));
}
