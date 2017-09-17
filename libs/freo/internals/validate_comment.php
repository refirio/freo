<?php

/*********************************************************************

 freo | 入力データ検証 | コメント登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_comment($mode, $input)
{
	global $freo;

	$errors = array();

	//名前
	if (!$freo->user['id']) {
		if ($input['comment']['name'] == '') {
			$errors[] = '名前が入力されていません。';
		} elseif (mb_strlen($input['comment']['name'], 'UTF-8') > 80) {
			$errors[] = '名前は80文字以内で入力してください。';
		}
	}

	//メールアドレス
	if (!$freo->user['id']) {
		if ($input['comment']['mail'] != '' and !strpos($input['comment']['mail'], '@')) {
			$errors[] = 'メールアドレスの入力内容が正しくありません。';
		} elseif (mb_strlen($input['comment']['mail'], 'UTF-8') > 80) {
			$errors[] = 'メールアドレスは80文字以内で入力してください。';
		}
	}

	//URL
	if (!$freo->user['id']) {
		if (mb_strlen($input['comment']['url'], 'UTF-8') > 200) {
			$errors[] = 'URLは200文字以内で入力してください。';
		}
	}

	//本文
	if ($input['comment']['text'] == '') {
		$errors[] = '本文が入力されていません。';
	} elseif (mb_strlen($input['comment']['text'], 'UTF-8') > 5000) {
		$errors[] = '本文は5000文字以内で入力してください。';
	}

	return $errors;
}

?>
