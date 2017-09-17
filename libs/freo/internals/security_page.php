<?php

/*********************************************************************

 freo | 保護データ取得 | ページ (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* 保護データ取得 */
function freo_security_page($mode, $pages, $exceptions = array())
{
	global $freo;

	if (empty($pages)) {
		return array();
	}

	//データ初期化
	$securities = array();
	foreach ($pages as $page) {
		$securities[$page] = false;
	}

	$pages    = array_map(array($freo->pdo, 'quote'), $pages);
	$protects = array();

	//保護データID取得
	if ($mode == 'nobody' or ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		//制限方法を取得
		$stmt = $freo->pdo->query('SELECT id, restriction FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id IN(' . implode(',', $pages) . ') AND restriction IS NOT NULL');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$restricts = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (!in_array('user', $exceptions) and $data['restriction'] == 'user') {
				$restricts['users'][] = $data['id'];
			} elseif (!in_array('group', $exceptions) and $data['restriction'] == 'group') {
				$restricts['groups'][] = $data['id'];
			} elseif (!in_array('password', $exceptions) and $data['restriction'] == 'password') {
				$restricts['passwords'][] = $data['id'];
			}
		}

		//ユーザー登録で制限
		if (!empty($restricts['users']) and ($mode == 'nobody' or !$freo->user['id'])) {
			$protects = array_merge($protects, $restricts['users']);
		}

		//グループで制限
		if (!empty($restricts['groups'])) {
			if ($mode == 'user' and $freo->user['groups']) {
				$groups          = array_map(array($freo->pdo, 'quote'), $freo->user['groups']);
				$restrict_groups = array_map(array($freo->pdo, 'quote'), $restricts['groups']);

				$stmt = $freo->pdo->query('SELECT page_id FROM ' . FREO_DATABASE_PREFIX . 'group_sets WHERE page_id IN(' . implode(',', $restrict_groups) . ') AND group_id IN(' . implode(',', $groups) . ') GROUP BY page_id');
				if (!$stmt) {
					freo_error($freo->pdo->errorInfo());
				}

				$accessibles = array();
				while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
					$accessibles[] = $data[0];
				}

				$protects = array_merge($protects, array_diff($restricts['groups'], $accessibles));
			} else {
				$protects = array_merge($protects, $restricts['groups']);
			}
		}

		//パスワードで制限
		if (!empty($restricts['passwords'])) {
			if ($mode == 'user' and isset($_SESSION['security']['page'])) {
				$accessibles = array();
				foreach ($_SESSION['security']['page'] as $id => $flag) {
					if (!$flag) {
						continue;
					}

					$accessibles[] = $id;
				}

				$protects = array_merge($protects, array_diff($restricts['passwords'], $accessibles));
			} else {
				$protects = array_merge($protects, $restricts['passwords']);
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
