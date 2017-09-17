<?php

/*********************************************************************

 freo | パスワード再発行 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_reissue.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_reissue('update', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$reissue = $_POST['reissue'];
		} else {
			$_SESSION['input'] = $_POST;

			//準備処理へ移動
			freo_redirect('reissue/send?freo%5Btoken%5D=' . freo_token('create'));
		}
	} else {
		//新規データ設定
		$reissue = array();
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'reissue' => $reissue
		)
	));

	return;
}

?>
