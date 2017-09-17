<?php

/*********************************************************************

 freo | 管理画面 | トラックバック管理 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$words = explode(' ', str_replace('　', ' ', $_GET['word']));

		foreach ($words as $word) {
			$condition .= ' AND (name LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR url LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR title LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ')';
		}
	}
	if (isset($_GET['approved'])) {
		$condition .= ' AND approved = ' . $freo->pdo->quote($_GET['approved']);
	}
	if (isset($_GET['date'])) {
		if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m%d\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		}
	}
	if ($condition) {
		$condition = ' WHERE id IS NOT NULL ' . $condition;
	}

	//トラックバック取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'trackbacks ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['view']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['view']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$trackbacks = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$trackbacks[$data['id']] = $data;
	}

	//トラックバック数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'trackbacks ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data            = $stmt->fetch(PDO::FETCH_NUM);
	$trackback_count = $data[0];
	$trackback_page  = ceil($trackback_count / $freo->config['view']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'           => freo_token('create'),
		'trackbacks'      => $trackbacks,
		'trackback_count' => $trackback_count,
		'trackback_page'  => $trackback_page
	));

	return;
}

?>
