<?php

/*********************************************************************

 freo | 入力データ検証 | フィルター登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_filter($mode, $input)
{
	global $freo;

	$errors = array();

	//フィルターID
	if ($input['filter']['id'] == '') {
		$errors[] = 'フィルターIDが入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['filter']['id'])) {
		$errors[] = 'フィルターIDは半角英数字で入力してください。';
	} elseif (preg_match('/^\d+$/', $input['filter']['id'])) {
		$errors[] = 'フィルターIDには半角英字を含んでください。';
	} elseif (mb_strlen($input['filter']['id'], 'UTF-8') > 80) {
		$errors[] = 'フィルターIDは80文字以内で入力してください。';
	} elseif ($mode == 'insert') {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'filters WHERE id = :id');
		$stmt->bindValue(':id', $input['filter']['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$errors[] = '入力されたフィルターIDはすでに使用されています。';
		}
	}

	//並び順
	if ($input['filter']['sort'] == '') {
		$errors[] = '並び順が入力されていません。';
	} elseif (!preg_match('/^\d+$/', $input['filter']['sort'])) {
		$errors[] = '並び順は半角数字で入力してください。';
	} elseif (mb_strlen($input['filter']['sort'], 'UTF-8') > 10) {
		$errors[] = '並び順は10文字以内で入力してください。';
	}

	//フィルター名
	if ($input['filter']['name'] == '') {
		$errors[] = 'フィルター名が入力されていません。';
	} elseif (mb_strlen($input['filter']['name'], 'UTF-8') > 80) {
		$errors[] = 'フィルター名は80文字以内で入力してください。';
	}

	//説明
	if (mb_strlen($input['filter']['memo'], 'UTF-8') > 5000) {
		$errors[] = '説明は5000文字以内で入力してください。';
	}

	return $errors;
}

?>
