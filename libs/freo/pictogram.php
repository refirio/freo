<?php

/*********************************************************************

 freo | 絵文字関数 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 絵文字コード統一 */
function freo_pictogram_unify($data)
{
	global $freo;
	static $translations;

	if (is_array($data)) {
		return array_map('freo_pictogram_unify', $data);
	}

	if (FREO_PICTOGRAM_MODE and $freo->agent['type'] == 'pc' and preg_match('/\[e:([^\]]+)\]/', $data)) {
		if (empty($translations)) {

			/* できれば、HTML_Emojiクラスを介さずにDefault.phpへアクセスするのは避けたい */

			$translations = include FREO_MAIN_DIR . 'HTML/Emoji/Pc/Default.php';
			$translations = array_flip($translations);
		}

		$data = preg_replace('/\[e:([^\]]+)\]/e', '$translations[pack("H*","$1")]', $data);
	}

	return $data;
}

/* キャリアに応じた絵文字に変換 */
function freo_pictogram_convert($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_pictogram_convert', $data);
	}

	$emoji = HTML_Emoji::getInstance();
	$emoji->setImageUrl(FREO_PICTOGRAM_IMAGE_URL);

	$data = $emoji->convertCarrier($data);

	if ($freo->agent['type'] == 'pc') {
		$temporary = md5(uniqid(rand(), true));

		$data = freo_pictogram_escape($data);
		$data = str_replace('$', $temporary, $data);
		$data = preg_replace('/(value="([^"\\\\]|\\\\.)*")/e', 'freo_pictogram_unescape("$1","text")', $data);
		$data = preg_replace('/(<textarea [^>]+>[^<]+<\/textarea>)/e', 'freo_pictogram_unescape("$1","text")', $data);
		$data = str_replace($temporary, '$', $data);
		$data = freo_pictogram_unescape($data);
	}

	return $data;
}

/* 絵文字コード削除 */
function freo_pictogram_except($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_pictogram_except', $data);
	}

	$emoji = HTML_Emoji::getInstance();
	$emoji->setImageUrl(FREO_PICTOGRAM_IMAGE_URL);

	$data = $emoji->removeEmoji($data);

	return $data;
}

/* 絵文字をエスケープ */
function freo_pictogram_escape($data)
{
	return preg_replace('/<img class="emoji" src="([^"]+)" alt="" width="12" height="12" \/>/e', '"[E:".basename("$1",".gif")."]"', $data);
}

/* 絵文字をアンエスケープ */
function freo_pictogram_unescape($data, $type = 'html')
{
	if ($type == 'html') {
		return preg_replace('/\[E:([^\]]+)\]/', '<img class="emoji" src="' . FREO_PICTOGRAM_IMAGE_URL . '$1.gif" alt="" width="12" height="12" />', $data);
	} else {
		return preg_replace('/\[E:([^\]]+)\]/e', '"[e:".basename("$1",".gif")."]"', $data);
	}
}

?>
