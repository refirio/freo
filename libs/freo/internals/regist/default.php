<?php

/*********************************************************************

 freo | ユーザー登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_user.php';
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//新規登録チェック
	if ($freo->user['id'] or !$freo->config['user']['regist']) {
		freo_redirect('default');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//関連データ取得
		if (!isset($_POST['user_associate'])) {
			$_POST['user_associate'] = array();
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_user('insert', $_POST);

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
				freo_redirect('regist/preview', true);
			} else {
				//登録処理へ移動
				freo_redirect('regist/post?freo%5Btoken%5D=' . freo_token('create'), true);
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$user           = $_SESSION['input']['user'];
			$user_associate = $_SESSION['input']['user_associate'];
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
