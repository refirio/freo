<?php

/*********************************************************************

 freo | 管理画面 | カテゴリー登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('admin/category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/category?error=1');
	}

	//入力データ取得
	$category = $_SESSION['input']['category'];

	if ($category['pid'] == '') {
		$category['pid'] = null;
	}
	if ($category['memo'] == '') {
		$category['memo'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'categories SET pid = :pid, modified = :now, display = :display, sort = :sort, name = :name, memo = :memo WHERE id = :id');
		$stmt->bindValue(':pid',     $category['pid']);
		$stmt->bindValue(':now',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':display', $category['display']);
		$stmt->bindValue(':sort',    $category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',    $category['name']);
		$stmt->bindValue(':memo',    $category['memo']);
		$stmt->bindValue(':id',      $category['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'categories VALUES(:id, :pid, :now1, :now2, :display, :sort, :name, :memo)');
		$stmt->bindValue(':id',      $category['id']);
		$stmt->bindValue(':pid',     $category['pid']);
		$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':display', $category['display']);
		$stmt->bindValue(':sort',    $category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',    $category['name']);
		$stmt->bindValue(':memo',    $category['memo']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('カテゴリーを編集しました。');
	} else {
		freo_log('カテゴリーを新規に登録しました。');
	}

	//カテゴリー管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('admin/category?exec=update&id=' . $category['id'] . ($category['pid'] ? '&pid=' . $category['pid'] : ''));
	} else {
		freo_redirect('admin/category?exec=insert' . ($category['pid'] ? '&pid=' . $category['pid'] : ''));
	}

	return;
}

?>
