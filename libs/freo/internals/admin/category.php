<?php

/*********************************************************************

 freo | 管理画面 | カテゴリー管理 (2011/01/28)

 Copyright(C) 2009-2011 freo.jp

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
	if (!isset($_GET['pid']) or !preg_match('/^[\w\-\/]+$/', $_GET['pid'])) {
		$_GET['pid'] = null;
	}

	//親カテゴリー取得
	$parent = array();
	if ($_GET['pid']) {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'categories WHERE id = :id');
		$stmt->bindValue(':id', $_GET['pid']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$parent = $data;
		}
	}

	//カテゴリー取得
	if ($_GET['pid']) {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'categories WHERE pid = :pid ORDER BY sort, id');
		$stmt->bindValue(':pid', $_GET['pid']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'categories WHERE pid IS NULL ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	$categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'      => freo_token('create'),
		'parent'     => $parent,
		'categories' => $categories
	));

	return;
}

?>
