--TEST--
* Build $ctx['_compiled_match'] #1
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';
	
	$ctx = array(
		'templates' => array(),
		'templates_cache' => './',
		'_parsed_templates' => array(
			2 => array(
				array('match' => array('$block == "head"', 'empty($mod)'), 'file' => 'head.phpt'),
				array('match' => array('$block == "footer"', 'empty($mod)'), 'file' => 'footer.phpt')
				),
			5 => array(
				array('match' => array('$block == "body"', 'empty($mod)', '$req_uri == "/about"', '(empty($_GET) && empty($_POST))', 'isset($_SESSION)'), 'file' => 'page-about.phpt')
			)
		)
	);
	
	$hash = md5(serialize(array($ctx['templates'], empty($ctx['exclude_templates'])?'':$ctx['exclude_templates'])));
	@unlink($ctx['templates_cache']."xphpt_match.$hash.php");
	
	xphpt_compile_match_func($ctx);
	var_export($ctx['_compiled_match']);
	echo "\n";
	
	print_r(file_get_contents($ctx['templates_cache']."xphpt_match.$hash.php"));
	echo "\n* evalute MATCH\n";
	
	// теперь выполним эту скомпиленную функцию
	$data = array('block' => 'footer', 'req_uri' => '/about');
	var_dump(call_user_func($ctx['_compiled_match'], $data, '', array(), $ctx, null, null));
	
--EXPECT--
'' . "\0" . 'lambda_1'
<?php
extract($xphpt_current); isset($_args) and extract($_args);
global $xphpt_default_ctx;
switch(empty($ctx['apply_traversal_mode']) ? $xphpt_default_ctx['apply_traversal_mode'] : $ctx['apply_traversal_mode']) {
case 'bem': case 'only_content':
	if(!isset($block)) $block = $xphpt_key;
	if(!isset($elem)) $elem = '';
	break;
}
$_expr_cache =& $ctx['_expr_cache'];
switch(isset($ctx['_matched_tpl_id']) ? $ctx['_matched_tpl_id'] : 1) {

/* ----- Priority 5 ----- */
case 1: /* page-about.phpt */
	$_expr_cache[0] = ($block == "body");
	$_expr_cache[1] = (empty($mod));
	$_expr_cache[2] = ($req_uri == "/about");
	$_expr_cache[3] = ((empty($_GET) && empty($_POST)));
	$_expr_cache[4] = (isset($_SESSION));
	if($_expr_cache[0] and $_expr_cache[1] and $_expr_cache[2] and $_expr_cache[3] and $_expr_cache[4]) { $ctx['_matched_tpl_id'] = 1; return $ctx['_parsed_templates'][5][0]; }

/* ----- Priority 2 ----- */
case 2: /* head.phpt */
	$_expr_cache[5] = ($block == "head");
	if($_expr_cache[5] and $_expr_cache[1]) { $ctx['_matched_tpl_id'] = 2; return $ctx['_parsed_templates'][2][0]; }
case 3: /* footer.phpt */
	$_expr_cache[6] = ($block == "footer");
	if($_expr_cache[6] and $_expr_cache[1]) { $ctx['_matched_tpl_id'] = 3; return $ctx['_parsed_templates'][2][1]; }
default: break;
}
return null;
* evalute MATCH
array(2) {
  ["match"]=>
  array(2) {
    [0]=>
    string(18) "$block == "footer""
    [1]=>
    string(11) "empty($mod)"
  }
  ["file"]=>
  string(11) "footer.phpt"
}