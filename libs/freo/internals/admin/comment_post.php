<?php

/*********************************************************************

 freo | 管理画面 | コメント登録 (2010/09/01)

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
		freo_redirect('admin/comment?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/comment?error=1');
	}

	//入力データ取得
	$comment = $_SESSION['input']['comment'];

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

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'comments SET modified = :now, restriction = :restriction, name = :name, mail = :mail, url = :url, text = :text WHERE id = :id');
	$stmt->bindValue(':now',         date('Y-m-d H:i:s'));
	$stmt->bindValue(':restriction', $comment['restriction']);
	$stmt->bindValue(':name',        $comment['name']);
	$stmt->bindValue(':mail',        $comment['mail']);
	$stmt->bindValue(':url',         $comment['url']);
	$stmt->bindValue(':text',        $comment['text']);
	$stmt->bindValue(':id',          $comment['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('コメントを編集しました。');

	//コメント管理へ移動
	freo_redirect('admin/comment?exec=update&id=' . $comment['id']);

	return;
}

?>
