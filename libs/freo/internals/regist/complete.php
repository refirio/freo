<?php

/*********************************************************************

 freo | ユーザー登録 | 完了 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//新規登録チェック
	if ($freo->user['id'] or !$freo->config['user']['regist']) {
		freo_redirect('default');
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

?>
