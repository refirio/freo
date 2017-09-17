<?php

/*********************************************************************

 freo | 保護データ取得 | トラックバック (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 保護データ取得 */
function freo_security_trackback($mode, $trackbacks)
{
	global $freo;

	if (empty($trackbacks)) {
		return array();
	}

	//データ初期化
	$securities = array();
	foreach ($trackbacks as $trackback) {
		$securities[$trackback] = false;
	}

	$trackbacks = array_map('intval', $trackbacks);
	$protects   = array();

	//保護データID取得
	if ($mode == 'nobody' or ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$stmt = $freo->pdo->query('SELECT id, approved FROM ' . FREO_DATABASE_PREFIX . 'trackbacks WHERE id IN(' . implode(',', $trackbacks) . ')');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($data['approved'] != 'yes') {
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
