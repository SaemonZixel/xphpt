# xphpt
eXtensible PHP Templates - template engine like XSL-T, but with BEM philosophy like XJST or BH

Stable version: [0.6](https://raw.githubusercontent.com/SaemonZixel/xphpt/master/xphpt.php)

Requires: PHP 5.2+

## Template example (BEM style)

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
```

## Common use case (BEM style)

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

## Template example (XSL-T style)

Contents of `404notfound.phpt` file:

```php
--DESCRIPTION--
Blog template
--MATCH--
$block == 'response'
--PHP--
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="Robots" content="index,follow" />
		<title><?php echo isset($blog_title) ? htmlspecialchars($blog_title) : 'My blog'; ?></title>
	</head>
	<body>
		<h1><?php echo isset($blog_title) ? htmlspecialchars($blog_title) : 'My blog'; ?></h1>
		<?php apply_templates($blog_posts); ?>
	</body>
</html>
--MATCH_BLOG_POST--
isset($post_type) and $post_type == 'post'
isset($post_status) and $post_status == 'publish'
--PHP_BLOG_POST--
		<article id="post_<?php echo $ID ?>">
			<h2><?php echo $post_title; ?></h2>
			<date><?php echo $post_date; ?></date>
			<div>
				<?php echo $post_content."\n"; ?>
			</div>
		</article>

```

## Common use case (XSL-T style)

Contents of `index.php` file:

```php
<?php

include 'xphpt.php';

$data = array(
	'block' => 'response',
	'blog_title' => 'My test blog',
	'blog_posts' =>array(
			array('ID' => 1, 'post_date' => '2018-01-01 09:00:00', 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'First post', 'post_content' => 'Text for first post...'),
			array('ID' => 2, 'post_date' => '2018-01-01 10:00:00', 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Second post', 'post_content' => 'Text for second post...'),
			array('ID' => 3, 'post_date' => '2018-01-01 10:00:00', 'post_type' => 'post', 'post_status' => 'draft', 'post_title' => 'Third post', 'post_content' => 'Text for third post... (draft)')
		)
	);

$mode = '';
$params = array();

$config = array(
	'templates' => $_SERVER['DOCUMENT_ROOT'].'/xphptpls',
	'templates_cache' => $_SERVER['DOCUMENT_ROOT'].'/xphptpls-cache', // if many templates
	'apply_traversal_mode' => 'xslt'
);

echo apply_templates($data, $mode, $params, $config);

exit;

?>
```

## Debugging

* Line number is preserved in error messages!
* You can set $GLOBALS['xphpt_debug'] = true to show additional info.
* You can set $GLOBALS['xphpt_debug_apply_call_limit'] = N for protect by infinity cyclec.