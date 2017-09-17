<?php

/*********************************************************************

 freo | 管理画面 | プロフィール入力 (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_user.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//ユーザーID取得
		$_POST['user']['id'] = $freo->user['id'];

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_user('update', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$user = $_POST['user'];
		} else {
			$_SESSION['input'] = $_POST;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('admin/profile_preview');
			} else {
				//登録処理へ移動
				freo_redirect('admin/profile_post?freo%5Btoken%5D=' . freo_token('create'));
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$user = $_SESSION['input']['user'];
		} else {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
			$stmt->bindValue(':id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$user = $data;
			} else {
				freo_error('指定されたユーザーが見つかりません。', '404 Not Found');
			}
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'user' => $user
		)
	));

	return;
}

?>
