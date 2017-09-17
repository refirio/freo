<?php

/*********************************************************************

 freo | ユーザー用画面 | プロフィール入力内容確認 (2010/09/01)

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

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('user?error=1', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('user/profile_preview', true);
		}

		//登録処理へ移動
		freo_redirect('user/profile_post?freo%5Btoken%5D=' . freo_token('create'), true);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'user'  => $_SESSION['input']['user']
	));

	return;
}

?>
