<?php

/*********************************************************************

 freo | 管理画面 | ページ入力内容確認 (2011/05/11)

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

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('admin/page?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('admin/page_preview' . ($_GET['id'] ? '?id=' . $_GET['id'] : ''));
		}

		//登録処理へ移動
		freo_redirect('admin/page_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'page'           => $_SESSION['preview']['page'],
		'page_associate' => $_SESSION['preview']['page_associate'],
		'page_tags'      => $_SESSION['preview']['page_tags'],
		'page_file'      => $_SESSION['preview']['page_file'],
		'page_thumbnail' => $_SESSION['preview']['page_thumbnail'],
		'page_image'     => $_SESSION['preview']['page_image'],
		'page_text'      => $_SESSION['preview']['page_text'],
		'page_path'      => $_SESSION['preview']['page_path']
	));

	return;
}

?>
