<?php

/*********************************************************************

 freo | コメント | 登録 (2012/09/01)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('default');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('default');
	}

	//入力データ取得
	$comment = $_SESSION['input']['comment'];

	if (!isset($comment['entry_id'])) {
		$comment['entry_id'] = null;
	}
	if (!isset($comment['page_id'])) {
		$comment['page_id'] = null;
	}
	if ($comment['restriction'] == '') {
		$comment['restriction'] = null;
	}
	if ($comment['name'] == '') {
		$comment['name'] = null;
	}
	if ($comment['mail'] == '') {
		$comment['mail'] = null;
	}
	if ($comment['url'] == '') {
		$comment['url'] = null;
	}

	//対象データ検証
	if ($comment['entry_id']) {
		$stmt = $freo->pdo->prepare('SELECT comment FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now))');
		$stmt->bindValue(':id', $comment['entry_id'], PDO::PARAM_INT);
	} else {
		$stmt = $freo->pdo->prepare('SELECT comment FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now))');
		$stmt->bindValue(':id', $comment['page_id']);
	}
	$stmt->bindValue(':now', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($data['comment'] == 'closed' or $data['comment'] == 'view') {
			freo_redirect('default');
		}
	} else {
		freo_redirect('default');
	}

	//権限確認
	if ($freo->user['authority'] == 'root' or $freo->user['authority'] == 'author') {
		$approved = 'yes';
	} else {
		$approved = $freo->config['comment']['approve'];
	}

	//データ登録
	$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'comments VALUES(NULL, :entry_id, :page_id, :user_id, :now1, :now2, :approved, :restriction, :name, :mail, :url, :ip, :text)');
	$stmt->bindValue(':entry_id',    $comment['entry_id']);
	$stmt->bindValue(':page_id',     $comment['page_id']);
	$stmt->bindValue(':user_id',     $freo->user['id']);
	$stmt->bindValue(':now1',        date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',        date('Y-m-d H:i:s'));
	$stmt->bindValue(':approved',    $approved);
	$stmt->bindValue(':restriction', $comment['restriction']);
	$stmt->bindValue(':name',        $comment['name']);
	$stmt->bindValue(':mail',        $comment['mail']);
	$stmt->bindValue(':url',         $comment['url']);
	$stmt->bindValue(':ip',          $_SERVER['REMOTE_ADDR']);
	$stmt->bindValue(':text',        $comment['text']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//登録者情報を記憶
	if (isset($comment['session'])) {
		freo_setcookie('comment[name]',    $comment['name'],    time() + FREO_COOKIE_EXPIRE);
		freo_setcookie('comment[mail]',    $comment['mail'],    time() + FREO_COOKIE_EXPIRE);
		freo_setcookie('comment[url]',     $comment['url'],     time() + FREO_COOKIE_EXPIRE);
		freo_setcookie('comment[session]', $comment['session'], time() + FREO_COOKIE_EXPIRE);
	} else {
		freo_setcookie('comment[name]',    null);
		freo_setcookie('comment[mail]',    null);
		freo_setcookie('comment[url]',     null);
		freo_setcookie('comment[session]', null);
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('コメントを新規に登録しました。');

	//登録完了画面へ移動
	if ($comment['entry_id']) {
		freo_redirect('view/' . $comment['entry_id'] . '?exec=insert');
	} else {
		freo_redirect('page/' . $comment['page_id'] . '?exec=insert');
	}

	return;
}

?>
