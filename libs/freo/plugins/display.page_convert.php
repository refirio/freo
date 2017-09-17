<?php

/*********************************************************************

 ページ本文変換プラグイン (2011/02/04)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_page_convert()
{
	global $freo;

	//データ取得
	$input = $freo->smarty->get_template_vars('input');

	//データ加工
	if (($freo->agent['type'] == 'mobile' or $freo->agent['type'] == 'ipad' or $freo->agent['type'] == 'iphone' or $freo->agent['type'] == 'ipod') and isset($input['page']['text']) and !preg_match('/<\/(address|blockquote|div|dl|fieldset|form|h1|h2|h3|h4|h5|h6|hr|noscript|ol|pre|style|table|ul)>/', $input['page']['text'])) {
		$lines = explode("\n", freo_unify($input['page']['text']));

		$data = '';
		foreach ($lines as $line) {
			$line = preg_replace('/^\s*<p[^>]*>/', "\n", $line);
			$line = preg_replace('/<\/p>\s*$/', "\n", $line);
			$line = preg_replace('/<br[^>]*\/>\s*/', "\n", $line);
			$line = str_replace(FREO_DIVIDE_MARK, '-----', $line);

			$data .= $line;
		}

		$input['page']['text'] = freo_trim($data);
	}

	//データ割当
	$freo->smarty->assign(array(
		'input' => $input
	));

	return;
}

?>
