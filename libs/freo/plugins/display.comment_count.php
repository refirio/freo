<?php

/*********************************************************************

 コメント数表示プラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_comment_count()
{
	global $freo;

	if (!$freo->smarty->get_template_vars('entries')) {
		return;
	}

	//エントリーID取得
	$entry_keys = array_keys($freo->smarty->get_template_vars('entries'));

	//データ初期化
	$comment_counts = array();
	foreach ($entry_keys as $entry) {
		$comment_counts[$entry] = 0;
	}

	//エントリーごとのコメント数取得
	$stmt = $freo->pdo->query('SELECT entry_id, COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE entry_id IN(' . implode(',', $entry_keys) . ') GROUP BY entry_id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$comment_counts[$data[0]] = $data[1];
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_comment_counts' => $comment_counts
	));

	return;
}

?>
