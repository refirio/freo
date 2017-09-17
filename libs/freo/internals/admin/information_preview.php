<?php

/*********************************************************************

 freo | 管理画面 | インフォメーション入力内容確認 (2011/04/23)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('admin/information_form?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('admin/information_preview');
		}

		//登録処理へ移動
		freo_redirect('admin/information_post?freo%5Btoken%5D=' . freo_token('create'));
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'            => freo_token('create'),
		'information'      => $_SESSION['input']['information'],
		'information_text' => $_SESSION['input']['information_text']
	));

	return;
}

?>
