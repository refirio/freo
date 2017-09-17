<?php

/*********************************************************************

 freo | 管理画面 | プロフィール登録 (2010/09/01)

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
		freo_redirect('admin/information_form?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/information_form?error=1');
	}

	//入力データ取得
	$information = $_SESSION['input']['information'];

	if ($information['entry_id'] == '') {
		$information['entry_id'] = null;
	}
	if ($information['page_id'] == '') {
		$information['page_id'] = null;
	}
	if ($information['text'] == '') {
		$information['text'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'informations SET entry_id = :entry_id, page_id = :page_id, modified = :now, text = :text WHERE id = :id');
	$stmt->bindValue(':entry_id', $information['entry_id']);
	$stmt->bindValue(':page_id',  $information['page_id']);
	$stmt->bindValue(':now',      date('Y-m-d H:i:s'));
	$stmt->bindValue(':text',     $information['text']);
	$stmt->bindValue(':id',       'default');
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (!$stmt->rowCount()) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'informations VALUES(:id, :entry_id, :page_id, :now1, :now2, :text)');
		$stmt->bindValue(':id',       'default');
		$stmt->bindValue(':entry_id', $information['entry_id']);
		$stmt->bindValue(':page_id',  $information['page_id']);
		$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':text',     $information['text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('インフォメーションを編集しました。');

	//プロフィール入力へ移動
	freo_redirect('admin/information_form?exec=update');

	return;
}

?>
