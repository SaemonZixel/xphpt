--TEST--
* BEM: toHtml() test #1
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';
	
	global $xphpt_default_ctx;
	$xphpt_default_ctx['delimElem'] = '__';
	$xphpt_default_ctx['delimMod'] = '_';
	
// $GLOBALS['xphpt_debug'] = true;
	print_r(
		toHtml(
			array(
				'block' => 'test',
				'content' => array(
					array('elem' => 'item', 'content' => array('123'))
				)
			)
		)
	);
	
	echo "\n------------------\n";
	print_r(
		toHtml(
			array(
				'block' => 'test',
				'tag' => 'a',
				'attrs' => array('href' => 'http://yandex.ru', 'target' => '_blank'),
				'before_html' => '<!-- link -->',
				'after_html' => '<!-- /link -->',
				'content' => array(
					array(
						'elem' => 'item', 
						'tag' => 'img',
						'attrs' => array('class' => null, 'alt' => '', 'src' => 'about:blank'),
						'content' => array('123'))
				)
			)
		)
	);
?>
--EXPECT--
<div class="test"><div class="test__item">123</div></div>
------------------
<!-- link --><a class="test" href="http://yandex.ru" target="_blank"><img alt="" src="about:blank"/></a><!-- /link -->