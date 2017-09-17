<?php

/*********************************************************************

 カテゴリー記事数表示プラグイン (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_display_category_count()
{
	global $freo;

	//検索条件設定
	$condition = null;

	//制限されたエントリーをカウントしない
	if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$entry_filters = freo_filter_entry('user', array_keys($freo->refer['entries']));
		$entry_filters = array_keys($entry_filters, true);
		$entry_filters = array_map('intval', $entry_filters);
		if (!empty($entry_filters)) {
			$condition .= ' AND id NOT IN(' . implode(',', $entry_filters) . ')';
		}

		$entry_securities = freo_security_entry('user', array_keys($freo->refer['entries']), array('password'));
		$entry_securities = array_keys($entry_securities, true);
		$entry_securities = array_map('intval', $entry_securities);
		if (!empty($entry_securities)) {
			$condition .= ' AND id NOT IN(' . implode(',', $entry_securities) . ')';
		}
	}

	//カテゴリーごとの記事数取得
	$stmt = $freo->pdo->prepare('SELECT category_id, COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'entries LEFT JOIN ' . FREO_DATABASE_PREFIX . 'category_sets ON id = entry_id WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' GROUP BY category_id');
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$category_counts = array();
	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$category_counts[$data[0]] = $data[1];
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_category_counts' => $category_counts
	));

	return;
}

?>
