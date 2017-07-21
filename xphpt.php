<?php

/**
 *  XPHPT - eXtansable PHP Templates
 *  
 *  @version	0.1
 *  @author   Saemon Zixel <saemonzixel@gmail.com>
 *  @link     https://github.com/SaemonZixel/unipath
 *
 *  @license  MIT
 *
 *  Copyright (c) 2017 Saemon Zixel
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software *  and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

global $xphpt_file_cache_dir, $xphpt_apply_traversal_mode;
$xphpt_file_cache_dir = null; // null - use eval() for templates, '...' - generate php scripts for templates in this directory
$xphpt_apply_traversal_mode = 'only_arrays'; // only_content, only_arrays, all


function xphpt_find_files($dir) {
	if (($o = opendir($dir)) === false) { 
		trigger_error("cannot open directory: $dir", E_USER_NOTICE);
		return array();
	}

	$result = array();
	while (($name = readdir($o)) !== false) {

		if (is_dir("{$dir}/{$name}") && !in_array($name, array('.', '..', '.svn'))) {
			$result = array_merge($result, xphpt_find_files("{$dir}/{$name}"));
		}

		if (substr($name, -5) == '.phpt') {
			$testfile = realpath("{$dir}/{$name}");
			$result[] = $testfile;
		}
	}

	closedir($o);
	
	return $result;
}

function xphpt_parse_phpt_file($file) {
	global $xphpt_file_cache_dir;

	if (($fp = fopen($file, "rb")) == false) {
		trigger_error("Cannot open PHPT-file: $file", E_USER_NOTICE);
		return array();
	}

	if(feof($fp)) {
		trigger_error("Empty PHPT-file [$file]", E_USER_NOTICE);
		return array();
	}

	if (!empty($xphpt_file_cache_dir)) {
		$write_to_this_phpfile = rtrim($xphpt_file_cache_dir, "\\/")."/".rtrim(basename($file), 't');
		if(file_exists($write_to_this_phpfile)) 
			file_put_contents($write_to_this_phpfile, '');
	}
	
	$section = null;
	$secfile = false;
	$secdone = false;
	$section_text = array();
	
	while (!feof($fp)) {
		$line = fgets($fp);

		if ($line === false and is_null($section)) {
			$error_info = "Cannot read PHPT-file";
		}
		elseif ($line === false) {
			break;
		}
		
		if (!empty($write_to_this_phpfile)) {
			file_put_contents($write_to_this_phpfile, 
				$section == 'PHP' && strncmp($section, '--', 2) !== 0 ? $line : "\n",
				FILE_APPEND
			);
		}
		
		// Match the beginning of a section.
		if (preg_match('/^--([_A-Z0-9]+)--/', $line, $r)) {
			$section = $r[1];
			settype($section, 'string');

			if (isset($section_text[$section])) {
				$error_info = "Duplicated $section section";
			}

			$section_text[$section] = '';
			$secfile = $section == 'FILE' || $section == 'FILEEOF' || $section == 'FILE_EXTERNAL';
			$secdone = false;
			continue;
		}

		// Add to the section text.
		if (!$secdone and !empty($section)) {
			$section_text[$section] .= $line;
		}

		// End of actual test?
		if ($secfile && preg_match('/^===DONE===\s*$/', $line)) {
			$secdone = true;
		}
		
	}
	
	fclose($fp);
	
	if(!empty($error_info)) 
		trigger_error($error_info, E_USER_NOTICE);
		
	return $section_text;
}

function xphpt_compile_templates($dirs = '.', $cache_dir = null) {
	global $xphpt_file_cache_dir, $xphpt_files_sections;
	if(!empty($cache_dir)) {
//		$xphpt_file_cache_dir = realpath($cache_dir);
		if(!file_exists($cache_dir)) 
			@mkdir($cache_dir, 0777, true);
		if(!file_exists($cache_dir))
			$xphpt_file_cache_dir = null;
		else 
			$xphpt_file_cache_dir = $cache_dir;
	}

	xphpt_build_apply($dirs);
	
	return $xphpt_files_sections;
}

function xphpt_build_apply($dir) {
	global $xphpt_files_sections;
	$xphpt_files_sections = array();

	// соберём все файлы phpt
	$xphpt_files = xphpt_find_files($dir);
// print_r($xphpt_files);

	// распарсим файл на секции
	foreach($xphpt_files as $file) {
		@unlink(rtrim($file, 't'));
		$sections = xphpt_parse_phpt_file($file);
		
		$sections['MATCHE'] = implode (" AND ", explode ("\n", trim($sections['MATCHE'])));
		
		$xphpt_files_sections[$file] = $sections;
	}
}

function xphpt_apply($compiled_templates, $bemarray = array(), $mode = '') {
	global $xphpt_files_sections;
	$xphpt_files_sections = $compiled_templates;
	
	return apply($mode, $bemarray);
}

function apply($mode = '', $block = null, $args = null) {
	if(!empty($GLOBALS['xphpt_debug'])) {
		error_reporting(E_ALL);
		ini_set('display_errors', 'on');
	}
	assert('is_array($block);') or debug_print_backtrace();

//	global $__apply_func;
//	$__apply_func = create_function('', 'return;');
// 	return call_user_func($__apply_func, $mode, $barray, $args);

	global $xphpt_files_sections;
// print_r($xphpt_files_sections);
	if(!empty($GLOBALS['xphpt_debug']))
		print_r(array_keys($xphpt_files_sections));

	foreach($xphpt_files_sections as $xphpt_file => $xphpt_file_sections) {
		if(!empty($GLOBALS['xphpt_debug'])) var_dump($xphpt_file);

		if(array_key_exists('0', $block)) {
			trigger_error('XPHPT: exist $block[0]! - skip', E_USER_NOTICE);
			return false;
		}
		
		// MATCHE
		$func = create_function(
			'$mode, $'.implode(',$', array_keys($block)), 
			'return '.$xphpt_file_sections['MATCHE'].';');
		$match = call_user_func_array($func, array('mode' => $mode)+$block);
		if(!$match) continue;

		// PHP
		global $xphpt_file_cache_dir;
		if(!empty($xphpt_file_cache_dir))
			xphpt_apply_include( rtrim($xphpt_file_cache_dir, "\\/")."/".rtrim(basename($xphpt_file), 't'), $block, $mode);
		else
			xphpt_apply_eval(trim(
				strncmp($xphpt_file_sections['PHP'], '<?php', 5) == 0
				? preg_replace('~^<\?php~', '', $xphpt_file_sections['PHP'])
				: '?>'.$xphpt_file_sections['PHP']
				), 
				$block, $mode);
		
		return true;
	}
	
	global $xphpt_apply_traversal_mode;
	switch($xphpt_apply_traversal_mode) {
		case 'only_content':
			if(array_key_exists('content', $block) == false)
				return false;
			if(is_array($block['content']) == false) {
				trigger_error("content => ".gettype($val)." (skip)", E_USER_NOTICE);
				return false;
			}
			foreach($block['content'] as $index => $child_block) {
// if(!empty($GLOBALS['xphpt_debug'])) var_dump($child_block);
				apply($mode, $child_block, $args);
			}
			break;
		case 'only_arrays':
			foreach($block as $key => $val)
				if(is_array($val))
					apply($mode, $val, $args);
			break;
		case 'all':
			foreach($block as $key => $val)
				apply($mode, $val, $args);
			break;
		default:
			trigger_error("unknown apply traversal mode \"$xphpt_apply_traversal_mode\"!", E_USER_NOTICE);
			return false;
	}
}

function xphpt_apply_include($__include_file, $__block, $mode) {
if(!empty($GLOBALS['xphpt_debug'])) var_dump(__FILE__.':'.__LINE__, $__include_file);
	extract($__block);
	ob_start();
	$__result = include $__include_file;
	echo ltrim(ob_get_clean(), "\n");
	return $__result;
}

function xphpt_apply_eval($__php_code, $__block, $mode) {
if(!empty($GLOBALS['xphpt_debug'])) var_dump(__FILE__.':'.__LINE__, __FUNCTION__);
// var_dump($__php_code);
	extract($__block);
	return eval($__php_code);
}

