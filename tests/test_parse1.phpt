--TEST--
* Parse test #1
--DESCRIPTION--
Test template - phpinfo()
--MATCH--
$block == 'phpinfo'
--PHP--
<?php

phpinfo();

?>
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';
	
	file_put_contents('phpinfo.phpt', "--DESCRIPTION--\nTest template - phpinfo()\n--MATCH--\n\$block == 'phpinfo'\n--PHP--\n<?php\nphpinfo();\n?>");
	$ctx = array();
	xphpt_parse_phpt_file($ctx, 'phpinfo.phpt');
	var_dump($ctx);
	
	unlink('phpinfo.phpt');
	echo "\n\n";
	
	$ctx = array();
	xphpt_parse_phpt_file($ctx, 'test_parse1.phpt');
	var_dump($ctx);
	
?>
--EXPECT--
array(1) {
  ["_parsed_templates"]=>
  array(1) {
    [1]=>
    array(1) {
      [0]=>
      array(4) {
        ["file"]=>
        string(12) "phpinfo.phpt"
        ["tpl_suffix"]=>
        string(0) ""
        ["match"]=>
        array(1) {
          [0]=>
          string(19) "$block == 'phpinfo'"
        }
        ["php_code"]=>
        string(56) "/* 




 */ global $xphpt_current; ?><?php
phpinfo();
?>"
      }
    }
  }
}


array(1) {
  ["_parsed_templates"]=>
  array(1) {
    [1]=>
    array(1) {
      [0]=>
      array(4) {
        ["file"]=>
        string(16) "test_parse1.phpt"
        ["tpl_suffix"]=>
        string(0) ""
        ["match"]=>
        array(1) {
          [0]=>
          string(19) "$block == 'phpinfo'"
        }
        ["php_code"]=>
        string(60) "/* 






 */ global $xphpt_current; ?><?php

phpinfo();

?>"
      }
    }
  }
}