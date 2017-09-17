<?php

/*********************************************************************

 freo | 関連データ取得 | ユーザー (2010/10/29)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 関連データ */
function freo_associate_user($mode, $data)
{
	global $freo;

	if ($mode == 'get') {
		return freo_associate_user_get($data);
	} elseif ($mode == 'post') {
		freo_associate_user_post($data);
	} elseif ($mode == 'delete') {
		freo_associate_user_delete($data);
	}

	return;
}

/* 関連データ取得 */
function freo_associate_user_get($users)
{
	global $freo;

	if (empty($users)) {
		return array();
	}

	//データ初期化
	$associates = array();
	foreach ($users as $user) {
		$associates[$user] = array();
	}

	$users = array_map(array($freo->pdo, 'quote'), $users);

	//グループ情報取得
	$stmt = $freo->pdo->query('SELECT group_id, user_id FROM ' . FREO_DATABASE_PREFIX . 'group_sets WHERE user_id IN(' . implode(',', $users) . ')');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$associates[$data[1]]['group'][$data[0]] = true;
	}

	return $associates;
}

/* 関連データ登録 */
function freo_associate_user_post($associates)
{
	global $freo;

	if (empty($associates)) {
		return;
	}

	//グループ登録
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'group_sets WHERE user_id = :id');
	$stmt->bindValue(':id', $associates['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (isset($associates['group'])) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'group_sets VALUES(:group_id, :user_id, NULL, NULL)');

		foreach ($associates['group'] as $group_id => $value) {
			if ($value == '') {
				continue;
			}

			$stmt->bindValue(':group_id', $group_id);
			$stmt->bindValue(':user_id',  $associates['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	return;
}

/* 関連データ削除 */
function freo_associate_user_delete($user_id)
{
	global $freo;

	if (empty($user_id)) {
		return;
	}

	//グループ削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'group_sets WHERE user_id = :user_id');
	$stmt->bindValue(':user_id', $user_id);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	return;
}

?>
