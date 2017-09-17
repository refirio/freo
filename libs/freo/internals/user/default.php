<?php

/*********************************************************************

 freo | ユーザー用画面 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

?>
