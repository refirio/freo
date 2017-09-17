<?php

/*********************************************************************

 freo | 入力データ検証 | カテゴリー登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_category($mode, $input)
{
	global $freo;

	$errors = array();

	//カテゴリーID
	if ($input['category']['id'] == '') {
		$errors[] = 'カテゴリーIDが入力されていません。';
	} elseif (!preg_match('/^[\w\-\/]+$/', $input['category']['id'])) {
		$errors[] = 'カテゴリーIDは半角英数字で入力してください。';
	} elseif (preg_match('/^\d+$/', $input['category']['id'])) {
		$errors[] = 'カテゴリーIDには半角英字を含んでください。';
	} elseif (mb_strlen($input['category']['id'], 'UTF-8') > 80) {
		$errors[] = 'カテゴリーIDは80文字以内で入力してください。';
	} elseif ($mode == 'insert') {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'categories WHERE id = :id');
		$stmt->bindValue(':id', $input['category']['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$errors[] = '入力されたカテゴリーIDはすでに使用されています。';
		}
	}

	//親ID
	if ($input['category']['pid'] != '') {
		if (!preg_match('/^[\w\-\/]+$/', $input['category']['pid'])) {
			$errors[] = '親IDは半角英数字で入力してください。';
		} elseif (preg_match('/^\d+$/', $input['category']['pid'])) {
			$errors[] = '親IDには半角英字を含んでください。';
		} elseif (mb_strlen($input['category']['pid'], 'UTF-8') > 80) {
			$errors[] = '親IDは80文字以内で入力してください。';
		}

		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'categories WHERE id = :pid');
		$stmt->bindValue(':pid', $input['category']['pid']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
			$errors[] = '入力された親IDは存在しません。';
		}
	}

	//並び順
	if ($input['category']['sort'] == '') {
		$errors[] = '並び順が入力されていません。';
	} elseif (!preg_match('/^\d+$/', $input['category']['sort'])) {
		$errors[] = '並び順は半角数字で入力してください。';
	} elseif (mb_strlen($input['category']['sort'], 'UTF-8') > 10) {
		$errors[] = '並び順は10文字以内で入力してください。';
	}

	//カテゴリー名
	if ($input['category']['name'] == '') {
		$errors[] = 'カテゴリー名が入力されていません。';
	} elseif (mb_strlen($input['category']['name'], 'UTF-8') > 80) {
		$errors[] = 'カテゴリー名は80文字以内で入力してください。';
	}

	//説明
	if (mb_strlen($input['category']['memo'], 'UTF-8') > 5000) {
		$errors[] = '説明は5000文字以内で入力してください。';
	}

	return $errors;
}

?>
