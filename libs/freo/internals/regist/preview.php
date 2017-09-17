<?php

/*********************************************************************

 freo | ユーザー登録 | 入力内容確認 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//新規登録チェック
	if ($freo->user['id'] or !$freo->config['user']['regist']) {
		freo_redirect('default');
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('regist/form?error=1', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('regist/preview', true);
		}

		//登録処理へ移動
		freo_redirect('regist/post?freo%5Btoken%5D=' . freo_token('create'), true);
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
