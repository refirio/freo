<?php

/*********************************************************************

 freo | 入力データ検証 | ログイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_login($mode, $input)
{
	global $freo;

	$errors = array();

	//ユーザーID
	if ($input['freo']['user'] == '') {
		$errors[] = 'ユーザーIDが入力されていません。';
	}

	//パスワード
	if ($input['freo']['password'] == '') {
		$errors[] = 'パスワードが入力されていません。';
	}

	//ログイン状態
	if (!$errors) {
		if (!$freo->user['id']) {
			$errors[] = 'ユーザーIDもしくはパスワードが違います。';
		}
	}

	return $errors;
}

?>
