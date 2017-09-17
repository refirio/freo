<?php

/*********************************************************************

 freo | 関連データ | ページ (2010/10/29)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 関連データ */
function freo_associate_page($mode, $data)
{
	global $freo;

	if ($mode == 'get') {
		return freo_associate_page_get($data);
	} elseif ($mode == 'post') {
		freo_associate_page_post($data);
	} elseif ($mode == 'delete') {
		freo_associate_page_delete($data);
	}

	return;
}

/* 関連データ取得 */
function freo_associate_page_get($pages)
{
	global $freo;

	if (empty($pages)) {
		return array();
	}

	//データ初期化
	$associates = array();
	foreach ($pages as $page) {
		$associates[$page] = array();
	}

	$pages = array_map(array($freo->pdo, 'quote'), $pages);

	//グループ情報取得
	$stmt = $freo->pdo->query('SELECT group_id, page_id FROM ' . FREO_DATABASE_PREFIX . 'group_sets WHERE page_id IN(' . implode(',', $pages) . ')');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$associates[$data[1]]['group'][$data[0]] = true;
	}

	//フィルター情報取得
	$stmt = $freo->pdo->query('SELECT filter_id, page_id FROM ' . FREO_DATABASE_PREFIX . 'filter_sets WHERE page_id IN(' . implode(',', $pages) . ')');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$associates[$data[1]]['filter'][$data[0]] = true;
	}

	//オプション情報取得
	$stmt = $freo->pdo->query('SELECT option_id, page_id, text FROM ' . FREO_DATABASE_PREFIX . 'option_sets WHERE page_id IN(' . implode(',', $pages) . ')');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$associates[$data[1]]['option'][$data[0]] = $data[2];
	}

	return $associates;
}

/* 関連データ登録 */
function freo_associate_page_post($associates)
{
	global $freo;

	if (empty($associates)) {
		return;
	}

	//グループ登録
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'group_sets WHERE page_id = :id');
	$stmt->bindValue(':id', $associates['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (isset($associates['group'])) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'group_sets VALUES(:group_id, NULL, NULL, :associate_id)');

		foreach ($associates['group'] as $group_id => $value) {
			if ($value == '') {
				continue;
			}

			$stmt->bindValue(':group_id',     $group_id);
			$stmt->bindValue(':associate_id', $associates['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//フィルター登録
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'filter_sets WHERE page_id = :id');
	$stmt->bindValue(':id', $associates['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (isset($associates['filter'])) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'filter_sets VALUES(:filter_id, NULL, :associate_id)');

		foreach ($associates['filter'] as $filter_id => $value) {
			if ($value == '') {
				continue;
			}

			$stmt->bindValue(':filter_id',    $filter_id);
			$stmt->bindValue(':associate_id', $associates['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//オプション登録
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'option_sets WHERE page_id = :id');
	$stmt->bindValue(':id', $associates['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (isset($associates['option'])) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'option_sets VALUES(:option_id, NULL, :associate_id, :value)');

		foreach ($associates['option'] as $option_id => $value) {
			if (is_array($value)) {
				$value = implode("\n", $value);
			}
			if ($value == '') {
				continue;
			}

			$stmt->bindValue(':option_id',    $option_id);
			$stmt->bindValue(':associate_id', $associates['id']);
			$stmt->bindValue(':value',        $value);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	return;
}

/* 関連データ削除 */
function freo_associate_page_delete($page_id)
{
	global $freo;

	if (empty($page_id)) {
		return;
	}

	//グループ削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'group_sets WHERE page_id = :page_id');
	$stmt->bindValue(':page_id', $page_id);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//フィルター削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'filter_sets WHERE page_id = :page_id');
	$stmt->bindValue(':page_id', $page_id);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//オプション削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'option_sets WHERE page_id = :page_id');
	$stmt->bindValue(':page_id', $page_id);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	return;
}

?>
