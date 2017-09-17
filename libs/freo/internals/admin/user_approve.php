<?php

/*********************************************************************

 freo | 管理画面 | ユーザー承認 (2010/11/15)

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

	//ユーザー承認
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET modified = :now, approved = :approved WHERE id = :id');
	$stmt->bindValue(':now',      date('Y-m-d H:i:s'));
	$stmt->bindValue(':approved', 'yes');
	$stmt->bindValue(':id',       $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('ユーザーを承認しました。');

	//ユーザー管理へ移動
	freo_redirect('admin/user?exec=approve&id=' . $_GET['id']);

	return;
}

?>
