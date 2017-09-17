<?php

/*********************************************************************

 freo | 管理画面 | コメント入力 (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_comment.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = 0;
	}

	//権限確認
	if ($freo->user['authority'] != 'root') {
		$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (isset($freo->refer['users'][$data['user_id']])) {
				$authority = $freo->refer['users'][$data['user_id']]['authority'];
			} else {
				$authority = null;
			}

			if ($authority == 'root' or ($authority == 'author' and $freo->user['id'] != $data['user_id'])) {
				freo_error('このコメントを編集する権限がありません。');
			}
		}
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//投稿者データ取得
		if ($_POST['comment']['user_id'] != '') {
			$_POST['comment']['name'] = null;
			$_POST['comment']['mail'] = null;
			$_POST['comment']['url']  = null;
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_comment('update', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$comment = $_POST['comment'];
		} else {
			$_SESSION['input'] = $_POST;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('admin/comment_preview?id=' . $_GET['id']);
			} else {
				//登録処理へ移動
				freo_redirect('admin/comment_post?freo%5Btoken%5D=' . freo_token('create') . '&id=' . $_GET['id']);
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$comment = $_SESSION['input']['comment'];
		} else {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$comment = $data;
			} else {
				freo_error('指定されたコメントが見つかりません。', '404 Not Found');
			}
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'comment' => $comment
		)
	));

	return;
}

?>
