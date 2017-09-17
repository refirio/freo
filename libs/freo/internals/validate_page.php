<?php

/*********************************************************************

 freo | 入力データ検証 | ページ登録 (2011/10/22)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_page($mode, $input)
{
	global $freo;

	$errors = array();

	//ページID
	if ($input['page']['id'] == '') {
		$errors[] = 'ページIDが入力されていません。';
	} elseif (!preg_match('/^[\w\-\/]+$/', $input['page']['id'])) {
		$errors[] = 'ページIDは半角英数字で入力してください。';
	} elseif (preg_match('/^\d+$/', $input['page']['id'])) {
		$errors[] = 'ページIDには半角英字を含んでください。';
	} elseif (mb_strlen($input['page']['id'], 'UTF-8') > 80) {
		$errors[] = 'ページIDは80文字以内で入力してください。';
	} elseif ($mode == 'insert') {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
		$stmt->bindValue(':id', $input['page']['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$errors[] = '入力されたページIDはすでに使用されています。';
		}
	}

	//親ID
	if ($input['page']['pid'] != '') {
		if (!preg_match('/^[\w\-\/]+$/', $input['page']['pid'])) {
			$errors[] = '親IDは半角英数字で入力してください。';
		} elseif (preg_match('/^\d+$/', $input['page']['pid'])) {
			$errors[] = '親IDには半角英字を含んでください。';
		} elseif (mb_strlen($input['page']['pid'], 'UTF-8') > 80) {
			$errors[] = '親IDは80文字以内で入力してください。';
		}

		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :pid');
		$stmt->bindValue(':pid', $input['page']['pid']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
			$errors[] = '入力された親IDは存在しません。';
		}
	}

	//パスワード
	if ($input['page']['restriction'] == 'password') {
		if ($input['page']['password'] == '') {
			$errors[] = 'パスワードが入力されていません。';
		} elseif (!preg_match('/^[\w\-]+$/', $input['page']['password'])) {
			$errors[] = 'パスワードは半角英数字で入力してください。';
		} elseif (strlen($input['page']['password']) < 4 or strlen($input['page']['password']) > 80) {
			$errors[] = 'パスワードは4文字以上80文字以内で入力してください。';
		}
	}

	//状態
	if ($input['page']['status'] == '') {
		$errors[] = '状態が入力されていません。';
	}

	//コメントの受付
	if ($input['page']['comment'] == '') {
		$errors[] = 'コメントの受付が入力されていません。';
	}

	//トラックバックの受付
	if ($input['page']['trackback'] == '') {
		$errors[] = 'トラックバックの受付が入力されていません。';
	}

	//並び順
	if ($input['page']['sort'] == '') {
		$errors[] = '並び順が入力されていません。';
	} elseif (!preg_match('/^\d+$/', $input['page']['sort'])) {
		$errors[] = '並び順は半角数字で入力してください。';
	} elseif (mb_strlen($input['page']['sort'], 'UTF-8') > 10) {
		$errors[] = '並び順は10文字以内で入力してください。';
	}

	//タイトル
	if ($input['page']['title'] == '') {
		$errors[] = 'タイトルが入力されていません。';
	} elseif (mb_strlen($input['page']['title'], 'UTF-8') > 80) {
		$errors[] = 'タイトルは80文字以内で入力してください。';
	}

	//タグ
	if (mb_strlen($input['page']['tag'], 'UTF-8') > 80) {
		$errors[] = 'タグは80文字以内で入力してください。';
	}

	//日時
	if ($input['page']['datetime'] == '') {
		$errors[] = '日時が入力されていません。';
	} elseif (!preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $input['page']['datetime'])) {
		$errors[] = '日時の書式が不正です。';
	}

	//公開終了日時
	if ($input['page']['close'] != '') {
		if (!preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $input['page']['close'])) {
			$errors[] = '公開終了日時の書式が不正です。';
		}
	}

	//ファイル
	if ($input['page']['file'] != '') {
		if (!$freo->config['page']['filename'] and !preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $input['page']['file'])) {
			$errors[] = 'ファイル名は半角英数字で入力してください。';
		} elseif (!$freo->config['page']['filename'] and mb_strlen($input['page']['file'], 'UTF-8') > 80) {
			$errors[] = 'ファイル名は80文字以内で入力してください。';
		}
	}

	//ファイルのイメージ
	if ($input['page']['image'] != '') {
		if (!$freo->config['page']['filename'] and !preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $input['page']['image'])) {
			$errors[] = 'ファイルのイメージ名は半角英数字で入力してください。';
		} elseif (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $input['page']['image'])) {
			$errors[] = 'アップロードできるファイルのイメージはGIF、JPEG、PNGのみです。';
		} elseif (!$freo->config['page']['filename'] and mb_strlen($input['page']['image'], 'UTF-8') > 80) {
			$errors[] = 'ファイルのイメージ名は80文字以内で入力してください。';
		}
	}

	//ファイルの説明
	if (mb_strlen($input['page']['memo'], 'UTF-8') > 80) {
		$errors[] = 'ファイルの説明は80文字以内で入力してください。';
	}

	//グループ
	if ($input['page']['restriction'] == 'group') {
		if (empty($input['page_associate']['group'])) {
			$errors[] = 'グループが入力されていません。';
		}
	}

	//オプション
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE target IS NULL OR target = \'page\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$options = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($data['required'] == 'yes' and (!isset($input['page_associate']['option'][$data['id']]) or $input['page_associate']['option'][$data['id']] == '')) {
			$errors[] = $data['name'] . 'が入力されていません。';
		} elseif ($data['validate'] == 'numeric' and $input['page_associate']['option'][$data['id']] != '' and !preg_match('/^\d+$/', $input['page_associate']['option'][$data['id']])) {
			$errors[] = $data['name'] . 'は半角数字で入力してください。';
		} elseif ($data['validate'] == 'alphabet' and $input['page_associate']['option'][$data['id']] != '' and !preg_match('/^[!-~]+$/', $input['page_associate']['option'][$data['id']])) {
			$errors[] = $data['name'] . 'は半角英数字で入力してください。';
		} elseif (isset($input['page_associate']['option'][$data['id']]) and mb_strlen($input['page_associate']['option'][$data['id']], 'UTF-8') > 5000) {
			$errors[] = $data['name'] . 'は5000文字以内で入力してください。';
		}
	}

	return $errors;
}

?>
