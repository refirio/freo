<?php

/*********************************************************************

 freo | ルーティング関数 (2010/09/01)

 Copyright(C) 2009 freo.jp

*********************************************************************/

/* ルーティング定義 */
function freo_routing_config()
{
	global $freo;

	$freo->routing = array(
		'rss'  => 'feed',
		'rss2' => 'feed/rss2'
	);

	return;
}

/* ルーティング実行 */
function freo_routing_execute()
{
	global $freo;

	$path = implode('/', $freo->parameters);

	if (isset($freo->routing[$path])) {
		$freo->parameters = explode('/', $freo->routing[$path]);
	}

	return;
}

?>
