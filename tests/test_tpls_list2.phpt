--TEST--
* Build $tpls_files (array)
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';

// 	var_dump('* $xphpt_maximum_deep_of_subdirectories = 3');
	
	@mkdir('./dir1/dir2', 0755, true);
	file_put_contents('./dir1/dir2/test1.phpt', ' ');
	
	@mkdir('./dir2', 0755, true);
	file_put_contents('./dir2/test2.phpt', ' ');
	
	$ctx = array(
		'templates' => array('./dir1/dir2', './dir2'),
		'test_mode.return_tpls_files_only' => true
	);
	$tpls_files = xphpt_parse_templates($ctx);
	foreach($tpls_files as $file)
	if(strpos($file, 'test_tpls_list1.phpt') or strpos($file, 'test_match1.phpt') or strpos($file, 'test_parse1.phpt') or strpos($file, '/test1.phpt') or strpos($file, '/test2.phpt'))
		var_dump(strstr($file, "tests/"));
	
--EXPECT--
string(26) "tests/dir1/dir2/test1.phpt"
string(21) "tests/dir2/test2.phpt"