<?php

/*********************************************************************

 freo | 入力データ検証 | セットアップ (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_setup($mode, $input)
{
	global $freo;

	$errors = array();

	//メールアドレス
	if ($input['setup']['mail'] == '') {
		$errors[] = 'メールアドレスが入力されていません。';
	} elseif (!strpos($input['setup']['mail'], '@')) {
		$errors[] = 'メールアドレスの入力内容が正しくありません。';
	} elseif (mb_strlen($input['setup']['mail'], 'UTF-8') > 80) {
		$errors[] = 'メールアドレスは80文字以内で入力してください。';
	}

	//ユーザーID
	if ($input['setup']['user'] == '') {
		$errors[] = 'ユーザーIDが入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['setup']['user'])) {
		$errors[] = 'ユーザーIDは半角英数字で入力してください。';
	} elseif (preg_match('/^\d+$/', $input['setup']['user'])) {
		$errors[] = 'ユーザーIDには半角英字を含んでください。';
	} elseif (strlen($input['setup']['user']) < 4 or strlen($input['setup']['user']) > 80) {
		$errors[] = 'ユーザーIDは4文字以上80文字以内で入力してください。';
	}

	//パスワード
	if ($input['setup']['password'] == '') {
		$errors[] = 'パスワードが入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['setup']['password'])) {
		$errors[] = 'パスワードは半角英数字で入力してください。';
	} elseif (strlen($input['setup']['password']) < 4 or strlen($input['setup']['password']) > 80) {
		$errors[] = 'パスワードは4文字以上80文字以内で入力してください。';
	}

	return $errors;
}

?>
