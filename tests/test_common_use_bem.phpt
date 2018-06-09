--TEST--
* BEM: common use #1
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
	'html_before' => '<!doctype html>',
	'content' => array(
		array(
			'block' => 'head', 
			'tag' => 'head',
			'content' => array(
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
			'block' => 'body',
			'tag' => 'body',
			'content' => array(
					array(
						'elem' => 'content', 
						'html' => '<h1>Page not found!</h1><p>Sorry...</p>'
					)
				)
			)
		)
	);

?>
--MATCH_BEM--
$block_elem == 'test1 elem1'
$block == 'test1'
$elem == 'elem1'
--PHP_BEM--
<?php /* empty */ ?>
--FILE--
<?php

$_SERVER['DOCUMENT_ROOT'] = substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6);

require $_SERVER['DOCUMENT_ROOT'].'/xphpt.php';

$config = array(
	'templates' => __FILE__.'t',
// 	'templates_cache' => $_SERVER['DOCUMENT_ROOT'].'/xphptpls-cache', // if many templates
	'apply_traversal_mode' => 'bem' // default mode
);

$bem_array = array(
	'block' => 'request',
	'req_uri' => ''
	);

// $GLOBALS['xphpt_debug'] = true;
$GLOBALS['xphpt_debug_apply_call_limit'] = 100;

print_r(applyCtx($bem_array, $config));

?>
--EXPECT--
<!doctype html><html class="page" xmlns="http://www.w3.org/1999/xhtml"><head class="head"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/><title>404 Not Found!</title></head><body class="body"><h1>Page not found!</h1><p>Sorry...</p></body></html>