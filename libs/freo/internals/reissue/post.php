<?php

/*********************************************************************

 freo | パスワード再発行 | 登録 (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//パスワード作成
	$password = rand(10000, 99999);

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET modified = :now, password = :password WHERE id = :user AND password = :key');
	$stmt->bindValue(':now',      date('Y-m-d H:i:s'));
	$stmt->bindValue(':password', md5($password));
	$stmt->bindValue(':user',     $_GET['user']);
	$stmt->bindValue(':key',      $_GET['key']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ユーザー取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :user');
	$stmt->bindValue(':user', $_GET['user']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$user = $data;
	} else {
		freo_error('指定されたユーザーが見つかりません。', '404 Not Found');
	}

	//メール本文定義
	$mail_header = file_get_contents(FREO_MAIL_DIR . 'internals/reissue/post_header.txt');
	$mail_footer = file_get_contents(FREO_MAIL_DIR . 'internals/reissue/post_footer.txt');

	eval('$mail_header = "' . $mail_header . '";');
	eval('$mail_footer = "' . $mail_footer . '";');

	$message  = $mail_header;
	$message .= 'ユーザー名：' . $_GET['user'] . "\n";
	$message .= 'パスワード：' . $password . "\n";
	$message .= $mail_footer;

	//メール送信
	$flag = freo_mail($user['mail'], 'パスワード再発行', $message);
	if (!$flag) {
		freo_error('メールを送信できません。');
	}

	//ログ記録
	freo_log('パスワードを再発行しました。');

	//ユーザー管理へ移動
	freo_redirect('reissue/complete');

	return;
}

?>
