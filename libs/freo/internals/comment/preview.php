<?php

/*********************************************************************

 freo | コメント | 入力内容確認 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//パラメータ検証
	if ((!isset($_GET['entry_id']) or !preg_match('/^\d+$/', $_GET['entry_id']) or $_GET['entry_id'] < 1) and (!isset($_GET['page_id']) or !preg_match('/^[\w\-\/]+$/', $_GET['page_id']))) {
		freo_redirect('default');
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		if (isset($_GET['entry_id'])) {
			freo_redirect('view/' . $_GET['entry_id'] . '?error=1');
		} else {
			freo_redirect('page/' . $_GET['page_id'] . '?error=1');
		}
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			if (isset($_GET['entry_id'])) {
				freo_redirect('comment/preview?entry_id=' . $_GET['entry_id']);
			} else {
				freo_redirect('comment/preview?page_id=' . $_GET['page_id']);
			}
		}

		//登録処理へ移動
		freo_redirect('comment/post?freo%5Btoken%5D=' . freo_token('create') . (isset($_GET['entry_id']) ? '&entry_id=' . $_GET['entry_id'] : '&page_id=' . $_GET['page_id']));
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'   => freo_token('create'),
		'comment' => $_SESSION['input']['comment']
	));

	return;
}

?>
