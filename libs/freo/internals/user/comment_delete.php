<?php

/*********************************************************************

 freo | ユーザー用画面 | コメント削除 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('user?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('user?error=1');
	}

	//コメント削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE id = :id AND user_id = :user_id');
	$stmt->bindValue(':id',      $_GET['id'], PDO::PARAM_INT);
	$stmt->bindValue(':user_id', $freo->user['id']);
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
	freo_redirect('user?exec=delete&id=' . $_GET['id']);

	return;
}

?>
