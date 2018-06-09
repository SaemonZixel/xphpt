--TEST--
* BEM: content(new_value)
--MATCH--
$block_elem == 'test '
empty($elem)
--PHP--
<?php

content(array(
	array('elem' => 'item1'),
	array('elem' => 'item2')
	));

?>
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';
	
// 	$GLOBALS['xphpt_debug'] = true;
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
--EXPECT--
<div class="test"><div class="test__item1"></div><div class="test__item2"></div></div>