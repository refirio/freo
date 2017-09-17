<?php

/*********************************************************************

 freo | 管理画面 | パスワード登録 (2010/09/01)

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

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('admin/password_form?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/password_form?error=1', true);
	}

	//入力データ取得
	$password = $_SESSION['input']['password'];

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET modified = :now, password = :new WHERE id = :id');
	$stmt->bindValue(':now', date('Y-m-d H:i:s'));
	$stmt->bindValue(':new', md5($password['new']));
	$stmt->bindValue(':id',  $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('パスワードを変更しました。');

	//パスワード入力へ移動
	freo_redirect('admin/password_form?exec=update', true);

	return;
}

?>
