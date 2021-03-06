<?php

/**
 *  XPHPT - eXtansable PHP Templates
 *  
 *  @version	0.6
 *  @author   Saemon Zixel <saemonzixel@gmail.com>
 *  @link     https://github.com/SaemonZixel/xphpt
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
 
if(!empty($GLOBALS['xphpt_debug'])) {
	error_reporting(E_ALL);
	ini_set('display_errors', 'on');
}
	
// $GLOBALS['xphpt_debug_apply_call_limit'] = 1000;
	
global $xphpt_default_ctx, $xphpt_current_ctx;
$xphpt_current_ctx = null; 
$xphpt_default_ctx = array(
	'maximum_deep_of_subdirectories' => 10,
	'apply_traversal_mode' => 'bem', // bem, xslt, only_arrays, all
	'templates' => '.', // default path to templates - current directory
	'exclude_templates' => null,
	'templates_cache' => null, // null - use eval() for templates, '...' - generate php scripts for templates in this directory
	'current_templates' => null, // ?
	
	'delimElem' => '__',
	'delimMod' => '_',
// 	'escapeContent' => false,
	'shortTags' => 'area base br col command embed hr img input keygen link menuitem meta param source track wbr',
	'xhtml' => true,
	
	// if(apply_traversal_mode = bem)
	'_currBlock' => null,
	'elem' => null,
	'mods' => array(),
	'elemMods' => array(),
	'position' => null, // ?
	
	'mode' => '', // used when call applyCtx()
	
	'_parsed_templates' => null,
	'_compiled_match' => null, // create_function()
	'_matched_tpl_id' => null, // last matched tpl_id
	'_expr_cache' => null, // for internal use only!
);

global $xphpt_current, $xphpt_current_bem_block, $xphpt_position, $xphpt_last, $xphpt_parents/*, $xphpt_key*/;

function xphpt_parse_phpt_file(&$ctx, $file) {

	if(($fp = fopen($file, "rb")) == false) {
		trigger_error("Cannot open PHPT-file: $file! (skip)", E_USER_NOTICE);
		return false;
	}

	if(feof($fp)) {
		trigger_error("Empty PHPT-file: $file!", E_USER_NOTICE);
		return false;
	}
	
	$line_num = 0;
	$sec_start = false;
	$sec_name = null;
	$sec_names = array(); // чтоб отслеживать дубли
	$sec_match = null; // содержимое MATCHE-секции
	$sec_php = null; // содержимое PHP-секции
	do {
		$line = fgets($fp);

		// проверим на начало секции
		$sec_start = sscanf((string) $line, '--%[_A-Z0-9]%1[-]%1[-]', $sec_name, $dash1, $dash2) == 3;
		
		// завершим обработку предыдущих секций MATCH и PHP
		// (если мы достигли конца файла или новой секции)
		if(($line == false // конец файла
		|| $sec_start) // новая секция
		and !empty($sec_php) and !empty($sec_match)) { 
// var_dump(__FUNCTION__.':'.__LINE__, $sec_match, $sec_php);	
			
			if(count($sec_match) < 2) {
				trigger_error((isset($sec_match[0])?$sec_match[0]:"--MATCH--")." section is empty in $file! (skip template)", E_USER_NOTICE);
				$sec_match = $sec_php = null;
				continue;
			}

			if(count($tpl_suffix = explode('_', trim(array_shift($sec_match), '-'), 2)) == 2)
				$tpl_suffix = $tpl_suffix[1];
			else
				$tpl_suffix = '';
			
			if(!isset($ctx['_parsed_templates'][count($sec_match)]))
				$ctx['_parsed_templates'][count($sec_match)] = array();

			// добавляем шаблон как php-код
			if(empty($ctx['templates_cache'])) {
				
				$ctx['_parsed_templates'][count($sec_match)][] = array(
					'file' => $file,
					'tpl_suffix' => $tpl_suffix,
					'match' => $sec_match,
					'php_code' => '/* '.str_pad("", array_shift($sec_php), "\n").' */ global $xphpt_current; ?>'.implode("\n", $sec_php));
			}
			
			// или как файл в кеше
			else {
				// формируем имя кеш-файла для шаблона
				$cache_file = $ctx['templates_cache'].pathinfo($file, PATHINFO_FILENAME);
				if($tpl_suffix)
					$cache_file .= '.'.$tpl_suffix.'.php';
				else
					$cache_file .= '.php';
				
				file_put_contents($cache_file, "<?php ".str_pad("", array_shift($sec_php), "\n").' global $xphpt_current; ?>'.implode("\n", $sec_php));
				
				$ctx['_parsed_templates'][count($sec_match)][] = array(
					'file' => $file,
					'tpl_suffix' => $tpl_suffix,
					'match' => $sec_match,
					'php_file' => $cache_file
				);
			}
				
			// ... и обнуляем
			$sec_match = $sec_php = null;
		}
		
		// либо не удаётся прочитать, либо файл пуст
		if($line === false and $line_num == 0) {
			trigger_error("Cannot read or PHPT-file empty: $file", E_USER_NOTICE);
			return false;
		}
		
		// файл закончился
		elseif($line === false) 
			break;
		
		// считаем эту годную строку
		$line_num++;
// var_dump(__FUNCTION__.':'.__LINE__.' = '.$line.' '.$sec_name.' '.count($sec_match).' '.count($sec_php));
		
		// --???-- начало секции
		if($sec_start) {
			if(isset($sec_names[$sec_name]))
				trigger_error("Duplicated $sec_name section", E_USER_NOTICE);
			else
				$sec_names[] = $sec_name;
				
			if(strncmp($sec_name, 'MATCH', 5) == 0) {
				$sec_match = array(rtrim($line));
			}
			
			elseif(strncmp($sec_name, 'PHP', 3) == 0) {
				$sec_php = array($line_num);
			}
		} // if(--???--)...
			
		// --MATCH-- содержимое MATCH-секции
		elseif(strncmp($sec_name, 'MATCH', 5) == 0) {
			$sec_match[] = rtrim($line);
		}
		
		// --PHP--
		elseif(strncmp($sec_name, 'PHP', 3) == 0) {
			$sec_php[] = rtrim($line);
		}
	} while($line !== false);
	
	return true;
}

function xphpt_compile_match_func(&$ctx) {
	
	assert("isset(\$ctx['_parsed_templates'])");	
	
	$tpl_id = 0; // порядковый номер шаблона
	$_expr_cache = array(); // кеш результатов вырожений в MATCH-секции
	$ctx['_expr_cache'] =& $_expr_cache;
	
	// многострочные первые (приоритетнее) - однострочные последнии
	krsort($ctx['_parsed_templates']); 

	// теперь собераем код MATCH
	$match_code = array(
		"extract(\$xphpt_current); isset(\$_args) and extract(\$_args);",
		"global \$xphpt_default_ctx, \$xphpt_current_bem_block;
switch(empty(\$ctx['apply_traversal_mode']) ? \$xphpt_default_ctx['apply_traversal_mode'] : \$ctx['apply_traversal_mode']) {
case 'bem': case 'only_content':
	if(!isset(\$block) and !isset(\$elem)) \$block = \$elem = null;
	elseif(!isset(\$block) and isset(\$elem)) \$block = \$xphpt_current_bem_block;
	elseif(!isset(\$elem)) \$elem = null;
	\$block_elem = \"\$block \$elem\";
	break;
default: 
	isset(\$block) or \$block = null;
	isset(\$elem) or \$elem = null;
	\$block_elem = null;
}",
		"\$_expr_cache =& \$ctx['_expr_cache'];",
isset($GLOBALS['xphpt_debug']) ? 'var_dump("MATCH: block = ".$block.", elem = ".$elem.", ".implode(", ", array_keys((array)$xphpt_current)).", ".implode(", ", array_keys((array)$_args))."...");' : '',
 		"switch(isset(\$ctx['_matched_tpl_id']) ? \$ctx['_matched_tpl_id'] : 1) {"
		);
	
		
	foreach($ctx['_parsed_templates'] as $level => $tpls) {
		$match_code[] = "\n/* ----- Priority $level ----- */";
		foreach($tpls as $tpl_num => $tpl_rec) {
			$match_code[] = 'case '.(++$tpl_id).': /* '.$tpl_rec['file'].(empty($tpl_rec['tpl_suffix'])?'':"#{$tpl_rec['tpl_suffix']}").' */';
			
			// выполним все выражения в MATCH-секции перед if
			$if_stmt = '';
			foreach($tpl_rec['match'] as $expr){
				// почистим комментарии
				if(strpos($expr, "//") !== false or strpos($expr, "/*") !== false)
					foreach(token_get_all("<?php ".$expr) as $token)
						if($token[0] == 370) $expr = str_replace($token[1], '', $expr);
				
				// пустые выражения пропускаем
				if(empty($expr)) continue;
				
				$expr_num = array_search($expr, $_expr_cache);
				if($expr_num === false) {
					$expr_num = count($_expr_cache);
					$_expr_cache[] = $expr;
					$match_code[] = "\t\$_expr_cache[$expr_num] = ($expr);";
				}
				$if_stmt .= (empty($if_stmt) ? '' : ' and ') . "\$_expr_cache[$expr_num]"; 
			}
			
			// пропустим с пустым MATCH
			if(empty($if_stmt)) {
				if(!empty($GLOBALS['xphpt_debug']))
				trigger_error("--MATCH-- section has only comments in ".$tpl_rec['file'].(empty($tpl_rec['tpl_suffix'])?'':"#{$tpl_rec['tpl_suffix']}")."! (skip template)", E_USER_NOTICE);
				continue;
			}
			
			$match_code[] = "\tif($if_stmt) { \$ctx['_matched_tpl_id'] = $tpl_id; return \$ctx['_parsed_templates'][$level][$tpl_num]; }";
		}
	}
	
	// завершаем MATCH-код
	$match_code[] = "default: break;";
	$match_code[] = "}";
	$match_code[] = "return null;";
	
	// компилируем в анонимную функцию
	if(!empty($ctx['templates_cache'])) {
		$hash = md5(serialize(array($ctx['templates'], empty($ctx['exclude_templates'])?'':$ctx['exclude_templates']))); 
// var_dump($ctx['templates_cache']."xphpt_match.$hash.php");
		// попробуем сохранить в файл
		if(file_put_contents(rtrim($ctx['templates_cache'], '\\/').DIRECTORY_SEPARATOR."xphpt_match.$hash.php", "<?php\n".implode("\n", $match_code)) == true) {
			$ctx['_compiled_match'] = create_function('$xphpt_current, $mode, $_args, $ctx, $xphpt_key, $xphpt_position', "return include '{$ctx['templates_cache']}xphpt_match.$hash.php';");
			return;
		}
	}

	// если файлового кеша нет или не удалось сохранить в файл, то по старинке
	$ctx['_compiled_match'] = create_function('$xphpt_current, $mode, $_args, $ctx, $xphpt_key, $xphpt_position', implode("\n", $match_code));
	
	// для отладочных целей
	$GLOBALS['xphpt_last_compiled_match_code'] = $match_code;
}

function xphpt_parse_templates(&$ctx) {
	
	// разберёмся с файловым кешем для шаблонов
	if(!empty($ctx['templates_cache'])) {
		if(!file_exists($ctx['templates_cache'])) 
			@mkdir($ctx['templates_cache'], 0777, true);
		if(file_exists($ctx['templates_cache']))
			$ctx['templates_cache'] = rtrim($ctx['templates_cache'], '/').'/';
	}

	assert("isset(\$ctx['templates']);") or debug_print_backtrace();
	
	// пройдёмся по директориям и соберём все файлы-шаблоны
	$tpls_dir = (array) $ctx['templates'];
	$tpls_exclude = (array) (isset($ctx['exclude_templates']) ? $ctx['exclude_templates'] : null);
	$tpls_files = array();

	// выясним максимальную глубину поддерикторий для поиска шаблонов
	if(isset($ctx['maximum_deep_of_subdirectories']))
		$maximum_deep_of_subdirectories = $ctx['maximum_deep_of_subdirectories'];
	else {
		global $xphpt_default_ctx;
		$maximum_deep_of_subdirectories = $xphpt_default_ctx['maximum_deep_of_subdirectories'];
	}
	
	$eval_code_start = array('$dir0 = "";
	
		foreach($tpls_dir as $file_name0) {
			if(in_array($file_name0, $tpls_exclude)) continue;
			if(file_exists($file_name0) == false and !empty($GLOBALS["xphpt_debug"]))
				trigger_error("File or directory not exists: $file_name0! (skip)", E_USER_NOTICE);
			'); 
	$eval_code_end = array('
			if(substr($file_name0, -5) == ".phpt")
				$tpls_files[] = realpath($file_name0);
		}');
		
	for($i = 1; $i <= $maximum_deep_of_subdirectories; $i++) {
		
		$eval_code_start[] = '
			if(is_dir($dir'.($i-1).'.$file_name'.($i-1).')) {
				$dir'.$i.' = $dir'.($i-1).'.$file_name'.($i-1).'."/";

				// сообщим, что не смогли открыть директорию
				if(($dp'.$i.' = opendir($dir'.$i.')) === false) { 
					trigger_error("Cannot open directory: $dir'.$i.'! (skip)", E_USER_NOTICE);
					continue;
				}
		
				// перебераем все файлы и директории
				while(($file_name'.$i.' = readdir($dp'.$i.')) !== false) {

					// отфильтруем
					if($file_name'.$i.'[0] == "." or in_array($file_name'.$i.', $tpls_exclude) or in_array("{$dir'.$i.'}/{$file_name'.$i.'}", $tpls_exclude)) continue; ';
				
		$eval_code_end[] = '
					// файлы-шаблоны добавим в список
					if(substr($file_name'.$i.', -5) == ".phpt")
						$tpls_files[] = realpath("{$dir'.$i.'}/{$file_name'.$i.'}");
				}
				
				closedir($dp'.$i.');
			}';
	}
// print_r(implode("\n", $eval_code_start).implode("\n",array_reverse($eval_code_end)));
	// выполним сгенерированный код
	eval(implode("\n", $eval_code_start).implode("\n",array_reverse($eval_code_end)));
	
	if(isset($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.': $tpls_files = '.print_r($tpls_files, true));
	
	if(!empty($ctx['test_mode.return_tpls_files_only'])) return $tpls_files;
	
	// теперь распарсим каждый файл
	$ctx['_parsed_templates'] = array();
	foreach($tpls_files as $file) {
		xphpt_parse_phpt_file($ctx, $file);
	}
	
	// компилируем все MATCH всех шаблонов в одну функцию
	xphpt_compile_match_func($ctx);

	return $ctx;
}

/**
 * XPHPT: xphpt_apply()
 * 
 * @param {Array}  [$val]  данные (array/bem_array)
 * @param {String} [$key]  ключ/индекс данных из родительского массива, если существует
 * @param {String} [$mode] название режима
 * @param {Array}  [$args] хэш, значения которого будут доступны в MATCH и PHP секциях шаблона
 * @param {Array}  [$ctx]  новый контекст с настройками
 *
 * @returns {*} Возвращает результат работы режима
 */
function xphpt_apply($val = array(), $mode = '', $args = null, $newctx = null, $key = null, $position = null) {
	global $xphpt_current_ctx, $xphpt_default_ctx;
 	global $xphpt_current, $xphpt_current_bem_block, $xphpt_position, $xphpt_last, $xphpt_parents/*, $xphpt_root, $xphpt_key */;

	if(isset($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.'(call): $val = '.(is_array($val)?(substr(json_encode($val),0,100).'...'):$val).', $key = '.$key.', parents='.count($xphpt_parents));
	
	if(isset($GLOBALS['xphpt_debug_apply_call_limit'])) {
		if(empty($GLOBALS['xphpt_debug_apply_call_limit'])) {
			trigger_error("\$GLOBALS['xphpt_debug_apply_call_limit'] = {$GLOBALS['xphpt_debug_apply_call_limit']} reached! return NULL as result.", E_USER_NOTICE);
// 			debug_print_backtrace();
			return null;
		}
		else $GLOBALS['xphpt_debug_apply_call_limit']--;
	}
	
	// если передали новый $ctx, то проверяем его и переключаемся на него
	if(isset($newctx)) {
		assert('is_array($newctx);') or print_r($newctx);

		// если шаблоны не скомпилированны, то скомпелируем
		if(empty($newctx['_parsed_templates'])) {
			xphpt_parse_templates($newctx);
		}

		$old_xphpt_current_ctx = $xphpt_current_ctx;
		$xphpt_current_ctx = $newctx;
	}
	
	// используем текущий $ctx с настройками
	else 
		$newctx = empty($xphpt_current_ctx) ? $xphpt_default_ctx : $xphpt_current_ctx;

	// строки не надо обрабатывать, они сразу идут в конечный результат
	if(is_string($val) or is_numeric($val) or is_null($val) or is_bool($val)) return $val;

	// абрабатываем только структуры далее
	assert('is_array($val);') or debug_print_backtrace(); // проверочка на всякий случай
	assert('isset($newctx["_compiled_match"]) and is_callable($newctx["_compiled_match"]);', 'Context not initialized?');

	// стек родителей
	if(empty($xphpt_parents)) $xphpt_parents = array($xphpt_current);
	else $xphpt_parents[] = $xphpt_current;
	
	$xphpt_current = $val; // пригодится для apply('mode')
	
	// position(), last() ...
	$old_xphpt_last = $xphpt_last; // сохраним заранее
	$old_xphpt_position = $xphpt_position;
	$xphpt_position = $position;
	
	$old_xphpt_current_bem_block = $xphpt_current_bem_block;
	if(isset($val['block'])) $xphpt_current_bem_block = $val['block'];
// 	elseif(!isset($val['elem'])) $xphpt_current_bem_block = null;

	// запустим MATCH
	$tpl_rec = call_user_func($newctx['_compiled_match'], $val, $mode, $args, $newctx, $key, $position);
	
	// если была ошибка в MATCH
 	if(($error = error_get_last())
 	&& strpos($error['file'], '/xphpt.php') and strpos($error['file'], 'runtime-created function')) {
//  		var_dump($GLOBALS['xphpt_last_compiled_match_code']);

		// покажем строку вызвавшую ошибку
		if(!empty($GLOBALS['xphpt_last_compiled_match_code'])) {
			$lines = $GLOBALS['xphpt_last_compiled_match_code'];
			trigger_error("Error line #{$error['line']}: ".$lines[$error['line']-1], E_USER_NOTICE);
		}
		
		// почистим за собой (PHP7+ only)
		if(function_exists('error_clear_last')) 
			error_clear_last();
 	}

	if(isset($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.'(match-result): $tpl_rec = '.(is_array($tpl_rec)?"{$tpl_rec['file']}#{$tpl_rec['tpl_suffix']}":$tpl_rec));
	
	// нашли подходящий шаблон
	if($tpl_rec) {
		if(!empty($tpl_rec['php_file']))
			$result = xphpt_apply_include($tpl_rec['php_file'], $val, $mode, $args, $newctx, $key, $position);
		else {
			$result = xphpt_apply_eval($tpl_rec['php_code'], $val, $mode, $args, $newctx, $key, $position);
			
			// на случай ошибки в шаблоне
			if(($error = error_get_last())
			&& strpos($error['file'], '/xphpt.php') and strpos($error['file'], 'eval()\'d code')) {
				$lines = explode("\n", $tpl_rec['php_code']);
				trigger_error("Error line in {$tpl_rec['file']}".(empty($tpl_rec['tpl_suffix'])?'':"#{$tpl_rec['tpl_suffix']}")."({$error['line']}): ".$lines[$error['line']-1], E_USER_NOTICE);
			}
		}
		
		if(isset($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.'(template-result): $result = '.(is_array($result)?(substr(json_encode($result),0,100).'...'):var_export($result,true)));
	}
	
	// обходим дерево в глубину
	switch(empty($newctx['apply_traversal_mode']) ? $xphpt_default_ctx['apply_traversal_mode'] : $newctx['apply_traversal_mode']) {
		case 'bem':
			// результат работы шаблона рекурсивно пропустим ещё раз через шаблоны
			if($tpl_rec and $result) {
				$xphpt_current = array_pop($xphpt_parents); // не даём добавить старый элемент как родителя
				$result = xphpt_apply($result, $mode, $args, $newctx, $key, $position);
				break;
			}
			
			// сказали удалить этот BEM-элемент
			elseif($tpl_rec and $result === false) {
				$result = null;
				break;
			}
			
			// шаблон не нашёлся, идём в глубину
			else {
				$result = $xphpt_current;
				
				if(!is_array($result) or !key_exists('content', $result))
					break;

				if(is_array($result['content']) == false)
					$result['content'] = (array) $result['content'];
			
				$counter = count($result['content']);
				foreach($result['content'] as $index => $bem_item) {
					$xphpt_last = $counter-- == 1;
					$result['content'][$index] = xphpt_apply($bem_item, $mode, $args, null, $key, $index);
				}
				
				break;
			}
		case 'only_arrays':
			// нормализуем $result
			isset($result) or $result = $xphpt_current;
			
			$index = 0; $counter = count($result);
			foreach($result as $key => $val) {
				$xphpt_last = $counter-- == 1;
				
				if(is_array($val)) 
					$result[$key] = xphpt_apply($val, $mode, $args, null, $key, $index++);
				else 
					$index++;
			}
			break;
		case 'xslt':
			// XSL-T: если шаблон отработал, то спускатся ниже и повторно обрабатывать резултат не надо!
			if($tpl_rec) break;
			else $result = $xphpt_current;
			
			$index = 0; $counter = count($result);
			foreach($result as $key => $val) {
				$xphpt_last = $counter-- == 1;
			
				$result[$key] = xphpt_apply($val, $mode, $args, null, $key, $index++);
			}
			
			break;
		default:
			trigger_error('Unknown apply traversal mode "'.(empty($newctx['apply_traversal_mode']) ? $xphpt_default_ctx['apply_traversal_mode'] : $newctx['apply_traversal_mode']).'"!', E_USER_NOTICE);
	}
	
	// восстановим то, что сохранилие
	if(isset($old_xphpt_last))
		$xphpt_last = $old_xphpt_last;
	
	// восстановим контекст обратно если надо
	if(isset($old_xphpt_current_ctx))
		$xphpt_current_ctx = $old_xphpt_current_ctx;
		
	// вернём обратно текущий Блок
	$xphpt_current_bem_block = $old_xphpt_current_bem_block;

	// вернём обратно текущий из стека родителей
	$xphpt_current = array_pop($xphpt_parents);
	
	if(isset($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.'(return): $result = '.(is_array($result)?(substr(json_encode($result),0,100).'...'):var_export($result,true)));
	
	return $result;
}

// функция для внутреннего использования!
function xphpt_apply_include($xphpt_include_file, $xphpt_current, $mode, $_args, $ctx, $xphpt_key, $xphpt_position) {
	if(!empty($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.'(include): xphpt_include_file = '.$xphpt_include_file);
	
	// распаковываем окружение
	extract($xphpt_current); isset($_args) and extract($_args);
	
	// (БЭМ) наследование блока элементами
	global $xphpt_current_bem_block;
	if(!isset($block) and !isset($elem)) $block = $elem = null;
	elseif(!isset($block) and isset($elem)) $block = $xphpt_current_bem_block;
	elseif(!isset($elem)) $elem = null;
	
	// запускаем шаблон и придерживаем весь вывод
	ob_start();
	$__result = include $xphpt_include_file;
	echo ltrim(ob_get_clean(), "\n"); // отпускаем то, что придержали
	
	return $__result === 1 ? null : $__result; // при успехе include возвращает 1
}

// функция для внутреннего использования!
function xphpt_apply_eval($xphpt_php_code, $xphpt_current, $mode, $_args, $ctx, $xphpt_key, $xphpt_position) {
	if(!empty($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.'(evalute): xphpt_php_code = '.$xphpt_php_code);
	
	// распаковываем окружение
	extract($xphpt_current); isset($_args) and extract($_args);
	
	// (БЭМ) наследование блока элементами
	global $xphpt_current_bem_block;
	if(!isset($block) and !isset($elem)) $block = $elem = null;
	elseif(!isset($block) and isset($elem)) $block = $xphpt_current_bem_block;
	elseif(!isset($elem)) $elem = null;
	
	// запускаем шаблон и возвращаем результат его работы
	return eval($xphpt_php_code);
}

/**
 * BEM: apply()
 * 
 * @param {String} $mode название режима
 * @param {Array} $args хэш, значения которого будут доступны в MATCH и PHP секциях шаблона
 *
 * @returns {*} Возвращает результат работы режима
 */
function apply($mode = '', $args = null) {
	global $xphpt_current_ctx, $xphpt_current;
	
	if(isset($xphpt_current_ctx['apply_traversal_mode']) and $xphpt_current_ctx['apply_traversal_mode'] == 'bem')
		return xphpt_apply($xphpt_current, $mode, $args);
		
	$old_traversal_mode = isset($xphpt_current_ctx['apply_traversal_mode']) 
		? $xphpt_current_ctx['apply_traversal_mode'] : null;
	$xphpt_current_ctx['apply_traversal_mode'] = 'bem';
	
	$result = xphpt_apply($xphpt_current, $mode, $args);
	
	$xphpt_current_ctx['apply_traversal_mode'] = $old_traversal_mode;
	return $result;
}

/**
 * BEM: applyNext()
 *
 * @param {Array} [$newctx] новый контекст с настройками. Если не указан будет использован текущий
 *
 * @returns {*}
 */
function applyNext($newctx = null) {
	global $xphpt_current_ctx;
	
	return xphpt_apply($bem_array, (isset($newctx, $newctx['mode']) ? $newctx['mode'] : ''), null, $newctx);
}

/**
 * BEM: applyCtx()
 *
 * @param   {BemArray} $bem_array  входные данные
 * @param   {Array}    [$ctx]      новый контекст с настройками. Если не указан будет использован текущий
 * @returns {String} результат в виде HTML-кода дерева BEM
 */
function applyCtx($bem_array, $ctx = null) {

	if(isset($ctx) and is_array($ctx)) {
		
		// отчистим кеш
		$ctx['_expr_cache'] = isset($ctx['_expr_cache']) && is_object($ctx['_expr_cache'])
			? new SplFixedArray(count($ctx['_expr_cache'])) 
			: array();

		$result_bem_array = xphpt_apply($bem_array, (isset($ctx['mode']) ? $ctx['mode'] : ''), null, $ctx);
	} 
	else {
// 		trigger_error('Not implemented yet!', E_USER_ERROR);

		global $xphpt_current_ctx, $xphpt_default_ctx;
		assert('is_array($xphpt_current_ctx);');
		
		// отчистим кеш временно
		$old_expr_cache = $xphpt_current_ctx['_expr_cache'];
		$xphpt_current_ctx['_expr_cache'] = isset($xphpt_current_ctx['_expr_cache']) && is_object($xphpt_current_ctx['_expr_cache'])
			? new SplFixedArray(count($xphpt_current_ctx['_expr_cache'])) 
			: array();
			
		$result_bem_array = xphpt_apply($bem_array, isset($ctx['mode']) ? $ctx['mode'] : '', null, null);
		
		// вернём обратно состояние кеша
		$xphpt_current_ctx['_expr_cache'] = $old_expr_cache;
		
	}

	return toHtml($result_bem_array);
}

/**
 * BH: Возвращает/устанавливает содержимое в зависимости от аргументов.
 *
 * @param   {String}  [$value]  новое содержимое (null - для удаления содержимого)
 * @param   {Boolean} [$force]  задать содержимое, даже если оно было задано ранее.
 * @returns {String}  содержимое новое или существующее
 */
function content($value = null, $force = true) {
	global $xphpt_current;
	
	if(func_num_args() == 0) 
		return is_array($xphpt_current) && isset($xphpt_current['content'])
			? $xphpt_current['content']
			: null;
	else {
		assert('is_array($xphpt_current);');
		
		if(isset($xphpt_current['content']) and is_null($value))
			unset($xphpt_current['content']);
		else
			$xphpt_current['content'] = $value;
	}
	
	return $value;
}

/**
 * BH: Возвращает/устанавливает значение атрибута в зависимости от аргументов.
 *
 * @param   {String}  $key      ключ атрибута
 * @param   {String}  [$value]  новое значение атрибута (null - для удаления атрибута)
 * @param   {Boolean} [$force]  задать значение атрибута, даже если оно было задано ранее.
 * @returns {String}  значение атрибута
 */
function attr($key, $value = null, $force = true) {
	global $xphpt_current;
	
	if(is_null($value)) 
		return is_array($xphpt_current) && isset($xphpt_current['attrs']) && is_array($xphpt_current['attrs']) && key_exists($key, $xphpt_current['attrs'])
		? $xphpt_current['attrs'][$key]
		: null;
		
	else {
		assert('is_array($xphpt_current);');
		
		if(!isset($xphpt_current['attrs']) and !is_null($value))
			$xphpt_current['attrs'] = array($key => $value);
		elseif(is_null($value))
			unset($xphpt_current['attrs'][$key]);
		else
			$xphpt_current['attrs'][$key] = $value;
	}
	
	return $value;
}

/**
 * Bозвращает позицию текущего элемента в рамках родительского.
 *
 * @returns {Integer}  номер (0 - первый элемент)
 */
function position() {
	global $xphpt_position;
	return $xphpt_position+1;
}

/**
 * возвращает true, если текущий элемент — последний в рамках родительского элемента.
 *
 * @returns {Boolean}
 */
function isLast() {
	global $xphpt_last;
	return $xphpt_last;
}

/**
 * возвращает true, если текущий элемент — первый в рамках родительского элемента.
 *
 * @returns {Boolean}
 */
function isFirst() {
	global $xphpt_position;
	return $xphpt_position == 0;
}

/**
 * Превращает BEMArray в HTML.
 *
 * @param {BemArray} $bem_array бэм-дерево или список бэк-деревьев
 * @param {String}   $block_name название родительского блока
 * @returns {String}
 */
function toHtml($bem_array, $block_name = null, $level = 0) {
if(isset($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.': '.str_pad('', intval($level)*2).(is_array($bem_array)&&!isset($bem_array[0])?(isset($bem_array['tag'])?$bem_array['tag']:'div').'.'.(isset($bem_array['block'])?$bem_array['block']:$block_name).(isset($bem_array['elem'])?'__'.$bem_array['elem']:'').' ':'').(is_array($bem_array)?('['.implode(', ',array_keys($bem_array)).']'):"'$bem_array'"));
	if(empty($bem_array)) return '';
	
	if(is_scalar($bem_array)) {
		// TODO htmlspecialchars()
		return (string) $bem_array;
	}
	
	if(is_array($bem_array)) {
	
		// если кто-то забыл или путает
		$before_html = isset($bem_array['before_html']) 
			? $bem_array['before_html'] 
			: (isset($bem_array['html_before']) 
			? $bem_array['html_before']
			: '');
		$after_html = isset($bem_array['after_html']) 
			? $bem_array['after_html'] 
			: (isset($bem_array['html_after']) 
			? $bem_array['html_after']
			: '');
	
		if(isset($bem_array['html'])) 
			return $before_html.$bem_array['html'].$after_html;
	
		// если это список bem-элементов 
		if(is_numeric(key($bem_array)) and $bem_array === array_values($bem_array)) {
			$result = array($before_html);
			foreach($bem_array as $bem_item)
				$result[] = toHtml(
					$bem_item, 
					isset($bem_array['block']) ? $bem_array['block'] : $block_name,
					$level);
			$result[] = $after_html;
			return implode("", $result);
		}
	
		global $xphpt_default_ctx, $xphpt_current_ctx;
		
		// Tag
		$tag = isset($bem_array['tag']) ? $bem_array['tag'] : 'div';

		// BEM
		$cls = isset($bem_array['block']) ? $bem_array['block'] : $block_name;
		if(isset($bem_array['elem'])) {
			$delimElem = isset($xphpt_current_ctx['delimElem']) ? $xphpt_current_ctx['delimElem'] : $xphpt_default_ctx['delimElem'];
			
			$cls .= $delimElem.$bem_array['elem'];
		}
		
		// Если не указаны не block не elem, значит это голый тег
		elseif(!isset($bem_array['block']) and !isset($bem_array['elem'])) {
			$cls = '';
		}
		
		// Class
		if(!empty($bem_array['cls'])) 
			$cls = is_array($bem_array['cls']) ? implode(' ', $bem_array['cls']) : strval($bem_array['cls']);
		
		// Attributes
		if(isset($bem_array['attrs']) and is_array($bem_array['attrs'])) {
		
			// attrs(class => null)
			if(array_key_exists('class', $bem_array['attrs']))
				unset($cls);
		
			$attrs = array();
			foreach($bem_array['attrs'] as $attr => $val)
				if(isset($val)) $attrs[] = " $attr=\"".htmlspecialchars($val)."\"";
			$attrs = implode("", $attrs);
		}
		else
			$attrs = isset($bem_array['attrs']) ? ' '.strval($bem_array['attrs']) : '';
	
		// если укороченный тег (br, img, link...) то закрываем его сразу
		$shortTags = isset($xphpt_current_ctx['shortTags']) ? $xphpt_current_ctx['shortTags'] : $xphpt_default_ctx['shortTags'];
		if(empty($bem_array['content'])
		&& ($p = stripos($shortTags, $tag)) != false 
		&& ($p === 0 or $shortTags[$p-1] == ' ')
		&& in_array(substr($shortTags, $p+strlen($tag), 1), array(false, ' '))) {
		
			if(isset($GLOBALS['xphpt_debug'])) var_dump(__FUNCTION__.': * ShortTag = '.$tag);
			
			// в режиме генерации XHTML надо его закрыть
			if(isset($xphpt_current_ctx['xhtml']) ? $xphpt_current_ctx['xhtml'] : $xphpt_default_ctx['xhtml'])
				return "$before_html<$tag".(empty($cls)?'':" class=\"$cls\"").(empty($attrs)?'':$attrs)."/>$after_html";
			else
				return "$before_html<$tag".(empty($cls)?'':" class=\"$cls\"").(empty($attrs)?'':$attrs).">$after_html";
		}
	
		// это полный тег с внутренним HTML
		$innerHtml = empty($bem_array['content']) ? '' 
			: toHtml(
				$bem_array['content'], 
				isset($bem_array['block']) ? $bem_array['block'] : $block_name,
				intval($level)+1
				);

		if(empty($tag)) 
			return $before_html.$innerHtml.$after_html;
		else
			return "$before_html<$tag".(empty($cls)?'':" class=\"$cls\"").(empty($attrs)?'':$attrs).">".$innerHtml."</$tag>$after_html";
		
	}
	
	trigger_error('Invalid type '.gettype($bem_array).' of \$bem_array!', E_USER_NOTICE);
	return print_r($bem_array, true);
}

/**
 * XSL-T: <apply-temlates select="..." mode="...">
 *
 * @param {Array} $data входные данные
 * @param {String} $mode название режима
 * @param {Array} [$params] массив с дополнительными переменными, будет распакован extract($params)
 * @param {Array} [$ctx]
 * @returns {*} Возвращает результат работы режима
 */ 
function apply_templates($data = null, $mode = '', $params = null, $ctx = null) {
	global $xphpt_current_ctx, $xphpt_current;
	if(!$ctx and !$xphpt_current_ctx) { 
		global $xphpt_default_ctx;
		$ctx = $xphpt_default_ctx;
	}
	elseif(!$ctx) 
		$ctx =& $xphpt_current_ctx;
	
	if($ctx['apply_traversal_mode'] != 'xslt') {
		$old_apply_traversal_mode = $ctx['apply_traversal_mode'];
		$ctx['apply_traversal_mode'] = 'xslt';
	}
	
	if(func_num_args() == 0) 
		$data = $xphpt_current;

	// если это список элементов
	if(is_numeric(key($data)) and $data === array_values($data))
	foreach($data as $key => $val)
		xphpt_apply($val, $mode, $params, $ctx, null, $key);
	else
		xphpt_apply($data, $mode, $params, $ctx);
		
	if(isset($old_apply_traversal_mode))
		$ctx['apply_traversal_mode'] = $old_apply_traversal_mode;
}

/**
 * XSL-T: <call-temlate name="...">
 *
 * @param {String/Array} $filename название файла с шаблоном или файл и название подшаблона
 * @param {Array} [$params] массив с дополнительными переменными, будет распакован extract($params) (необязательно)
 * @param {Array} [$ctx] контекст (необязательно)
 * @returns {*} Возвращает результат работы шаблона
 */ 
function call_template($filename, $params = null, $ctx = null) {
	global $xphpt_current_ctx, $xphpt_default_ctx;

	// если не указан $ctx, то создадим и настроим сами
	if(empty($ctx)) {
		$ctx = array();
		if(isset($xphpt_current_ctx, $xphpt_current_ctx['templates_cache']))
			$ctx['templates_cache'] = $xphpt_current_ctx['templates_cache'];
		elseif(isset($xphpt_default_ctx, $xphpt_default_ctx['templates_cache']))
			$ctx['templates_cache'] = $xphpt_default_ctx['templates_cache'];
	}
	
	$file = is_array($filename) ? $filename[0] : $filename;
	$sub_tpl_name = strtoupper(is_array($filename) ? $filename[1] : '');
	
	// распарсим шаблон файл
	xphpt_parse_phpt_file($ctx, $file);
	assert('!empty($ctx["_parsed_templates"]);');

	// теперь найдём шаблон или подшаблон и выполним его
	foreach($ctx["_parsed_templates"] as $match_priority => $tpls)
	foreach($tpls as $tpl_rec) {
// var_dump($tpl_rec);
		if($tpl_rec['file'] != basename($file)) continue;
		if(!empty($sub_tpl_name) and $tpl_rec['tpl_suffix'] != $sub_tpl_name) continue;
		
		$xphpt_current_ctx =& $ctx;
		
		if(!empty($params)) assert('is_array($params);');
		
		// выполним шаблон
		if(!empty($tpl_rec['php_file']))
			$result = xphpt_apply_include($tpl_rec['php_file'], 
				array(), '', empty($params) ? array() : $params, $ctx, null, null);
		else
			$result = xphpt_apply_eval($tpl_rec['php_code'],
				array(), '', empty($params) ? array() : $params, $ctx, null, null);
		
		// вернём результат
		return $result;
	}
	
	// сообщим, что не нашли шаблон или подшаблон
	trigger_error('No matching template found in '.(is_array($filename) ? $filename[0]."#".$filename[1] : $filename).'!', E_USER_NOTICE);
	
	return null;
}
