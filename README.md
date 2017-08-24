# xphpt
eXtensible PHP Templates - template engine like XSL-T, but with BEM philosophy like XJST or BH

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
	'contents' => array(
		array(
			'block' => 'head', 
			'tag' => 'head',
			'contents' => array(
					array('block' => 'title', 'tag' => 'title', 'contents' => array('404 Not Found!'))
				)
			),
		array(
			'block' => 'body'
			'tag' => 'body',
				'contents' => array(
					array('block' => 'content', 'html' => '<h1>Page not found!</h1><p>Sorry...</p>')
				)
			)
		),
	),
	'before_html' => '<!doctype html>',
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

print_r(applyCtx($bem_array, $config));

exit;

?>
```