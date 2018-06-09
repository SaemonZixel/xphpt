--TEST--
* XSL-T: common use #1
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

--MATCH_BEM--
$block_elem == 'test1 elem1'
$block == 'test1'
$elem == 'elem1'
--PHP_BEM--
<?php /* empty */ ?>
--FILE--
<?php

require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';

$config = array(
	'templates' => __FILE__.'t',
	'apply_traversal_mode' => 'xslt'
);

$array = array(
	'block' => 'response',
	'blog_title' => 'My test blog',
	'blog_posts' =>array(
			array('ID' => 1, 'post_date' => '2018-01-01 09:00:00', 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'First post', 'post_content' => 'Text for first post...'),
			array('ID' => 2, 'post_date' => '2018-01-01 10:00:00', 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Second post', 'post_content' => 'Text for second post...'),
			array('ID' => 3, 'post_date' => '2018-01-01 10:00:00', 'post_type' => 'post', 'post_status' => 'draft', 'post_title' => 'Third post', 'post_content' => 'Text for third post... (draft)')
		)
	);
	
$params = array();
$mode = '';

// $GLOBALS['xphpt_debug'] = true;
$GLOBALS['xphpt_debug_apply_call_limit'] = 100;

print_r(apply_templates($array, $mode, $params, $config));
	
?>
--EXPECT--
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="Robots" content="index,follow" />
		<title>My test blog</title>
	</head>
	<body>
		<h1>My test blog</h1>
				<article id="post_1">
			<h2>First post</h2>
			<date>2018-01-01 09:00:00</date>
			<div>
				Text for first post...
			</div>
		</article>
		<article id="post_2">
			<h2>Second post</h2>
			<date>2018-01-01 10:00:00</date>
			<div>
				Text for second post...
			</div>
		</article>
	</body>
</html>