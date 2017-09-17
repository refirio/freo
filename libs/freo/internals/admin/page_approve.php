<?php

/*********************************************************************

 freo | 管理画面 | ページ承認 (2010/09/01)

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
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		freo_redirect('admin/page?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/page?error=1');
	}

	//ページ承認
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET modified = :now, approved = :approved WHERE id = :id');
	$stmt->bindValue(':now',      date('Y-m-d H:i:s'));
	$stmt->bindValue(':approved', 'yes');
	$stmt->bindValue(':id',       $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('ページを承認しました。');

	//ページ管理へ移動
	freo_redirect('admin/page?exec=approve&id=' . $_GET['id']);

	return;
}

?>
