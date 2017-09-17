<?php

/*********************************************************************

 freo | 管理画面 | エントリー承認 (2010/09/01)

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
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('admin/entry?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/entry?error=1');
	}

	//エントリー承認
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'entries SET modified = :now, approved = :approved WHERE id = :id');
	$stmt->bindValue(':now',      date('Y-m-d H:i:s'));
	$stmt->bindValue(':approved', 'yes');
	$stmt->bindValue(':id',       $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('エントリーを承認しました。');

	//エントリー管理へ移動
	freo_redirect('admin/entry?exec=approve&id=' . $_GET['id']);

	return;
}

?>
