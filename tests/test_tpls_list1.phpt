--TEST--
* Build $tpls_files
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';

	var_dump('* $xphpt_maximum_deep_of_subdirectories = 3');
	
	@mkdir('./dir1/dir2', 0755, true);
	file_put_contents('./dir1/dir2/test1.phpt', ' ');
	
	$ctx = array(
		'templates' => '.', 
		'maximum_deep_of_subdirectories' => 3,
		'test_mode.return_tpls_files_only' => true
	);
	$tpls_files = xphpt_parse_templates($ctx);
	foreach($tpls_files as $file)
	if(strpos($file, 'test_tpls_list1.phpt') or strpos($file, 'test_match1.phpt') or strpos($file, 'test_parse1.phpt') or strpos($file, '/test1.phpt'))
		var_dump(strstr($file, "tests/"));
	
	$ctx['maximum_deep_of_subdirectories'] = 2;
	var_dump('* $xphpt_maximum_deep_of_subdirectories = 2');
	$tpls_files = xphpt_parse_templates($ctx);
	foreach($tpls_files as $file)
	if(strpos($file, 'test_tpls_list1.phpt') or strpos($file, 'test_match1.phpt') or strpos($file, 'test_parse1.phpt') or strpos($file, '/test1.phpt'))
		var_dump(strstr($file, "tests/"));
	
--EXPECT--
string(43) "* $xphpt_maximum_deep_of_subdirectories = 3"
string(26) "tests/test_tpls_list1.phpt"
string(22) "tests/test_match1.phpt"
string(22) "tests/test_parse1.phpt"
string(26) "tests/dir1/dir2/test1.phpt"
string(43) "* $xphpt_maximum_deep_of_subdirectories = 2"
string(26) "tests/test_tpls_list1.phpt"
string(22) "tests/test_match1.phpt"
string(22) "tests/test_parse1.phpt"