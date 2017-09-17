<?php

/*********************************************************************

 freo | 管理画面 | ユーザー削除 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('admin/user?error=1');
	}
	if ($_GET['id'] and $freo->user['id'] == $_GET['id']) {
		freo_redirect('admin/user?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/user?error=1');
	}

	if ($_GET['article'] == 'delete') {
		//コメント削除
		$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE user_id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//ページ削除
		$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE user_id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//エントリー削除
		$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE user_id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//関連データ削除
		freo_associate_user('delete', $_GET['id']);

		//ユーザー削除
		$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//連番リセット
		if (FREO_DATABASE_TYPE == 'mysql') {
			$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'comments AUTO_INCREMENT = 0');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}

			$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'entries AUTO_INCREMENT = 0');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
		}
	} else {
		//コメント更新
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'comments SET user_id = :user WHERE user_id = :id');
		$stmt->bindValue(':user', $_GET['user']);
		$stmt->bindValue(':id',   $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//ページ更新
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET user_id = :user WHERE user_id = :id');
		$stmt->bindValue(':user', $_GET['user']);
		$stmt->bindValue(':id',   $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//エントリー更新
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'entries SET user_id = :user WHERE user_id = :id');
		$stmt->bindValue(':user', $_GET['user']);
		$stmt->bindValue(':id',   $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//ユーザー削除
		$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//ログ記録
	freo_log('ユーザーを削除しました。');

	//ユーザー管理へ移動
	freo_redirect('admin/user?exec=delete&id=' . $_GET['id']);

	return;
}

?>
