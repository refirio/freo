<?php

/*********************************************************************

 freo | 管理画面 | ユーザー入力内容確認 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('admin/user?error=1', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('admin/user_preview' . ($_GET['id'] ? '?id=' . $_GET['id'] : ''), true);
		}

		//登録処理へ移動
		freo_redirect('admin/user_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''), true);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'user'           => $_SESSION['input']['user'],
		'user_associate' => $_SESSION['input']['user_associate']
	));

	return;
}

?>
