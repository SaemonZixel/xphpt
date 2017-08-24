--TEST--
* XSL-T: call_template() #1
--DESCRIPTION--
Test template - phpinfo()
--MATCH--
$block == 'phpinfo'
--PHP--
<?php

return array('block' => 'test_ok');

?>
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';
	
	$ctx = array();
	$result = call_template('test_call_template1.phpt', $ctx);
	var_dump($result);
	
?>
--EXPECT--
array(1) {
  ["block"]=>
  string(7) "test_ok"
}