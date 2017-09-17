<?php

/*********************************************************************

 freo | 管理画面 | ページ削除 (2012/11/04)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';

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
		freo_redirect('admin/page?error=1');
	}

	//権限確認
	if ($freo->user['authority'] != 'root' and $_GET['id']) {
		$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($freo->user['id'] != $data['user_id']) {
				freo_redirect('admin/page?error=1');
			}
		}
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/page?error=1');
	}

	//子カテゴリー確認
	$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		freo_error('子ページを持つページは削除できません。');
	}

	//親ID取得
	$stmt = $freo->pdo->prepare('SELECT pid FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$page = $data;
	} else {
		freo_error('指定されたページが見つかりません。');
	}

	//トラックバック削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'trackbacks WHERE page_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//コメント削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE page_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//オプションファイル削除
	freo_rmdir(FREO_FILE_DIR . 'page_options/' . $_GET['id'] . '/', false);

	//関連データ削除
	freo_associate_page('delete', $_GET['id']);

	//イメージ削除
	freo_rmdir(FREO_FILE_DIR . 'page_images/' . $_GET['id'] . '/', false);

	//サムネイル削除
	freo_rmdir(FREO_FILE_DIR . 'page_thumbnails/' . $_GET['id'] . '/', false);

	//ファイル削除
	freo_rmdir(FREO_FILE_DIR . 'page_files/' . $_GET['id'] . '/', false);

	//ページ削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//連番リセット
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'trackbacks AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'comments AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('ページを削除しました。');

	//ページ管理へ移動
	freo_redirect('admin/page?exec=delete&id=' . $_GET['id'] . ($page['pid'] ? '&pid=' . $page['pid'] : ''));

	return;
}

?>
