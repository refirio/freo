<?php

/*********************************************************************

 エントリー本文変換プラグイン (2011/02/04)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_entry_convert()
{
	global $freo;

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//データ取得
		$data = $_POST['entry']['text'];

		//データ加工
		if (($freo->agent['type'] == 'mobile' or $freo->agent['type'] == 'ipad' or $freo->agent['type'] == 'iphone' or $freo->agent['type'] == 'ipod') and !preg_match('/<\/(address|blockquote|div|dl|fieldset|form|h1|h2|h3|h4|h5|h6|hr|noscript|olp||pre|style|table|ul)>/', $data)) {
			$data = str_replace("\n", '<br />', $data);
			$data = str_replace('<br /><br />', "</p>\n<p>", $data);
			$data = str_replace('-----', FREO_DIVIDE_MARK, $data);
			$data = '<p>' . $data . '</p>';
		}

		//データ割当
		$_POST['entry']['text'] = $data;
	}

	return;
}

?>
