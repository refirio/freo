<?php

/*********************************************************************

 freo | フィルター取得 | エントリー (2010/10/29)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* フィルター取得 */
function freo_filter_entry($mode, $entries)
{
	global $freo;

	if (empty($entries)) {
		return array();
	}

	//データ初期化
	$filters = array();
	foreach ($entries as $entry) {
		$filters[$entry] = false;
	}

	$entries = array_map('intval', $entries);

	//フィルターID取得
	if ($mode == 'nobody' or ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		if ($mode == 'user' and !empty($_SESSION['filter'])) {
			$stmt = $freo->pdo->query('SELECT entry_id FROM ' . FREO_DATABASE_PREFIX . 'filter_sets WHERE entry_id IN(' . implode(',', $entries) . ') AND filter_id NOT IN(' . implode(',', array_map(array($freo->pdo, 'quote'), array_keys($_SESSION['filter']))) . ') GROUP BY entry_id');
		} else {
			$stmt = $freo->pdo->query('SELECT entry_id FROM ' . FREO_DATABASE_PREFIX . 'filter_sets WHERE entry_id IN(' . implode(',', $entries) . ') GROUP BY entry_id');
		}
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
			$filters[$data[0]] = true;
		}
	}

	return $filters;
}

?>
