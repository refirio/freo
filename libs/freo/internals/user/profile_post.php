<?php

/*********************************************************************

 freo | ユーザー用画面 | プロフィール登録 (2010/09/01)

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

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('user?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('user?error=1', true);
	}

	//入力データ取得
	$user = $_SESSION['input']['user'];

	if ($user['url'] == '') {
		$user['url'] = null;
	}
	if ($user['text'] == '') {
		$user['text'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET modified = :now, name = :name, mail = :mail, url = :url, text = :text WHERE id = :id');
	$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':name', $user['name']);
	$stmt->bindValue(':mail', $user['mail']);
	$stmt->bindValue(':url',  $user['url']);
	$stmt->bindValue(':text', $user['text']);
	$stmt->bindValue(':id',   $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('プロフィールを編集しました。');

	//プロフィール入力へ移動
	freo_redirect('user/profile_form?exec=update', true);

	return;
}

?>
