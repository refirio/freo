<?php

/*********************************************************************

 freo | 管理画面 | オプション登録 (2010/09/01)

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
		freo_redirect('admin/option?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/option?error=1');
	}

	//入力データ取得
	$option = $_SESSION['input']['option'];

	if ($option['validate'] == '') {
		$option['validate'] = null;
	}
	if ($option['target'] == '') {
		$option['target'] = null;
	}
	if ($option['memo'] == '') {
		$option['memo'] = null;
	}
	if ($option['text'] == '') {
		$option['text'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'options SET modified = :now, target = :target, type = :type, required = :required, validate = :validate, sort = :sort, name = :name, memo = :memo, text = :text WHERE id = :id');
		$stmt->bindValue(':now',      date('Y-m-d H:i:s'));
		$stmt->bindValue(':target',   $option['target']);
		$stmt->bindValue(':type',     $option['type']);
		$stmt->bindValue(':required', $option['required']);
		$stmt->bindValue(':validate', $option['validate']);
		$stmt->bindValue(':sort',     $option['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',     $option['name']);
		$stmt->bindValue(':memo',     $option['memo']);
		$stmt->bindValue(':text',     $option['text']);
		$stmt->bindValue(':id',       $option['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'options VALUES(:id, :now1, :now2, :target, :type, :required, :validate, :sort, :name, :memo, :text)');
		$stmt->bindValue(':id',       $option['id']);
		$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':target',   $option['target']);
		$stmt->bindValue(':type',     $option['type']);
		$stmt->bindValue(':required', $option['required']);
		$stmt->bindValue(':validate', $option['validate']);
		$stmt->bindValue(':sort',     $option['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',     $option['name']);
		$stmt->bindValue(':memo',     $option['memo']);
		$stmt->bindValue(':text',     $option['text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('オプションを編集しました。');
	} else {
		freo_log('オプションを新規に登録しました。');
	}

	//オプション管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('admin/option?exec=update&id=' . $option['id']);
	} else {
		freo_redirect('admin/option?exec=insert');
	}

	return;
}

?>
