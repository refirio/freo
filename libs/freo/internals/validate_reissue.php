<?php

/*********************************************************************

 freo | 入力データ検証 | パスワード再発行 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_reissue($mode, $input)
{
	global $freo;

	$errors = array();

	//メールアドレス
	if ($input['reissue']['mail'] == '') {
		$errors[] = 'メールアドレスが入力されていません。';
	} elseif (!strpos($input['reissue']['mail'], '@')) {
		$errors[] = 'メールアドレスの入力内容が正しくありません。';
	} elseif (mb_strlen($input['reissue']['mail'], 'UTF-8') > 80) {
		$errors[] = 'メールアドレスは80文字以内で入力してください。';
	} else {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE mail = :mail');
		$stmt->bindValue(':mail', $input['reissue']['mail']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
			$errors[] = '入力されたメールアドレスは存在しません。';
		}
	}

	return $errors;
}

?>
