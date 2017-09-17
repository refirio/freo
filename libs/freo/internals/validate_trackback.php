<?php

/*********************************************************************

 freo | 入力データ検証 | トラックバック登録 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_trackback($mode, $input)
{
	global $freo;

	$errors = array();

	//名前
	if ($mode == 'insert') {
		if ($input['trackback']['name'] == '') {
			$errors[] = 'No Name (blog_name)';
		}
	} elseif ($mode == 'update') {
		if ($input['trackback']['name'] == '') {
			$errors[] = '名前が入力されていません。';
		} elseif (mb_strlen($input['trackback']['name'], 'UTF-8') > 80) {
			$errors[] = '名前は80文字以内で入力してください。';
		}
	}

	//URL
	if ($mode == 'insert') {
		if ($input['trackback']['url'] == '') {
			$errors[] = 'No URL (url)';
		}
	} elseif ($mode == 'update') {
		if ($input['trackback']['url'] == '') {
			$errors[] = 'URLが入力されていません。';
		} elseif (mb_strlen($input['trackback']['url'], 'UTF-8') > 200) {
			$errors[] = 'URLは200文字以内で入力してください。';
		}
	}

	//タイトル
	if ($mode == 'insert') {
		if ($input['trackback']['title'] == '') {
			$errors[] = 'No Title (title)';
		}
	} elseif ($mode == 'update') {
		if ($input['trackback']['title'] == '') {
			$errors[] = 'タイトルが入力されていません。';
		} elseif (mb_strlen($input['trackback']['title'], 'UTF-8') > 80) {
			$errors[] = 'タイトルは80文字以内で入力してください。';
		}
	}

	//本文
	if ($mode == 'update') {
		if (mb_strlen($input['trackback']['text'], 'UTF-8') > 5000) {
			$errors[] = '本文は5000文字以内で入力してください。';
		}
	}

	return $errors;
}

?>
