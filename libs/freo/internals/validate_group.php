<?php

/*********************************************************************

 freo | 入力データ検証 | グループ登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_group($mode, $input)
{
	global $freo;

	$errors = array();

	//グループID
	if ($input['group']['id'] == '') {
		$errors[] = 'グループIDが入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['group']['id'])) {
		$errors[] = 'グループIDは半角英数字で入力してください。';
	} elseif (preg_match('/^\d+$/', $input['group']['id'])) {
		$errors[] = 'グループIDには半角英字を含んでください。';
	} elseif (mb_strlen($input['group']['id'], 'UTF-8') > 80) {
		$errors[] = 'グループIDは80文字以内で入力してください。';
	} elseif ($mode == 'insert') {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'groups WHERE id = :id');
		$stmt->bindValue(':id', $input['group']['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$errors[] = '入力されたグループIDはすでに使用されています。';
		}
	}

	//並び順
	if ($input['group']['sort'] == '') {
		$errors[] = '並び順が入力されていません。';
	} elseif (!preg_match('/^\d+$/', $input['group']['sort'])) {
		$errors[] = '並び順は半角数字で入力してください。';
	} elseif (mb_strlen($input['group']['sort'], 'UTF-8') > 10) {
		$errors[] = '並び順は10文字以内で入力してください。';
	}

	//グループ名
	if ($input['group']['name'] == '') {
		$errors[] = 'グループ名が入力されていません。';
	} elseif (mb_strlen($input['group']['name'], 'UTF-8') > 80) {
		$errors[] = 'グループ名は80文字以内で入力してください。';
	}

	//説明
	if (mb_strlen($input['group']['memo'], 'UTF-8') > 5000) {
		$errors[] = '説明は5000文字以内で入力してください。';
	}

	return $errors;
}

?>
