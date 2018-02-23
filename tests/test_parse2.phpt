--TEST--
* Parse test #2
--DESCRIPTION--
Test templates for parsing
--MATCH--
empty($block)
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
	xphpt_parse_phpt_file($ctx, 'test_parse2.phpt');
	var_dump($ctx);
?>
--EXPECT--
array(1) {
  ["_parsed_templates"]=>
  array(1) {
    [1]=>
    array(2) {
      [0]=>
      array(4) {
        ["file"]=>
        string(16) "test_parse2.phpt"
        ["tpl_suffix"]=>
        bool(false)
        ["match"]=>
        array(1) {
          [0]=>
          string(13) "empty($block)"
        }
        ["php_code"]=>
        string(62) "/* 






 */ ?><?php

return array('block' => 'test_ok');

?>"
      }
      [1]=>
      array(4) {
        ["file"]=>
        string(16) "test_parse2.phpt"
        ["tpl_suffix"]=>
        string(6) "ABC123"
        ["match"]=>
        array(1) {
          [0]=>
          string(18) "$block == 'abc123'"
        }
        ["php_code"]=>
        string(69) "/* 














 */ ?><?php

return array('block' => 'abc123');

?>"
      }
    }
  }
}