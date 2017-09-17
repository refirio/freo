<?php

/*********************************************************************

 freo | 管理画面 | パスワード入力 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_password.php';

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

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_password(null, $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$password = $_POST['password'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('admin/password_post?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$password = $_SESSION['input']['password'];
		} else {
			//新規データ設定
			$password = array();
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'password' => $password
		)
	));

	return;
}

?>
