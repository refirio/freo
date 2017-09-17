<?php

/*********************************************************************

 freo | ログイン画面 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_login.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_login(null, $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}
	}

	//ログイン後の画面へ移動
	if ($freo->user['id']) {
		if ($freo->user['authority'] == 'guest') {
			freo_redirect('user');
		} else {
			freo_redirect('admin');
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

?>
