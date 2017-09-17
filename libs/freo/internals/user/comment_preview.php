<?php

/*********************************************************************

 freo | ユーザー用画面 | コメント入力内容確認 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = 0;
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('user/comment?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('user/comment_preview?id=' . $_GET['id']);
		}

		//登録処理へ移動
		freo_redirect('user/comment_post?freo%5Btoken%5D=' . freo_token('create') . '&id=' . $_GET['id']);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'   => freo_token('create'),
		'comment' => $_SESSION['input']['comment']
	));

	return;
}

?>
