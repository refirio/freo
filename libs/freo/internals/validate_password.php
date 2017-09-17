<?php

/*********************************************************************

 freo | 入力データ検証 | パスワード登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_password($mode, $input)
{
	global $freo;

	$errors = array();

	//以前のパスワード
	if ($input['password']['old'] == '') {
		$errors[] = '以前のパスワードが入力されていません。';
	} else {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :user AND password = :old');
		$stmt->bindValue(':user', $freo->user['id']);
		$stmt->bindValue(':old',  md5($input['password']['old']));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
			$errors[] = '以前のパスワードが違います。';
		}
	}

	//新しいパスワード
	if ($input['password']['new'] == '') {
		$errors[] = '新しいパスワードが入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['password']['new'])) {
		$errors[] = '新しいパスワードは半角英数字で入力してください。';
	} elseif (strlen($input['password']['new']) < 4 or strlen($input['password']['new']) > 80) {
		$errors[] = '新しいパスワードは4文字以上80文字以内で入力してください。';
	}

	//新しいパスワード（確認入力）
	if ($input['password']['new'] == '') {
		$errors[] = '確認入力パスワードが入力されていません。';
	} elseif ($input['password']['confirm'] != $input['password']['new']) {
		$errors[] = '確認入力パスワードと新しいパスワードが一致しません。';
	}

	return $errors;
}

?>
