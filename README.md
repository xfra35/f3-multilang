# Multilang
**…for F3 polyglots!**

This plugin for [Fat-Free Framework](http://github.com/bcosca/fatfree) provides a URL-friendly mean to localize your web site/app.

* [Basic usage](#basic-usage)
* [Root URL](#root-url)
* [Advanced usage](#advanced-usage)
    * [Rewrite URLs](#rewrite-urls)
    * [Exclude a language from a route](#exclude-a-language-from-a-route)
    * [Global routes](#global-routes)
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
So if you're Spanish, you'll be redirected to `/es` but if you're Russian, you'll be redirected to `/en` (fallback to the primary language).

You can override the default handler by setting the `MULTILANG.root` to a custom handler:
```ini
[MULTILANG]
root = App\MyRoot
```

Use case: display a splash page with the list of available languages.


## Advanced usage
(requires the usage of [route aliases](http://fatfreeframework.com/routing-engine#named-routes))

### Rewrite URLs
A translated URL consist of a language identifier followed by the original URL:
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
It could also occur that some parts won't ever be localized (for example a blog).

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
global = alias1, alias2, alias3
```

**NB:** on a global route, the language is auto-detected by default.
So in the case of a monolingual back office, you may need to force the language at the controller level.

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

This function is a language-aware version of the `$f3->alias()` function.

```php
echo $ml->alias('terms',NULL,'es');// /es/terminos-y-condiciones [local route]
echo $ml->alias('captcha');// /captcha [global route]
```

## Potential improvements

* Allow domain level recognition (mydomain.jp/es, or jp/es.mydomain.com)
* Hook on "run" event if an event system is implemented on F3 [core](https://github.com/bcosca/fatfree-core).

