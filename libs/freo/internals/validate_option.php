<?php

/*********************************************************************

 freo | 入力データ検証 | オプション登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_option($mode, $input)
{
	global $freo;

	$errors = array();

	//オプションID
	if ($input['option']['id'] == '') {
		$errors[] = 'オプションIDが入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['option']['id'])) {
		$errors[] = 'オプションIDは半角英数字で入力してください。';
	} elseif (preg_match('/^\d+$/', $input['option']['id'])) {
		$errors[] = 'オプションIDには半角英字を含んでください。';
	} elseif (mb_strlen($input['option']['id'], 'UTF-8') > 80) {
		$errors[] = 'オプションIDは80文字以内で入力してください。';
	} elseif ($mode == 'insert') {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE id = :id');
		$stmt->bindValue(':id', $input['option']['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$errors[] = '入力されたオプションIDはすでに使用されています。';
		}
	}

	//利用対象
	if ($input['option']['target'] != '') {
		if (!preg_match('/^[\w\-]+$/', $input['option']['target'])) {
			$errors[] = '利用対象は半角英数字で入力してください。';
		} elseif (mb_strlen($input['option']['target'], 'UTF-8') > 20) {
			$errors[] = '利用対象は20文字以内で入力してください。';
		}
	}

	//種類
	if ($input['option']['type'] == '') {
		$errors[] = '種類が入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['option']['type'])) {
		$errors[] = '種類は半角英数字で入力してください。';
	} elseif (mb_strlen($input['option']['type'], 'UTF-8') > 20) {
		$errors[] = '種類は20文字以内で入力してください。';
	}

	//必須
	if ($input['option']['required'] == '') {
		$errors[] = '必須が入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['option']['required'])) {
		$errors[] = '必須は半角英数字で入力してください。';
	} elseif (mb_strlen($input['option']['required'], 'UTF-8') > 20) {
		$errors[] = '必須は20文字以内で入力してください。';
	}

	//並び順
	if ($input['option']['sort'] == '') {
		$errors[] = '並び順が入力されていません。';
	} elseif (!preg_match('/^\d+$/', $input['option']['sort'])) {
		$errors[] = '並び順は半角数字で入力してください。';
	} elseif (mb_strlen($input['option']['sort'], 'UTF-8') > 10) {
		$errors[] = '並び順は10文字以内で入力してください。';
	}

	//オプション名
	if ($input['option']['name'] == '') {
		$errors[] = 'オプション名が入力されていません。';
	} elseif (mb_strlen($input['option']['name'], 'UTF-8') > 80) {
		$errors[] = 'オプション名は80文字以内で入力してください。';
	}

	//説明
	if (mb_strlen($input['option']['memo'], 'UTF-8') > 5000) {
		$errors[] = '説明は5000文字以内で入力してください。';
	}

	//初期値
	if (mb_strlen($input['option']['text'], 'UTF-8') > 5000) {
		$errors[] = '初期値は5000文字以内で入力してください。';
	}

	return $errors;
}

?>
