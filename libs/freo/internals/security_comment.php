<?php

/*********************************************************************

 freo | 保護データ取得 | コメント (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 保護データ取得 */
function freo_security_comment($mode, $comments)
{
	global $freo;

	if (empty($comments)) {
		return array();
	}

	//データ初期化
	$securities = array();
	foreach ($comments as $comment) {
		$securities[$comment] = false;
	}

	$comments = array_map('intval', $comments);
	$protects = array();

	//保護データID取得
	if ($mode == 'nobody' or ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$stmt = $freo->pdo->query('SELECT id, approved, restriction FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE id IN(' . implode(',', $comments) . ')');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($data['approved'] != 'yes') {
				$protects[] = $data['id'];
			} elseif ($data['restriction'] == 'user' and ($mode == 'nobody' or !$freo->user['authority'])) {
				$protects[] = $data['id'];
			} elseif ($data['restriction'] == 'admin') {
				$protects[] = $data['id'];
			}
		}

		//保護データ情報設定
		foreach ($protects as $protect) {
			$securities[$protect] = true;
		}
	}

	return $securities;
}

?>
