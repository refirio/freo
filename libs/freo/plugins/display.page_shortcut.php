<?php

/*********************************************************************

 ページショートカット表示プラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_page_shortcut()
{
	global $freo;

	$shortcuts = explode("\n", $freo->config['plugin']['page_shortcut']['shortcuts']);

	$page_shortcuts = array();
	foreach ($shortcuts as $shortcut) {
		if (!$shortcut) {
			continue;
		}

		list($id, $text) = explode(',', $shortcut, 2);

		$page_shortcuts[$id] = array(
			'id'   => $id,
			'text' => $text
		);
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_page_shortcuts' => $page_shortcuts
	));

	return;
}

?>
