<?php

/**
 * Multilang: a localization plugin for the PHP Fat-Free Framework
 *
 * The contents of this file are subject to the terms of the GNU General
 * Public License Version 3.0. You may not use this file except in
 * compliance with the license. Any of the license terms and conditions
 * can be waived if you get permission from the copyright holder.
 *
 * @author xfra35 <xfra35@gmail.com>
 * @see https://github.com/xfra35/f3-multilang
 */
class Multilang extends \Prefab {

	//@{ Error messages
	const
		E_NoLang='Configuration error. No language has been defined!',
		E_Duplicate='Cannot rewrite: the URL %s is already in use!',
		E_Undefined='Undefined property: %s::$%s';
	//@}

	protected
		//! current language
		$current,
		//! primary language
		$primary,
		//! auto-detected language
		$auto=FALSE,
		//! migration mode
		$migrate=FALSE;

	protected
		//! available languages
		$languages=array(),
		//! original ALIASES
		$_aliases=array(),
		//! language-specific rules
		$rules=array(),
		//! aliases of global routes
		$global_aliases=array(),
		//! regex for global routes
		$global_regex=NULL;

	/** @var \Base */
	private $f3;

	/**
	 * Assemble url from alias name
	 * @param string $name
	 * @param array|string $params
	 * @param string $lang
	 * @return string|FALSE
	 */
	function alias($name,$params=NULL,$lang=NULL) {
		if (in_array($name,$this->global_aliases))
			return $this->f3->alias($name,$params);
		if (!is_array($params))
			$params=$this->f3->parse($params);
		if (!$lang)
			$lang=$this->current;
		if (isset($this->rules[$lang][$name]) && $this->rules[$lang][$name]===FALSE)
			return FALSE;
		$url=isset($this->rules[$lang][$name])?
			$this->rules[$lang][$name]:@$this->_aliases[$name];
		if (!$url)
			user_error(sprintf(\Base::E_Named,$name),E_USER_ERROR);
		return $this->f3->build(rtrim('/'.$lang.$url,'/'),$params);
	}

	/**
	 * Check if a route is global
	 * @param string $name
	 * @return bool
	 */
	function isGlobal($name) {
		return in_array($name,$this->global_aliases);
	}

	/**
	 * Check if a route is localized in a given language
	 * @param string $name
	 * @param string $lang
	 * @return bool
	 */
	function isLocalized($name,$lang=NULL) {
		if (!isset($lang))
			$lang=$this->current;
		return !$this->isGlobal($name) && array_key_exists($name,$this->_aliases) &&
				(!isset($this->rules[$lang][$name]) || $this->rules[$lang][$name]!==FALSE);
	}

	/**
	 * Return the current locale
	 * @return string
	 */
	function locale() {
		return setlocale(LC_NUMERIC,0);// LC_ALL does not always return a unique locale
	}

	/**
	 * Return the language name corresponding to the given ISO code
	 * NB: the name is localized if the intl extension is installed, otherwise it is returned in English
	 * @param string $iso
	 * @return string
	 */
	function displayLanguage($iso) {
		if (!$iso)
			return '';
		return class_exists('Locale')?\Locale::getDisplayLanguage($iso,$this->locale()):constant('ISO::LC_'.$iso);
	}

	/**
	 * Return the country name corresponding to the given ISO code
	 * NB: the name is localized if the intl extension is installed, otherwise it is returned in English
	 * @param string $iso
	 * @return string
	 */
	function displayCountry($iso) {
		if (!$iso)
			return '';
		return class_exists('Locale')?\Locale::getDisplayRegion('-'.$iso,$this->locale()):constant('ISO::CC_'.$iso);
	}

	/**
	 * Alias for displayLanguage [deprecated]
	 * @param string $iso
	 * @return string
	 */
	function display($iso) {
		return $this->displayLanguage($iso);
	}

	/**
	 * Return the list of available aliases
	 * @return array
	 */
	function aliases() {
		return array_keys($this->_aliases);
	}

	/**
	 * Return the list of available languages
	 * @return array
	 */
	function languages() {
		return array_keys($this->languages);
	}

	/**
	 * Language-aware reroute (autoprefix unnamed routes)
	 * @param string $url 
	 * @param bool $permanent 
	 * @return NULL
	 */
	function reroute($url=NULL,$permanent=FALSE) {
		if (preg_match('/^\/([^\/]*)/',$url,$m) && !array_key_exists($m[1],$this->languages))
			$url=rtrim('/'.$this->current.$url,'/');
		$this->f3->reroute($url,$permanent);
	}

	//! Detects the current language
	protected function detect($uri=NULL) {
		$this->current=$this->primary;
		if (preg_match('/^'.preg_quote($this->f3->get('BASE'),'/').'\/([^\/?]+)([\/?]|$)/',$uri?:$_SERVER['REQUEST_URI'],$m) &&
				array_key_exists($m[1],$this->languages))
			$this->current=$m[1];
		else {//auto-detect language
			$this->auto=TRUE;
			$detected=array_intersect(explode(',',$this->f3->get('LANGUAGE')),explode(',',implode(',',$this->languages)));
			if ($detected=reset($detected))
				foreach($this->languages as $lang=>$locales)
					if (in_array($detected,explode(',',$locales))) {
						$this->current=$lang;
						break;
					}
		}
		$this->f3->set('LANGUAGE',$this->languages[$this->current]);
	}

	//! Rewrite ROUTES and ALIASES
	protected function rewrite() {
		$routes=array();
		$aliases=&$this->f3->ref('ALIASES');
		$redirects=array();
		foreach($this->f3->get('ROUTES') as $old=>$data) {
			$route=current(current($data));//let's pick up any route just to get the URL name
			$name=@$route[3];//PHP 5.3 compatibility
			$new=$old;
			if (!($name && in_array($name,$this->global_aliases)
				|| isset($this->global_regex) && preg_match($this->global_regex,$old))) {
				if (isset($this->rules[$this->current][$name])) {
					$new=$this->rules[$this->current][$name];
					if ($new===FALSE) {
						if (isset($aliases[$name]))
							unset($aliases[$name]);
						continue;
					}
				}
				$new=rtrim('/'.$this->current.($new),'/');
				if ($this->migrate && $this->auto) {
					$redir=$old;
					if (isset($this->rules[$this->primary][$name]))
						$redir=$this->rules[$this->primary][$name];
					if ($redir!==FALSE)
						$redirects[$old]=rtrim('/'.$this->primary.($redir),'/');
				}
			}
			if (isset($routes[$new]))
				user_error(sprintf(self::E_Duplicate,$new),E_USER_ERROR);
			$routes[$new]=$data;
			if (isset($aliases[$name]))
				$aliases[$name]=$new;
		}
		$this->f3->set('ROUTES',$routes);
		foreach($redirects as $old=>$new)
			$this->f3->route('GET '.$old,function($f3)use($new){$f3->reroute($new,TRUE);});
	}

	//! Read-only public properties
	function __get($name) {
		if (in_array($name,array('current','primary','auto')))
			return $this->$name;
		trigger_error(sprintf(self::E_Undefined,__CLASS__,$name));
	}

	//! Bootstrap
	function __construct() {
		$this->f3=\Base::instance();
		$config=$this->f3->get('MULTILANG');
		//languages definition
		if (!isset($config['languages']) || !$config['languages'])
			user_error(self::E_NoLang,E_USER_ERROR);
		foreach($config['languages'] as $lang=>$locales) {
			if (is_array($locales))
				$locales=implode(',',$locales);
			if (!$this->languages) {
				$this->f3->set('FALLBACK',$locales);
				$this->primary=$lang;
			}
			$this->languages[$lang]=$locales;
			$this->rules[$lang]=array();
		}
		//aliases definition
		$this->_aliases=$this->f3->get('ALIASES');
		if (is_array(@$config['rules']))
			foreach($config['rules'] as $lang=>$aliases)
				$this->rules[$lang]=$aliases;
		//global routes
		if (isset($config['global'])) {
			if (!is_array($config['global']))
				$config['global']=array($config['global']);
			$prefixes=array();
			foreach($config['global'] as $global)
				if (@$global[0]=='/')
					$prefixes[]=$global;
				else
					$this->global_aliases[]=$global;
			if ($prefixes)
				$this->global_regex='#^('.implode('|',array_map('preg_quote',$prefixes)).')#';
		}
		//migration mode
		$this->migrate=(bool)@$config['migrate'];
		//detect current language
		$this->detect();
		//rewrite existing routes
		$this->rewrite();
		//root handler
		$self=$this;//PHP 5.3 compatibility
		$this->f3->route('GET /',@$config['root']?:function($f3) use($self){$f3->reroute('/'.$self->current);});
	}

}
