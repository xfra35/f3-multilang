<?php

class Tests {

    private $routes,$aliases;//original backup

	private $ml;

    function run($f3) {
        $test=new \Test;
        $f3->config('tests/cfg/app.ini');
        $this->routes=$f3->get('ROUTES');//backup
        $this->aliases=$f3->get('ALIASES');//backup
        $this->ml=new Multilang;
        //plugin configuration
        $test->expect(
            $this->ml->languages()==array('fr','it','de','en'),
            'Languages definition'
        );
        $test->expect(
            $this->ml->primary=='fr' && $f3->get('FALLBACK')=='fr-FR,fr',
            'Primary language and FALLBACK correctly set'

        );
        //language detection
        $browser='da-DK,it-IT,en';//simulate browser detection
        $f3->set('LANGUAGE',$browser);//reset browser language
        $this->simulate('/');
        $test->expect(
            $this->ml->auto && $this->ml->current=='it' && $f3->get('LANGUAGE')=='it-IT,it,fr-FR,fr',
            'Browser detected language'
        );
        $this->simulate('/en/something',$this->ml);
        $test->expect(
            !$this->ml->auto && $this->ml->current=='en' && $f3->get('LANGUAGE')=='en,en-GB,fr-FR,fr',
            'URL detected language'
        );
        $f3->set('LANGUAGE',$browser);//reset browser language
        $this->simulate('/da/noget');
        $test->expect(
            $this->ml->current=='it' && $f3->get('LANGUAGE')=='it-IT,it,fr-FR,fr',
            'Unknown language (fallback on browser detection)'
        );
        //routes rewriting
        $routes=&$f3->ref('ROUTES');
        $aliases=&$f3->ref('ALIASES');
        $this->simulate('/de/etwas');//german URL
        $test->expect(
            array_keys($routes)==array('/de','/de/legal','/de/faq','/de/blog/@slug','/resize/@format/@file','/') &&
                array_values($aliases)==array('/de/legal','/de/faq','/de/blog/@slug','/resize/@format/@file'),
            'ROUTES and ALIASES rewritten (auto)'
        );
        $this->simulate('/fr/quelque-chose');//french URL
        $test->expect(
            array_keys($routes)==array('/fr','/fr/mentions-legales','/fr/foire-aux-questions','/resize/@format/@file','/') &&
                array_values($aliases)==array('/fr/mentions-legales','/fr/foire-aux-questions','/resize/@format/@file'),
            'ROUTES and ALIASES rewritten (custom)'
        );
        $test->expect(
            !array_key_exists('/fr/resize/@format/@file',$routes) && array_key_exists('/resize/@format/@file',$routes) &&
                !in_array('/fr/resize/@format/@file',$aliases) && in_array('/resize/@format/@file',$aliases),
            'Global route not rewritten'
        );
        $test->expect(
            !array_key_exists('/fr/blog/@slug',$routes) && !array_key_exists('/blog/@slug',$routes) &&
                !in_array('/fr/blog/@slug',$aliases) && !in_array('/blog/@slug',$aliases),
            'Exclusions (excluded language)'
        );
        $this->simulate('/en/something');//english URL
        $test->expect(
            array_key_exists('/en/blog/@slug',$routes) && !array_key_exists('/blog/@slug',$routes) &&
                in_array('/en/blog/@slug',$aliases) && !in_array('/blog/@slug',$aliases),
            'Exclusions (allowed language)'
        );
        //routes testing
        $test->expect(
            $this->mock('/fr/foire-aux-questions')=='Bonjour faq' &&
                $this->mock('/it/legal')=='Ciao da Italia legal',
            'Rewritten routes executed'
        );
        $f3->set('LANGUAGE',$browser);//reset browser language
        $test->expect(
            $this->mock('/resize/120x80/foo.gif')=='Ciao da Italia foo.gif',
            'Global route executed'
        );
        $f3->set('ONREROUTE',function($url,$permanent=FALSE) use($f3){
            $f3->mock('GET '.$url);
        });
        $test->expect(
            $this->mock('/')=='Ciao da Italia home',
            'Default root handler (browser detection)'
        );
        $f3->set('MULTILANG.root',function($f3){
            echo Multilang::instance()->current.' detected';
        });
        $test->expect(
            $this->mock('/')=='it detected',
            'Custom root handler'
        );
		$f3->clear('MULTILANG.root');
        //alias function
        $this->simulate('/de/zehr-gut');//german URL
        $test->expect(
            $this->ml->alias('blogEntry','slug=hallo-welt')=='/de/blog/hallo-welt',
            'Alias function (current language)'
        );
        $test->expect(
            $this->ml->alias('blogEntry','slug=hello-world','en')=='/en/blog/hello-world',
            'Alias function (target language)'
        );
        $test->expect(
            $this->ml->alias('blogEntry','slug=bonjour','fr')===FALSE,
            'Alias function (ignored route)'
        );
        $test->expect(
            $this->ml->alias('resize','format=big,file=foo.gif')==='/resize/big/foo.gif' &&
                $this->ml->alias('resize','format=big,file=foo.gif','it')==='/resize/big/foo.gif',
            'Alias function (global route)'
        );
		//migration mode
        $f3->set('LANGUAGE',$browser);//reset browser language
		$f3->set('MULTILANG.migrate',TRUE);
        $f3->set('ONREROUTE',function($url,$permanent) use($f3){
            echo "rerouted to $url";
        });
		$test->expect(
			$this->mock('/faq')=='rerouted to /fr/foire-aux-questions',
			'Migration mode: old URIs redirected to primary URIs'
		);
		$test->expect(
			$this->mock('/')=='rerouted to /it',
			'Migration mode: root not redirected to primary URI (see MULTILANG.root)'
		);
		$f3->set('MULTILANG.migrate',FALSE);
        //rerouting
        $this->simulate('/de/zehr-gut');//german URL
        $f3->set('ONREROUTE',function($url,$permanent) use($f3){
            $f3->set('rerouted',$url);
        });
        $f3->clear('rerouted');
        $this->ml->reroute('@blogEntry(slug=hallo-welt)');
        $test->expect(
            $f3->get('rerouted')=='/de/blog/hallo-welt',
            'Reroute to a named rewritten route'
        );
        $f3->clear('rerouted');
        $this->ml->reroute('@resize(format=big,file=foo.gif)');
        $test->expect(
            $f3->get('rerouted')=='/resize/big/foo.gif',
            'Reroute to a named global route'
        );
		$ok=TRUE;
		$reroutes=array(
			NULL=>$f3->REALM,
			'/'=>'/de',
			'/blog/hallo-welt'=>'/de/blog/hallo-welt',
			'/de/blog/hallo-welt'=>'/de/blog/hallo-welt',
		);
		foreach($reroutes as $url=>$expected) {
			$f3->clear('rerouted');
			$this->ml->reroute($url);
			$ok=$ok && $f3->get('rerouted')==$expected;
		}
        $test->expect(
            $ok,
            'Reroute to any unnamed route (auto prefix)'
        );
        //helper functions
        $test->expect(
            $this->ml->isGlobal('resize') && !$this->ml->isGlobal('blogEntry'),
            'isGlobal()'
        );
        $test->expect(
            $this->ml->isLocalized('blogEntry') && !$this->ml->isLocalized('blogEntry','fr'),
            'isLocalized()'
        );
        $f3->set('results',$test->results());
    }

    private function mock($url) {
        $f3=Base::instance();
        $this->simulate($url);
        ob_start();
        $f3->mock('GET '.$url);
        return ob_get_clean();
    }

    /**
     * Simulate plugin behavior on a given URL
     * @param string $url
     */
    private function simulate($url) {
        $f3=\Base::instance();
        $_SERVER['REQUEST_URI']=\Base::instance()->get('BASE').$url;
        $f3->set('ROUTES',$this->routes);
        $f3->set('ALIASES',$this->aliases);
		$this->ml=new Multilang;
    }

    function afterRoute($f3) {
        $f3->set('active','Multilang');
        echo \Preview::instance()->render('tests.htm');
    }

}
