--TEST--
* XSL-T: call_template() #2
--DESCRIPTION--
Test templates for call_template
--MATCH--
empty($block);
--PHP--
<?php

return array('block' => 'test_ok');

?>
--MATCH_ABC123--
$block == 'abc123'
--PHP_ABC123--
<?php

return array('block' => 'abc123');

?>
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';
	
	$ctx = array();
	$result = call_template('test_call_template2.phpt', $ctx);
	var_dump($result);
	
	$ctx = array();
	$result = call_template(array('test_call_template2.phpt', 'ABC123'), $ctx);
	var_dump($result);
	
?>
--EXPECT--
array(1) {
  ["block"]=>
  string(7) "test_ok"
}
array(1) {
  ["block"]=>
  string(6) "abc123"
}