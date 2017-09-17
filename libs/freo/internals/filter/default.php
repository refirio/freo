<?php

/*********************************************************************

 freo | フィルター設定 (2012/12/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//フィルターチェック
	if ($freo->user['authority'] == 'root' or $freo->user['authority'] == 'author' or (!$freo->config['entry']['filter'] and !$freo->config['page']['filter'] and !$freo->config['media']['filter'])) {
		freo_redirect('default');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		$_SESSION['input'] = $_POST;

		//準備処理へ移動
		freo_redirect('filter/post?freo%5Btoken%5D=' . freo_token('create'));
	} else {
		//編集データ取得
		if (isset($_SESSION['filter'])) {
			$filter = $_SESSION['filter'];
		} else {
			$filter = array();
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'filter' => $filter
		)
	));

	return;
}

?>
