<?php

/*********************************************************************

 freo | 管理画面 | フィルター削除 (2010/09/01)

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

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('admin/filter?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/filter?error=1');
	}

	//フィルター削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'filter_sets WHERE filter_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'filters WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('フィルターを削除しました。');

	//フィルター管理へ移動
	freo_redirect('admin/filter?exec=delete&id=' . $_GET['id']);

	return;
}

?>
