<?php

/*********************************************************************

 freo | 管理画面 | ユーザー管理 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//ユーザー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users ORDER BY id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$users = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$users[$data['id']] = $data;
	}

	//ユーザーID取得
	$user_keys = array_keys($users);

	//ユーザー関連データ取得
	$user_associates = freo_associate_user('get', $user_keys);

	//データ割当
	$freo->smarty->assign(array(
		'token'           => freo_token('create'),
		'users'           => $users,
		'user_associates' => $user_associates
	));

	return;
}

?>
