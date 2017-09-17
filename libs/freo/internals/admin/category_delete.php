<?php

/*********************************************************************

 freo | 管理画面 | カテゴリー削除 (2010/12/07)

 Copyright(C) 2009 freo.jp

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
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		freo_redirect('admin/category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/category?error=1');
	}

	//子カテゴリー確認
	$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'categories WHERE pid = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		freo_error('子カテゴリーを持つカテゴリーは削除できません。');
	}

	//親ID取得
	$stmt = $freo->pdo->prepare('SELECT pid FROM ' . FREO_DATABASE_PREFIX . 'categories WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$category = $data;
	} else {
		freo_error('指定されたカテゴリーが見つかりません。');
	}

	//カテゴリー削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'category_sets WHERE category_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'categories WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('カテゴリーを削除しました。');

	//カテゴリー管理へ移動
	freo_redirect('admin/category?exec=delete&id=' . $_GET['id'] . ($category['pid'] ? '&pid=' . $category['pid'] : ''));

	return;
}

?>
