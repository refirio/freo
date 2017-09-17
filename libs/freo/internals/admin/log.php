<?php

/*********************************************************************

 freo | 管理画面 | ログ管理 (2010/09/01)

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
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//ログ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'logs ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['view']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['view']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$logs = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$logs[$data['id']] = $data;
	}

	//ログ数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'logs');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data      = $stmt->fetch(PDO::FETCH_NUM);
	$log_count = $data[0];
	$log_page  = ceil($log_count / $freo->config['view']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'     => freo_token('create'),
		'logs'      => $logs,
		'log_count' => $log_count,
		'log_page'  => $log_page
	));

	return;
}

?>
