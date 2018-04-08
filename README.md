# xphpt
eXtensible PHP Templates - template engine like XSL-T, but with BEM philosophy like XJST or BH

Stable version: [0.5.2](https://raw.githubusercontent.com/SaemonZixel/xphpt/master/xphpt.php)

Requires: PHP 5.2+

## Template example

Contents of `404notfound.phpt` file:

```php
--DESCRIPTION--
Page 404 Not found!
--MATCH--
$block == 'request'
--PHP--
<?php

return array(
	'block' => 'page',
	'tag' => 'html',
	'attrs' => array('xmlns' => 'http://www.w3.org/1999/xhtml'),
	'before_html' => '<!doctype html>',
	'content' => array(
		array(
			'block' => 'head', 
			'tag' => 'head',
			'contents' => array(
					array(
						'tag' => 'meta',
						'attrs' => array(
							'http-equiv' => 'Content-Type',  
							'content' => 'text/html; charset=UTF-8')
						),
					array(
						'elem' => 'title', 
						'tag' => 'title', 
						'attrs' => array('class' => null), // removes the class attribute
						'content' => array('404 Not Found!')
						)
				)
			),
		array(
			'block' => 'body'
			'tag' => 'body',
			'contents' => array(
					array(
						'elem' => 'content', 
						'html' => '<h1>Page not found!</h1><p>Sorry...</p>'
					)
				)
			)
		)
	);

?>
```

## Common use case

Contents of `index.php` file:

```php
<?php

include 'xphpt.php';

$config = array(
	'templates' => $_SERVER['DOCUMENT_ROOT'].'/xphptpls',
	'templates_cache' => $_SERVER['DOCUMENT_ROOT'].'/xphptpls-cache', // if many templates
	'apply_traversal_mode' => 'bem' // default mode
);

$bem_array = array(
	'block' => 'request',
	'req_uri' => $_SERVER['REQUEST_URI']
	);

echo applyCtx($bem_array, $config);

exit;

?>
```

## Debugging

* Line number is preserved in error messages!
* You can set $GLOBALS['xphpt_debug'] = true to show additional info.
* You can set $GLOBALS['xphpt_debug_apply_call_limit'] = N for protect by infinity cyclec.