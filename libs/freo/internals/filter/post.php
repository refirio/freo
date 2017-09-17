<?php

/*********************************************************************

 freo | フィルター設定 | 登録 (2012/12/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//フィルターチェック
	if ($freo->user['authority'] == 'root' or $freo->user['authority'] == 'author' or (!$freo->config['entry']['filter'] and !$freo->config['page']['filter'] and !$freo->config['media']['filter'])) {
		freo_redirect('default');
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('filter/default?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('filter/default?error=1', true);
	}

	//データ登録
	$filters = array();
	foreach ($_SESSION['input']['filter'] as $key => $value) {
		if ($value) {
			$filters[$key] = true;

			freo_setcookie('filter[' . $key . ']', true, time() + FREO_COOKIE_EXPIRE);
		} else {
			freo_setcookie('filter[' . $key . ']', null);
		}
	}

	$_SESSION['filter'] = $filters;

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('フィルターを設定しました。');

	//ユーザー管理へ移動
	freo_redirect('filter/default?exec=update');

	return;
}

?>
