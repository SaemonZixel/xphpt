--TEST--
* BEM: inheritance block name test #1
--DESCRIPTION--
Test templates for parsing
--MATCH--
$block == 'test'
$elem == 'item'
--PHP--
<?php

return array('block' => 'test_ok');

?>
--MATCH_ITEM_INNER--
$block == 'test'
$elem == 'item-inner'
--PHP_ITEM_INNER--
<?php

return array('elem' => 'test_inner_ok', 'content' => array(array('elem' => 'item-title', 'tag' => 'h1')));

?>
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';
	
// $GLOBALS['xphpt_debug'] = true;
	$ctx = array('templates' => __FILE__.'t', 'maximum_deep_of_subdirectories' => 1);
	print_r(
		applyCtx(
			array(
				'block' => 'test',
				'content' => array(
					array('elem' => 'item')
				)
			),
			$ctx
		)
	);
	
	echo "\n-----\n";
// $GLOBALS['xphpt_debug'] = true;
	print_r(
		applyCtx(
			array(
				'block' => 'test',
				'content' => array(
					array('elem' => 'item-inner')
				)
			),
			$ctx
		)
	);
	
	echo "\n-----\n";
// $GLOBALS['xphpt_debug'] = true;
	print_r(
		applyCtx(
			array(
				'block' => 'test',
				'content' => array(
					array(
						'tag' => 'header', 
						'content' => array(
							array('elem' => 'item-inner')
						)
					)
				)
			),
			$ctx
		)
	);
?>
--EXPECT--
<div class="test"><div class="test_ok"></div></div>
-----
<div class="test"><div class="test__test_inner_ok"><h1 class="test__item-title"></h1></div></div>
-----
<div class="test"><header><div class="test__test_inner_ok"><h1 class="test__item-title"></h1></div></header></div>