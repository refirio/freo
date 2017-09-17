<?php

/*********************************************************************

 freo | 入力データ検証 | ユーザー登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_user($mode, $input)
{
	global $freo;

	$errors = array();

	//ユーザーID
	if ($mode == 'insert') {
		if ($input['user']['id'] == '') {
			$errors[] = 'ユーザーIDが入力されていません。';
		} elseif (!preg_match('/^[\w\-]+$/', $input['user']['id'])) {
			$errors[] = 'ユーザーIDは半角英数字で入力してください。';
		} elseif (preg_match('/^\d+$/', $input['user']['id'])) {
			$errors[] = 'ユーザーIDには半角英字を含んでください。';
		} elseif (strlen($input['user']['id']) < 4 or strlen($input['user']['id']) > 80) {
			$errors[] = 'ユーザーIDは4文字以上80文字以内で入力してください。';
		} elseif ($mode == 'insert') {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
			$stmt->bindValue(':id', $input['user']['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$errors[] = '入力されたユーザーIDはすでに使用されています。';
			}
		}
	}

	//パスワード
	if ($mode == 'insert') {
		if ($input['user']['password'] == '') {
			$errors[] = 'パスワードが入力されていません。';
		} elseif (!preg_match('/^[\w\-]+$/', $input['user']['password'])) {
			$errors[] = 'パスワードは半角英数字で入力してください。';
		} elseif (strlen($input['user']['password']) < 4 or strlen($input['user']['password']) > 80) {
			$errors[] = 'パスワードは4文字以上80文字以内で入力してください。';
		}
	}

	//名前
	if ($input['user']['name'] == '') {
		$errors[] = '名前が入力されていません。';
	} elseif (mb_strlen($input['user']['name'], 'UTF-8') > 80) {
		$errors[] = '名前は80文字以内で入力してください。';
	}

	//メールアドレス
	if ($input['user']['mail'] == '') {
		$errors[] = 'メールアドレスが入力されていません。';
	} elseif (!strpos($input['user']['mail'], '@')) {
		$errors[] = 'メールアドレスの入力内容が正しくありません。';
	} elseif (mb_strlen($input['user']['mail'], 'UTF-8') > 80) {
		$errors[] = 'メールアドレスは80文字以内で入力してください。';
	} else {
		if ($mode == 'insert') {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE mail = :mail');
			$stmt->bindValue(':mail', $input['user']['mail']);
		} elseif ($mode == 'update') {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id <> :id AND mail = :mail');
			$stmt->bindValue(':id',   $input['user']['id']);
			$stmt->bindValue(':mail', $input['user']['mail']);
		}
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$errors[] = '入力されたメールアドレスはすでに使用されています。';
		}
	}

	//URL
	if (mb_strlen($input['user']['url'], 'UTF-8') > 200) {
		$errors[] = 'URLは200文字以内で入力してください。';
	}

	//紹介文
	if (mb_strlen($input['user']['text'], 'UTF-8') > 5000) {
		$errors[] = '紹介文は5000文字以内で入力してください。';
	}

	return $errors;
}

?>
