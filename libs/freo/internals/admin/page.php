<?php

/*********************************************************************

 freo | 管理画面 | ページ管理 (2011/01/28)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['pid']) or !preg_match('/^[\w\-\/]+$/', $_GET['pid'])) {
		$_GET['pid'] = null;
	}
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//親ページ取得
	$parent = array();
	if ($_GET['pid']) {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
		$stmt->bindValue(':id', $_GET['pid']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$parent = $data;
		}
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$words = explode(' ', str_replace('　', ' ', $_GET['word']));

		foreach ($words as $word) {
			$condition .= ' AND (title LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ')';
		}
	}
	if (isset($_GET['user'])) {
		$condition .= ' AND user_id = ' . $freo->pdo->quote($_GET['user']);
	}
	if (isset($_GET['approved'])) {
		$condition .= ' AND approved = ' . $freo->pdo->quote($_GET['approved']);
	}
	if (isset($_GET['status'])) {
		$condition .= ' AND status = ' . $freo->pdo->quote($_GET['status']);
	}
	if (isset($_GET['tag'])) {
		$condition .= ' AND (tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%') . ')';
	}
	if (isset($_GET['date'])) {
		if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m%d\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		}
	}
	if ($condition) {
		$condition = ' WHERE id IS NOT NULL ' . $condition;
	}

	//ページ取得
	if ($_GET['pid']) {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :pid ORDER BY sort, id');
		$stmt->bindValue(':pid', $_GET['pid']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} elseif ($condition) {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages ' . $condition . ' ORDER BY sort, id LIMIT :start, :limit');
		$stmt->bindValue(':start', intval($freo->config['view']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
		$stmt->bindValue(':limit', intval($freo->config['view']['admin_limit']), PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid IS NULL ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	$pages = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$pages[$data['id']] = $data;
	}

	//ページID取得
	$page_keys = array_keys($pages);

	//ページ関連データ取得
	$page_associates = freo_associate_page('get', $page_keys);

	//ページファイル取得
	$page_files = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['file']) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_files/' . $page . '/' . $pages[$page]['file']);

		$page_files[$page] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//ページイメージ取得
	$page_images = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['image']) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_images/' . $page . '/' . $pages[$page]['image']);

		$page_images[$page] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//ページテキスト取得
	$page_texts = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['text']) {
			continue;
		}

		list($excerpt, $more) = freo_divide($pages[$page]['text']);

		$page_texts[$page] = array(
			'excerpt' => $excerpt,
			'more'    => $more
		);
	}

	//ページ数取得
	if ($condition) {
		$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'pages ' . $condition);
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$data       = $stmt->fetch(PDO::FETCH_NUM);
		$page_count = $data[0];
		$page_page  = ceil($page_count / $freo->config['view']['admin_limit']);
	} else {
		$page_count = 0;
		$page_page  = 0;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'           => freo_token('create'),
		'parent'          => $parent,
		'pages'           => $pages,
		'page_associates' => $page_associates,
		'page_files'      => $page_files,
		'page_images'     => $page_images,
		'page_texts'      => $page_texts,
		'page_count'      => $page_count,
		'page_page'       => $page_page
	));

	return;
}

?>
