# Multilang
**…for F3 polyglots!**

This plugin for [Fat-Free Framework](http://github.com/bcosca/fatfree) provides a URL-friendly mean to localize your web site/app.

Demo: [here](http://ml.aesx.fr).

* [Basic usage](#basic-usage)
* [Root URL](#root-url)
* [Advanced usage](#advanced-usage)
    * [Rewrite URLs](#rewrite-urls)
    * [Exclude a language from a route](#exclude-a-language-from-a-route)
    * [Global routes](#global-routes)
* [Rerouting](#rerouting)
* [Migration mode](#migration-mode)
* [API](#api)
* [Potential improvements](#potential-improvements)

## Basic usage

### Step 1:
Declare the languages of your app in the `MULTILANG.languages` variable:
```php
$f3->set('MULTILANG.languages',array(
  'en' => 'en-GB,en-US,en',
  'ja' => 'ja-JP,ja',
  'es' => 'es-ES,es'
));
```
The same declaration can be achieved in a configuration file using the following syntax:
```ini
[MULTILANG.languages]
en = en-GB, en-US, en
ja = ja-JP, ja
es = es-ES, es
```

**NB1:** each entry maps a language identifier (`en`, `ja`, `es`) to one or more locales. The language identifiers are arbitrary (`english`, `en-GB`, `japan`, etc) but remember: they will appear in your URLs.

**NB2:** The first defined language is considered as the primary language, which means it is set as [FALLBACK](http://fatfreeframework.com/quick-reference#FALLBACK). In our example, on a japanese page, the locales and dictionaries would be searched in the following order: ja-JP, ja, en-GB, en, en-US.

**NB3:** It is strongly advised to include a country-independant language code (`en`, `ja`, `es`, etc...) in the list of locales for a better browser language detection.

### Step 2:
Start the plugin by instantiating the class, **just before** the call to `$f3->run`:
```php
$f3->config('config.ini');
Multilang::instance();
$f3->run();
```

### That's it!
Now every existing URL has been duplicated into as many languages you've declared, using identifiers as prefixes:
```
         => /en/contact
/contact => /ja/contact
         => /es/contact
```
* How about the original URLs? => They have been removed.
* How about the original root `/`? => It autodetects the browser language. [See below](#root-url)

## Root URL

By default, the root URL autodetects the browser language and performs a redirection to its root page.

In our example, a Spanish user would be redirected to `/es` while a Russian user would be redirected to `/en` (fallback to the primary language).

You can override this default behaviour by setting the `MULTILANG.root` to a custom handler:
```ini
[MULTILANG]
root = App\MyRoot
```

Use case: display a splash page with the list of available languages.


## Advanced usage
(requires the usage of [route aliases](http://fatfreeframework.com/routing-engine#named-routes))

### Rewrite URLs
Each translated URL consists of a language identifier followed by the original URL:
```
/es + /terms-and-conditions = /es/terms-and-conditions
```
You can customize the second part by setting the `MULTILANG.rules` variable.
For example, in order to translate the Spanish URL above, you could write (assuming the route is named `terms`):
```php
$f3->set('MULTILANG.rules',array(
  'es' => array(
    'terms' => '/terminos-y-condiciones'
  )
));
```
The same declaration can be achieved in a configuration file using the following syntax:
```ini
[MULTILANG.rules.es]
terms = /terminos-y-condiciones
```

### Exclude a language from a route

When translating a website, you may need to perform a progressive translation, route by route.
It could also occur that some parts won't be localized at all (for example a blog).

For this purpose, you can remove a route for a specific language by setting it to `FALSE`:
```ini
[MULTILANG.rules.es]
blog = FALSE
```
A request to `/es/blog` will return a 404 error.

### Global routes

Some routes have to stay language-independant, for example a captcha doesn't have to be localized.
Also back offices often happen to be monolingual.

Those global routes are not rewritten: they keep their original URL.
They are defined using the `MULTILANG.global` variable:
```ini
[MULTILANG]
global = captcha
;could also be an array:
global = alias1, alias2, alias3, /admin, /foo/bar
```

Each entry can be a route alias or a URI prefix.

**NB:** on a global route, the language is auto-detected by default.
So in the case of a monolingual back office, you may need to force the language at the controller level.

## Rerouting

### If you're using named routes...

`$f3->reroute` will work as expected, that is to say, it will reroute to the
current language URL of the provided named route. E.g:

```php
$f3->reroute('@contact'); // OK => reroute to /xx/contact where xx is the current language
```

### If you're using unnamed routes...

In that case, you have to provide a language-prefixed URL to `$f3->reroute`:

```php
$f3->reroute('/en/contact'); // OK
$f3->reroute('/contact'); // Error => 404 Not Found
```

If you'd prefer to give the short URL to the framework and have it automatically prefix the URL with the current language,
use the `$ml->reroute` method provided by the plugin:

```php
$ml->reroute('/en/contact'); // OK
$ml->reroute('/contact'); // OK => reroute to /xx/contact where xx is the current language
```

In the situation where you'd like to quickly localize an existing project with unnamed routes, and would prefer to avoid
having to rewrite every `$f3->reroute` into `$ml->reroute`, you can simply use the framework's `ONREROUTE` hook:

```php
$f3->set('ONREROUTE',function($url,$permanent) use($f3,$ml){
  $f3->clear('ONREROUTE');
  $ml->reroute($url,$permanent);
});

// then in your controller, existing reroutes will keep on working:
$f3->reroute('/contact'); // OK => reroute to /xx/contact where xx is the current language
```

## Migration mode

When translating an existing monolingual site, it is often interesting to redirect the old monolingual URIs to the new multilingual ones.
The plugin does it automatically for you if you set `MULTILANG.migrate` to `TRUE`.

Example:

* when migration mode is disabled, `/contact` throws a 404 error
* when migration mode is enabled, `/contact` performs a 301 redirection to `/en/contact` (the primary language)

## API

```php
$ml = Multilang::instance();
```

### current

**Return the language detected for the current URL**

```php
echo $ml->current;// ja
```

### primary

**Return the name of the primary language**

```php
echo $ml->primary;// en
```

### auto

**TRUE if language has been auto-detected**

```php
echo $ml->auto;//FALSE
```


### locale()

**Return the currently selected locale**

NB: the value returned by this function can be different from what you're expecting
if the locales configured in `MULTILANG.languages` are not present on your system.

```php
echo $ml->locale();// en_GB.UTF-8
```

### display( $iso )

**Return the language name corresponding to the given ISO code**

NB: the name is localized if the intl extension is installed, otherwise it is returned in English.

```php
// on a Danish route (with intl)
echo $ml->display('fr');// fransk

// on a Russian route (with intl)
echo $ml->display('fr');// французский

// on any route (without intl)
echo $ml->display('fr');// French
```

### languages()

**Return the list of available languages**

```php
$ml->languages();// array('en','ja','es')
```

### aliases()

**Return the list of all aliases**

(even those not available for the current language)

```php
$ml->aliases();// array('terms','blog','captcha')
```

### isLocalized( $name, $lang=NULL )

**Check if a route is localized in a given language (default=current)**

(localized = not global nor excluded)

```php
$ml->isLocalized('terms');// TRUE (current language)
$ml->isLocalized('terms','es');// TRUE
$ml->isLocalized('blog','es');// FALSE (excluded for Spanish)
$ml->isLocalized('captcha');// FALSE (global)
$ml->isLocalized('foo');// FALSE (non-existent route)
```

### isGlobal( $name )

**Check if a route is global**

```php
$ml->isGlobal('captcha');// TRUE
```

### alias( $name, $params=NULL, $lang=NULL )

**Assemble url from alias name**

This function is a language-aware version of `$f3->alias()`.

```php
echo $ml->alias('terms',NULL,'es');// /es/terminos-y-condiciones [local route]
echo $ml->alias('captcha');// /captcha [global route]
```

### reroute( $url=NULL, $permanent=FALSE )

**Reroute to specified URI**

This function is a language-aware version of `$f3->reroute()`.

Use it if you want an automatic language prefix on **unnamed** routes. Cf. [rerouting](#rerouting).

```php
$ml->reroute('/en/contact'); // OK
$ml->reroute('/contact'); // OK => reroute to /xx/contact where xx is the current language
```

## Potential improvements

* Allow domain level recognition (mydomain.jp/es, or jp/es.mydomain.com)
* Hook on "run" event if an event system is implemented in F3 [core](https://github.com/bcosca/fatfree-core).

