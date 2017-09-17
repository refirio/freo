<?php

/*********************************************************************

 エントリーアーカイブ表示プラグイン (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_display_entry_archive()
{
	global $freo;

	//検索条件設定
	$condition = null;

	//制限されたエントリーを一覧に表示しない
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

	//エントリー取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT DATE_FORMAT(datetime, \'%Y-%m\') AS month, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' GROUP BY month ORDER BY month DESC');
	} else {
		$stmt = $freo->pdo->prepare('SELECT STRFTIME(\'%Y-%m\', datetime) AS month, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' GROUP BY month ORDER BY month DESC');
	}
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$archives = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (preg_match('/^(\d\d\d\d)\-(\d\d)$/', $data['month'], $matches)) {
			$archives[] = array(
				'year'  => $matches[1],
				'month' => $matches[2],
				'count' => $data['count']
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_entry_archives' => $archives
	));

	return;
}

?>
