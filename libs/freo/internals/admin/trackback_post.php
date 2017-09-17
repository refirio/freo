<?php

/*********************************************************************

 freo | 管理画面 | トラックバック登録 (2010/09/01)

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
		freo_redirect('admin/trackback?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/trackback?error=1');
	}

	//入力データ取得
	$trackback = $_SESSION['input']['trackback'];

	if ($trackback['text'] == '') {
		$trackback['text'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'trackbacks SET modified = :now, name = :name, url = :url, title = :title, text = :text WHERE id = :id');
	$stmt->bindValue(':now',   date('Y-m-d H:i:s'));
	$stmt->bindValue(':name',  $trackback['name']);
	$stmt->bindValue(':url',   $trackback['url']);
	$stmt->bindValue(':title', $trackback['title']);
	$stmt->bindValue(':text',  $trackback['text']);
	$stmt->bindValue(':id',    $trackback['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('トラックバックを編集しました。');

	//トラックバック管理へ移動
	freo_redirect('admin/trackback?exec=update&id=' . $trackback['id']);

	return;
}

?>
