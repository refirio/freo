<?php

/*********************************************************************

 freo | 入力データ検証 | インフォメーション登録 (2013/04/29)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_information($mode, $input)
{
	global $freo;

	$errors = array();

	//並び順
	if ($input['information']['entry_id'] != '') {
		if (!preg_match('/^\d+$/', $input['information']['entry_id'])) {
			$errors[] = 'エントリーIDは半角数字で入力してください。';
		} elseif (mb_strlen($input['information']['entry_id'], 'UTF-8') > 10) {
			$errors[] = 'エントリーIDは10文字以内で入力してください。';
		}
	}

	//ページID
	if ($input['information']['page_id'] != '') {
		if (!preg_match('/^[\w\-\/]+$/', $input['information']['page_id'])) {
			$errors[] = 'ページIDは半角英数字で入力してください。';
		} elseif (preg_match('/^\d+$/', $input['information']['page_id'])) {
			$errors[] = 'ページIDには半角英字を含んでください。';
		} elseif (mb_strlen($input['information']['page_id'], 'UTF-8') > 80) {
			$errors[] = 'ページIDは80文字以内で入力してください。';
		}
	}

	return $errors;
}

?>
