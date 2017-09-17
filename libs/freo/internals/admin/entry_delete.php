<?php

/*********************************************************************

 freo | 管理画面 | エントリー削除 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';

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
		freo_redirect('admin/entry?error=1');
	}

	//権限確認
	if ($freo->user['authority'] != 'root' and $_GET['id']) {
		$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($freo->user['id'] != $data['user_id']) {
				freo_redirect('admin/entry?error=1');
			}
		}
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/entry?error=1');
	}

	//トラックバック削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'trackbacks WHERE entry_id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//コメント削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE entry_id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//オプションファイル削除
	freo_rmdir(FREO_FILE_DIR . 'entry_options/' . $_GET['id'] . '/');

	//関連データ削除
	freo_associate_entry('delete', $_GET['id']);

	//イメージ削除
	freo_rmdir(FREO_FILE_DIR . 'entry_images/' . $_GET['id'] . '/');

	//サムネイル削除
	freo_rmdir(FREO_FILE_DIR . 'entry_thumbnails/' . $_GET['id'] . '/');

	//ファイル削除
	freo_rmdir(FREO_FILE_DIR . 'entry_files/' . $_GET['id'] . '/');

	//エントリー削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
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

		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'entries AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('エントリーを削除しました。');

	//エントリー管理へ移動
	freo_redirect('admin/entry?exec=delete&id=' . $_GET['id']);

	return;
}

?>
