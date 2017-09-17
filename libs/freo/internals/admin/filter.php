<?php

/*********************************************************************

 freo | 管理画面 | フィルター管理 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//フィルター取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'filters ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$filters = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$filters[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'   => freo_token('create'),
		'filters' => $filters
	));

	return;
}

?>
