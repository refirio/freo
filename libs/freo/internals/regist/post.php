<?php

/*********************************************************************

 freo | ユーザー登録 | 登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//新規登録チェック
	if ($freo->user['id'] or !$freo->config['user']['regist']) {
		freo_redirect('default');
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('regist/form?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('regist/form?error=1', true);
	}

	//入力データ取得
	$user           = $_SESSION['input']['user'];
	$user_associate = $_SESSION['input']['user_associate'];

	if ($user['url'] == '') {
		$user['url'] = null;
	}
	if ($user['text'] == '') {
		$user['text'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'users VALUES(:id, :now1, :now2, :approved, :authority, :password, NULL, NULL, :name, :mail, :url, :text)');
	$stmt->bindValue(':id',        $user['id']);
	$stmt->bindValue(':now1',      date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',      date('Y-m-d H:i:s'));
	$stmt->bindValue(':approved',  $freo->config['user']['approve']);
	$stmt->bindValue(':authority', 'guest');
	$stmt->bindValue(':password',  md5($user['password']));
	$stmt->bindValue(':name',      $user['name']);
	$stmt->bindValue(':mail',      $user['mail']);
	$stmt->bindValue(':url',       $user['url']);
	$stmt->bindValue(':text',      $user['text']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$user_associate['id'] = $user['id'];

	//関連データ更新
	freo_associate_user('post', $user_associate);

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('ユーザーを新規に登録しました。');

	//ユーザー管理へ移動
	freo_redirect('regist/complete');

	return;
}

?>
