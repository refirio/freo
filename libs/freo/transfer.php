<?php

/*********************************************************************

 freo | セッションID自動付加関数 (2012/12/17)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* セッションID自動付加実行 */
function freo_transfer_execute($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_transfer_execute', $data);
	}
	if (!$freo->agent['career']) {
		return $data;
	}

	$temporary = md5(uniqid(rand(), true));

	$data = str_replace('$', $temporary, $data);
	$data = preg_replace('/href="(([^"\\\\]|\\\\.)*)"/e', '"href=\"".freo_transfer_link("$1")."\""', $data);
	$data = preg_replace('/src="(([^"\\\\]|\\\\.)*)"/e', '"src=\"".freo_transfer_link("$1")."\""', $data);
	$data = preg_replace('/(<form [^>]+>)/e', 'freo_transfer_form("$1")', $data);
	$data = str_replace($temporary, '$', $data);

	return $data;
}

/* リンク */
function freo_transfer_link($data)
{
	global $freo;

	if (preg_match('/#/', $data)) {
		list($data, $id) = explode('#', $data, 2);
	} else {
		$id = null;
	}

	if (preg_match('/^(' . preg_quote(FREO_HTTP_URL, '/') . ')/', $data) or (FREO_HTTPS_URL and preg_match('/^(' . preg_quote(FREO_HTTPS_URL, '/') . ')/', $data))) {
		if (strpos($data, '?')) {
			$data .= '&amp;';
		} else {
			$data .= '?';
		}
		$data .= $freo->core['session_name'] . '=' . $freo->core['session_id'];
	}

	if ($id !== null) {
		$data .= '#' . $id;
	}

	return $data;
}

/* フォーム */
function freo_transfer_form($data)
{
	global $freo;

	if (preg_match('/action="(' . preg_quote(FREO_HTTP_URL, '/') . ')[^\"]*"/', $data) or (FREO_HTTPS_URL and preg_match('/action="(' . preg_quote(FREO_HTTPS_URL, '/') . ')[^\"]*"/', $data))) {
		$data .= '<input type="hidden" name="' . $freo->core['session_name'] . '" value="' . $freo->core['session_id'] . '" />';
	}

	return $data;
}

?>
