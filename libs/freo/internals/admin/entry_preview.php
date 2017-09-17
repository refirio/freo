<?php

/*********************************************************************

 freo | 管理画面 | エントリー入力内容確認 (2011/05/11)

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
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = 0;
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('admin/entry?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('admin/entry_preview' . ($_GET['id'] ? '?id=' . $_GET['id'] : ''));
		}

		//登録処理へ移動
		freo_redirect('admin/entry_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'           => freo_token('create'),
		'entry'           => $_SESSION['preview']['entry'],
		'entry_associate' => $_SESSION['preview']['entry_associate'],
		'entry_tags'      => $_SESSION['preview']['entry_tags'],
		'entry_file'      => $_SESSION['preview']['entry_file'],
		'entry_thumbnail' => $_SESSION['preview']['entry_thumbnail'],
		'entry_image'     => $_SESSION['preview']['entry_image'],
		'entry_text'      => $_SESSION['preview']['entry_text'],
		'entry_path'      => $_SESSION['preview']['entry_path']
	));

	return;
}

?>
