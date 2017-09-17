<?php

/*********************************************************************

 freo | パスワード再発行 | 準備 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_reissue.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('reissue?error=1');
	}

	//入力データ取得
	$reissue = $_SESSION['input']['reissue'];

	//ユーザー取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE mail = :mail');
	$stmt->bindValue(':mail', $reissue['mail']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$user = $data;
	} else {
		freo_error('指定されたユーザーが見つかりません。');
	}

	//メール本文定義
	$mail_header = file_get_contents(FREO_MAIL_DIR . 'internals/reissue/send_header.txt');
	$mail_footer = file_get_contents(FREO_MAIL_DIR . 'internals/reissue/send_footer.txt');

	eval('$mail_header = "' . $mail_header . '";');
	eval('$mail_footer = "' . $mail_footer . '";');

	$message  = $mail_header;
	$message .= $freo->core['http_file'] . '/reissue/post?user=' . $user['id'] . '&key=' . $user['password'] . "\n";
	$message .= $mail_footer;

	//メール送信
	$flag = freo_mail($user['mail'], 'パスワード再発行', $message);
	if (!$flag) {
		freo_error('メールを送信できません。');
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'reissue' => $reissue
		)
	));

	return;
}

?>
