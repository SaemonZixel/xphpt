--TEST--
* BEM: no block or elem #1
--DESCRIPTION--
Test templates for parsing
--MATCHE_RSS_ITEM--
$block == 'rss-channel-realty'
$elem == 'item'
--PHP_RSS_ITEM--
<?php
	return array(
		'tag' => 'item',
		'content' => array(
			array('tag' => 'link', 'content' => '/realty/view/'.$pbr_id),
			array('tag' => 'title', 'content' => htmlspecialchars($pbr_title)),
			array('tag' => 'description', 'content' => htmlspecialchars($pbr_extra)),
			array('tag' => 'pubDate', 'content' => date('r', strtotime($pbr_updatedate))),
			array('tag' => 'enclosure', 'content' => $image)
		)
	); 
?>
--MATCHE--
$block == 'request'
stripos($_SERVER['REQUEST_URI'], '/rss/rss_realty') === 0
--PHP--
<?php

return array(
		'block' => 'rss',
		'tag' => 'rss',
		'content' => array(
			array(
				'block' => 'rss-channel-realty', 
				'tag' => 'channel',
				'content' => array(
					array('elem' => 'item', 'pbr_id' => 1, 'pbr_title' => 'aaa', 'pbr_extra' => 'a.a.a.a...', 'pbr_updatedate' => '2000-01-01 00:00:00', 'image' => 'photo1.jpg'),
					array('elem' => 'item', 'pbr_id' => 2, 'pbr_title' => 'bbb', 'pbr_extra' => 'b.b.b.b...', 'pbr_updatedate' => '2000-01-02 00:00:00', 'image' => 'photo2.jpg')
				),
			)
		)
	);

?>
--FILE--
<?php

	require substr(empty($_SERVER['PWD']) ? getcwd() : $_SERVER['PWD'], 0, -6).'/xphpt.php';
	
	$_SERVER['HTTP_HOST'] = 'example.com';
	$_SERVER['REQUEST_URI'] = '/rss/rss_realty.xml';
	
// $GLOBALS['xphpt_debug'] = true;
$GLOBALS['xphpt_debug_apply_call_limit'] = 100;

	$ctx = array(
		'templates' => __FILE__.'t', 
		'maximum_deep_of_subdirectories' => 1);
		
// 	print_r(applyCtx(array('block' => 'request'), $ctx));
	print_r(applyCtx(array(
		'block' => 'rss',
		'tag' => 'rss',
		'attrs' => array('class' => null),
		'content' => array(
			array(
				'block' => 'rss-channel-realty', 
				'tag' => 'channel',
				'attrs' => array('class' => null),
				'content' => array(
					array('elem' => 'item', 'pbr_id' => 1, 'pbr_title' => 'aaa', 'pbr_extra' => 'a.a.a.a...', 'pbr_updatedate' => '2000-01-01 00:00:00', 'image' => 'photo1.jpg'),
					array('elem' => 'item', 'pbr_id' => 2, 'pbr_title' => 'bbb', 'pbr_extra' => 'b.b.b.b...', 'pbr_updatedate' => '2000-01-02 00:00:00', 'image' => 'photo2.jpg')
				),
			)
		)
	), $ctx));
	
// 	var_dump($xphpt_current_ctx, implode("\n", $GLOBALS['xphpt_last_compiled_match_code']));
?>
--EXPECT--
<rss><channel><item><link>/realty/view/1</link><title>aaa</title><description>a.a.a.a...</description><pubDate>Sat, 01 Jan 2000 00:00:00 +0000</pubDate><enclosure>photo1.jpg</enclosure></item><item><link>/realty/view/2</link><title>bbb</title><description>b.b.b.b...</description><pubDate>Sun, 02 Jan 2000 00:00:00 +0000</pubDate><enclosure>photo2.jpg</enclosure></item></channel></rss>