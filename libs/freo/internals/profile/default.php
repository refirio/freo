<?php

/*********************************************************************

 freo | プロフィール (2013/09/18)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[1])) {
		$_GET['id'] = $freo->parameters[1];
	}
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_error('表示したいユーザーを指定してください。');
	}

	//ユーザー取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$user = $data;
	} else {
		freo_error('指定されたユーザーが見つかりません。', '404 Not Found');
	}

	//関連データ取得
	$user_associates = freo_associate_user('get', array($_GET['id']));
	$user_associate  = $user_associates[$_GET['id']];

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'user'           => $user,
		'user_associate' => $user_associate
	));

	return;
}

?>
