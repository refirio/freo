<?php

/*********************************************************************

 freo | フィルター取得 | ページ (2010/10/29)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* フィルター取得 */
function freo_filter_page($mode, $pages)
{
	global $freo;

	if (empty($pages)) {
		return array();
	}

	//データ初期化
	$filters = array();
	foreach ($pages as $page) {
		$filters[$page] = false;
	}

	$pages = array_map(array($freo->pdo, 'quote'), $pages);

	//フィルターID取得
	if ($mode == 'nobody' or ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		if ($mode == 'user' and !empty($_SESSION['filter'])) {
			$stmt = $freo->pdo->query('SELECT page_id FROM ' . FREO_DATABASE_PREFIX . 'filter_sets WHERE page_id IN(' . implode(',', $pages) . ') AND filter_id NOT IN(' . implode(',', array_map(array($freo->pdo, 'quote'), array_keys($_SESSION['filter']))) . ') GROUP BY page_id');
		} else {
			$stmt = $freo->pdo->query('SELECT page_id FROM ' . FREO_DATABASE_PREFIX . 'filter_sets WHERE page_id IN(' . implode(',', $pages) . ') GROUP BY page_id');
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
