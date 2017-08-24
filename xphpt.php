<?php

/**
 *  XPHPT - eXtansable PHP Templates
 *  
 *  @version	0.5
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
 
if(!empty($GLOBALS['xphpt_debug'])) {
	error_reporting(E_ALL);
	ini_set('display_errors', 'on');
}
	
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

// global $xphpt_root, $xphpt_current, $xphpt_parents, $xphpt_key;

function xphpt_parse_phpt_file(&$ctx, $file) {

	if(($fp = fopen($file, "rb")) == false) {
		trigger_error("Cannot open PHPT-file: $file! (skip)", E_USER_NOTICE);
		return false;
	}

	if(feof($fp)) {
		trigger_error("Empty PHPT-file [$file]!", E_USER_NOTICE);
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
			
			$tpl_suffix = substr(trim(array_shift($sec_match), '-'), 6);
			
			if(!isset($ctx['_parsed_templates'][count($sec_match)]))
				$ctx['_parsed_templates'][count($sec_match)] = array();

			// добавляем шаблон как php-код
			if(empty($ctx['templates_cache'])) {
				
				$ctx['_parsed_templates'][count($sec_match)][] = array(
					'file' => $file,
					'tpl_suffix' => $tpl_suffix,
					'match' => $sec_match,
					'php_code' => str_pad("", array_shift($sec_php), "\n").' ?>'.implode("\n", $sec_php));
			}
			
			// или как файл в кеше
			else {
				// формируем имя кеш-файла для шаблона
				$cache_file = $ctx['templates_cache'].pathinfo($file, PATHINFO_FILENAME);
				if($tpl_suffix)
					$cache_file .= '.'.$tpl_suffix.'.php';
				else
					$cache_file .= '.php';
				
				file_put_contents($cache_file, "<?php ".str_pad("", array_shift($sec_php), "\n").' ?>'.implode("\n", $sec_php));
				
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
			trigger_error("Cannot read PHPT-file", E_USER_NOTICE);
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
		"\$_expr_cache =& \$ctx['_expr_cache'];",
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
				if(strpos($expr, "//") !== false)
					$expr = preg_replace("~//[^\"']+$~", '', $expr);
				elseif(strpos($expr, "/*") !== false)
					$expr = preg_replace('~/\*(?:[^*]*(?:\*(?!/))*)*\*/~', '', $expr);
					
				// пустые вырожения пропускаем
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
	if(empty($ctx['templates_cache'])) {
		$ctx['_compiled_match'] = create_function('$xphpt_current, $mode, $_args, $ctx, $xphpt_key, $xphpt_position', implode("\n", $match_code));
	} 
	else {
		$hash = md5(serialize(array($ctx['templates'], empty($ctx['exclude_templates'])?'':$ctx['exclude_templates']))); 
		file_put_contents($ctx['templates_cache']."xphpt_match.$hash.php", "<?php\n".implode("\n", $match_code));
// var_dump($ctx['templates_cache']."xphpt_match.$hash.php");
		$ctx['_compiled_match'] = create_function('$xphpt_current, $mode, $_args, $ctx, $xphpt_key, $xphpt_position', "return include '{$ctx['templates_cache']}xphpt_match.$hash.php';");
	}
}

function xphpt_parse_templates(&$ctx) {
	
	// разберёмся с файловым кешем для шаблонов
	if(!empty($ctx['templates_cache'])) {
		if(!file_exists($ctx['templates_cache'])) 
			@mkdir($ctx['templates_cache'], 0777, true);
		if(file_exists($ctx['templates_cache']))
			$ctx['templates_cache'] = rtrim($ctx['templates_cache'], '/').'/';
	}

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
 	global $xphpt_current /*, $xphpt_root, $xphpt_parents, $xphpt_key */;

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

	$xphpt_current = $val; // пригодится для apply('mode')
	
	// запустим MATCH
	assert('is_array($val); /* '.print_r($val, true).' */') or debug_print_backtrace();
	$tpl_rec = call_user_func($newctx['_compiled_match'], $val, $mode, $args, $newctx, $key, $position);

	// нашли подходящий шаблон
	if($tpl_rec) {
		if(!empty($tpl_rec['php_file']))
			$result = xphpt_apply_include($tpl_rec['php_file'], $val, $mode, $args, $newctx, $key, $position);
		else
			$result = xphpt_apply_eval(trim($tpl_rec['php_code']), $val, $mode, $args, $newctx, $key, $position);
		
		if(is_array($result))
			$result = xphpt_apply($result, $mode, $args, $newctx, $key, $position);
		
		return $result;
	}

	// шаблон не нашли по этому начинаем обходить дерево в глубину
	$result = $val;
	switch(empty($newctx['apply_traversal_mode']) ? $xphpt_default_ctx['apply_traversal_mode'] : $newctx['apply_traversal_mode']) {
		case 'bem':
		case 'only_content':
			if(!is_array($result) or !key_exists('content', $result))
				return $result;
			if(is_array($result['content']) == false)
				$result['content'] = (array) $result['content'];
			foreach($result['content'] as $index => $bem_item) {
// if(!empty($GLOBALS['xphpt_debug'])) var_dump($bem_item);
				$result['content'][$index] = xphpt_apply($bem_item, $mode, $args, null, $index, $index);
			}
			break;
		case 'only_arrays':
			$index = 1;
			foreach($result as $key => $val)
				if(is_array($val)) 
					$result[$key] = xphpt_apply($val, $mode, $args, null, $key, $index++);
				else 
					$index++;
			break;
		case 'xslt':
		case 'all':
			$index = 1;
			foreach($result as $key => $val)
				$result[$key] = xphpt_apply($val, $mode, $args, null, $key, $index++);
			break;
		default:
			trigger_error('Unknown apply traversal mode "'.(empty($newctx['apply_traversal_mode']) ? $xphpt_default_ctx['apply_traversal_mode'] : $newctx['apply_traversal_mode']).'"!', E_USER_NOTICE);
	}
	
	// востоновим контекст обратно если надо
	if(isset($old_xphpt_current_ctx))
		$xphpt_current_ctx = $old_xphpt_current_ctx;
		
	return $result;
}

// функция для внутреннего использования!
function xphpt_apply_include($xphpt_include_file, $xphpt_current, $mode, $_args, $ctx, $xphpt_key, $xphpt_position) {
	if(!empty($GLOBALS['xphpt_debug'])) var_dump(__FILE__.':'.__LINE__, $xphpt_include_file);
	
	// распаковываем окружение
	extract($xphpt_current); isset($_args) and extract($_args);
	
	// запускаем шаблон и придерживаем весь вывод
	ob_start();
	$__result = include $xphpt_include_file;
	echo ltrim(ob_get_clean(), "\n"); // отпускаем то, что придержали
	
	return $__result;
}

// функция для внутреннего использования!
function xphpt_apply_eval($xphpt_php_code, $xphpt_current, $mode, $_args, $ctx, $xphpt_key, $xphpt_position) {
	if(!empty($GLOBALS['xphpt_debug'])) var_dump(__FILE__.':'.__LINE__, $xphpt_php_code);
	
	// распаковываем окружение
	extract($xphpt_current); isset($_args) and extract($_args);
	
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
// 	

	if(isset($ctx) and is_array($ctx)) {
		
		// отчистим кеш
		$ctx['_expr_cache'] = isset($ctx['_expr_cache']) && is_object($ctx['_expr_cache'])
			? new SplFixedArray(count($ctx['_expr_cache'])) 
			: array();

		return toHtml(xphpt_apply($bem_array, (isset($ctx['mode']) ? $ctx['mode'] : ''), null, $ctx));
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
			
		$result = toHtml(xphpt_apply($bem_array, isset($ctx['mode']) ? $ctx['mode'] : '', null, null));
		
		// вернём обратно состояние кеша
		$xphpt_current_ctx['_expr_cache'] = $old_expr_cache;
		
		return $result;
	}
	
}

/**
 * Превращает BEMArray в HTML.
 *
 * @param {BemArray} $bem_array бэм-дерево или список бэк-деревьев
 * @param {String}   $block_name название родительского блока
 * @returns {String}
 */
function toHtml($bem_array, $block_name = null) {
	if(empty($bem_array)) return '';
	
	if(is_scalar($bem_array)) {
		return (string) $bem_array;
	}
	
	if(is_array($bem_array)) {
	
		$before_html = isset($bem_array['before_html']) ? $bem_array['before_html'] : '';
		$after_html = isset($bem_array['after_html']) ? $bem_array['after_html'] : '';
	
		if(isset($bem_array['html'])) 
			return $before_html.$bem_array['html'].$after_html;
	
		// если это список bem-элементов 
		if(!key_exists('block', $bem_array) and !key_exists('elem', $bem_array)) {
			return $before_html.implode("", array_map('toHtml', $bem_array)).$after_html;
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
		
		// Class
		if(!empty($bem_array['cls'])) 
			$cls = is_array($bem_array['cls']) ? implode(' ', $bem_array['cls']) : strval($bem_array['cls']);
		
		// Attributes
		if(isset($bem_array['attrs']) and is_array($bem_array['attrs'])) {
			$attrs = implode(" ", array_walk($bem_array['attrs'], 
				create_function('$attr, $val, $attrs', '$attrs[] = "$attr=\"".htmlspecialchars ($val)."\"";'), 
				array()));
		}
		else
			$attrs = isset($bem_array['attrs']) ? (string) $bem_array['attrs'] : '';
	
		// если укороченный тег (br, img, link...) то закрываем его сразу
		$shortTags = isset($xphpt_current_ctx['shortTags']) ? $xphpt_current_ctx['shortTags'] : $xphpt_default_ctx['shortTags'];
		if($p = stripos($shortTags, $tag) != false 
		&& in_array(substr($shortTags, $p+strlen($tag), 1), array(false, ' '))) {
			
			// в режиме генерации XHTML надо его закрыть
			if(isset($xphpt_current_ctx['xhtml']) ? $xphpt_current_ctx['xhtml'] : $xphpt_default_ctx['xhtml'])
				return "$before_html<$tag class=\"$cls\"".(empty($attrs)?'':$attrs)."/>$after_html";
			else
				return "$before_html<$tag class=\"$cls\"".(empty($attrs)?'':$attrs).">$after_html";
		}
	
		// это полный тег с внутренним HTML
		$innerHtml = isset($bem_array['content']) ? toHtml($bem_array['content']) : '';

		if(empty($tag)) 
			return $before_html.$innerHtml.$after_html;
		else
			return "$before_html<$tag class=\"$cls\"".(empty($attrs)?'':$attrs).">".$innerHtml."</$tag>$after_html";
		
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
function apply_templates($data, $mode, $params = null, $ctx = null) {
	global $xphpt_current_ctx;
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
	
	if(is_array($data) and isset($data[0]))
	foreach($data as $key => $val)
		apply($mode, $val, $args, $key);
	else
		apply($mode, $data, $args);
		
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
