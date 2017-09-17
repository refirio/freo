<?php

/*********************************************************************

 freo | 入力データ検証 | エントリー登録 (2011/10/22)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_entry($mode, $input)
{
	global $freo;

	$errors = array();

	//パスワード
	if ($input['entry']['restriction'] == 'password') {
		if ($input['entry']['password'] == '') {
			$errors[] = 'パスワードが入力されていません。';
		} elseif (!preg_match('/^[\w\-]+$/', $input['entry']['password'])) {
			$errors[] = 'パスワードは半角英数字で入力してください。';
		} elseif (strlen($input['entry']['password']) < 4 or strlen($input['entry']['password']) > 80) {
			$errors[] = 'パスワードは4文字以上80文字以内で入力してください。';
		}
	}

	//状態
	if ($input['entry']['status'] == '') {
		$errors[] = '状態が入力されていません。';
	}

	//コメントの受付
	if ($input['entry']['comment'] == '') {
		$errors[] = 'コメントの受付が入力されていません。';
	}

	//トラックバックの受付
	if ($input['entry']['trackback'] == '') {
		$errors[] = 'トラックバックの受付が入力されていません。';
	}

	//コード
	if ($input['entry']['code'] != '') {
		if (!preg_match('/^[\w\-]+$/', $input['entry']['code'])) {
			$errors[] = 'コードは半角英数字で入力してください。';
		} elseif (preg_match('/^\d+$/', $input['entry']['code'])) {
			$errors[] = 'コードには半角英字を含んでください。';
		} elseif (mb_strlen($input['entry']['code'], 'UTF-8') > 80) {
			$errors[] = 'コードは80文字以内で入力してください。';
		} else {
			if ($mode == 'insert') {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE code = :code');
				$stmt->bindValue(':code', $input['entry']['code']);
			} elseif ($mode == 'update') {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id <> :id AND code = :code');
				$stmt->bindValue(':id',   $input['entry']['id'], PDO::PARAM_INT);
				$stmt->bindValue(':code', $input['entry']['code']);
			}
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$errors[] = '入力されたコードはすでに使用されています。';
			}
		}
	}

	//タイトル
	if ($input['entry']['title'] == '') {
		$errors[] = 'タイトルが入力されていません。';
	} elseif (mb_strlen($input['entry']['title'], 'UTF-8') > 80) {
		$errors[] = 'タイトルは80文字以内で入力してください。';
	}

	//タグ
	if (mb_strlen($input['entry']['tag'], 'UTF-8') > 80) {
		$errors[] = 'タグは80文字以内で入力してください。';
	}

	//日時
	if ($input['entry']['datetime'] == '') {
		$errors[] = '日時が入力されていません。';
	} elseif (!preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $input['entry']['datetime'])) {
		$errors[] = '日時の書式が不正です。';
	}

	//公開終了日時
	if ($input['entry']['close'] != '') {
		if (!preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $input['entry']['close'])) {
			$errors[] = '公開終了日時の書式が不正です。';
		}
	}

	//ファイル
	if ($input['entry']['file'] != '') {
		if (!$freo->config['entry']['filename'] and !preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $input['entry']['file'])) {
			$errors[] = 'ファイル名は半角英数字で入力してください。';
		} elseif (!$freo->config['entry']['filename'] and mb_strlen($input['entry']['file'], 'UTF-8') > 80) {
			$errors[] = 'ファイル名は80文字以内で入力してください。';
		}
	}

	//ファイルのイメージ
	if ($input['entry']['image'] != '') {
		if (!$freo->config['entry']['filename'] and !preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $input['entry']['image'])) {
			$errors[] = 'ファイルのイメージ名は半角英数字で入力してください。';
		} elseif (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $input['entry']['image'])) {
			$errors[] = 'アップロードできるファイルのイメージはGIF、JPEG、PNGのみです。';
		} elseif (!$freo->config['entry']['filename'] and mb_strlen($input['entry']['image'], 'UTF-8') > 80) {
			$errors[] = 'ファイルのイメージ名は80文字以内で入力してください。';
		}
	}

	//ファイルの説明
	if (mb_strlen($input['entry']['memo'], 'UTF-8') > 80) {
		$errors[] = 'ファイルの説明は80文字以内で入力してください。';
	}

	//グループ
	if ($input['entry']['restriction'] == 'group') {
		if (empty($input['entry_associate']['group'])) {
			$errors[] = 'グループが入力されていません。';
		}
	}

	//オプション
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE target IS NULL OR target = \'entry\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$options = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($data['required'] == 'yes' and (!isset($input['entry_associate']['option'][$data['id']]) or $input['entry_associate']['option'][$data['id']] == '')) {
			$errors[] = $data['name'] . 'が入力されていません。';
		} elseif ($data['validate'] == 'numeric' and $input['entry_associate']['option'][$data['id']] != '' and !preg_match('/^\d+$/', $input['entry_associate']['option'][$data['id']])) {
			$errors[] = $data['name'] . 'は半角数字で入力してください。';
		} elseif ($data['validate'] == 'alphabet' and $input['entry_associate']['option'][$data['id']] != '' and !preg_match('/^[!-~]+$/', $input['entry_associate']['option'][$data['id']])) {
			$errors[] = $data['name'] . 'は半角英数字で入力してください。';
		} elseif (isset($input['entry_associate']['option'][$data['id']]) and mb_strlen($input['entry_associate']['option'][$data['id']], 'UTF-8') > 5000) {
			$errors[] = $data['name'] . 'は5000文字以内で入力してください。';
		}
	}

	return $errors;
}

?>
