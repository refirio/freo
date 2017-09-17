<?php

/*********************************************************************

 freo | 管理画面 | ユーザー入力 (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_user.php';
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}
	if ($_GET['id'] and $freo->user['id'] == $_GET['id']) {
		freo_redirect('admin/user?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//承認データ取得
		$_POST['user']['approved'] = ($freo->user['authority'] == 'root') ? 'yes' : $freo->config['user']['approve'];

		//関連データ取得
		if (!isset($_POST['user_associate'])) {
			$_POST['user_associate'] = array();
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_user($_GET['id'] ? 'update' : 'insert', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$user           = $_POST['user'];
			$user_associate = $_POST['user_associate'];
		} else {
			$_SESSION['input'] = $_POST;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('admin/user_preview' . ($_GET['id'] ? '?id=' . $_GET['id'] : ''), true);
			} else {
				//登録処理へ移動
				freo_redirect('admin/user_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''), true);
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$user           = $_SESSION['input']['user'];
			$user_associate = $_SESSION['input']['user_associate'];
		} elseif ($_GET['id']) {
			//編集データ取得
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
		} else {
			//新規データ設定
			$user = array(
				'approve' => $freo->config['user']['approve']
			);
			$user_associate = array();
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'user'           => $user,
			'user_associate' => $user_associate
		)
	));

	return;
}

?>
