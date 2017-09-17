<?php

/*********************************************************************

 freo | 管理画面 | コメント削除 (2010/09/01)

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
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('admin/comment?error=1');
	}

	//権限確認
	if ($freo->user['authority'] != 'root') {
		$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (isset($freo->refer['users'][$data['user_id']])) {
				$authority = $freo->refer['users'][$data['user_id']]['authority'];
			} else {
				$authority = null;
			}

			if ($authority == 'root' or ($authority == 'author' and $freo->user['id'] != $data['user_id'])) {
				freo_redirect('admin/comment?error=1');
			}
		}
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/comment?error=1');
	}

	//コメント削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
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
	}

	//ログ記録
	freo_log('コメントを削除しました。');

	//コメント管理へ移動
	freo_redirect('admin/comment?exec=delete&id=' . $_GET['id']);

	return;
}

?>
