<?php

/*********************************************************************

 freo | 管理画面 | グループ登録 (2010/09/01)

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
		freo_redirect('admin/group?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/group?error=1');
	}

	//入力データ取得
	$group = $_SESSION['input']['group'];

	if ($group['memo'] == '') {
		$group['memo'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'groups SET modified = :now, sort = :sort, name = :name, memo = :memo WHERE id = :id');
		$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $group['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $group['name']);
		$stmt->bindValue(':memo', $group['memo']);
		$stmt->bindValue(':id',   $group['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'groups VALUES(:id, :now1, :now2, :sort, :name, :memo)');
		$stmt->bindValue(':id',   $group['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $group['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $group['name']);
		$stmt->bindValue(':memo', $group['memo']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('グループを編集しました。');
	} else {
		freo_log('グループを新規に登録しました。');
	}

	//グループ管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('admin/group?exec=update&id=' . $group['id']);
	} else {
		freo_redirect('admin/group?exec=insert');
	}

	return;
}

?>
