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
?>
--EXPECT--
<div class="test"><div class="test_ok"></div></div>