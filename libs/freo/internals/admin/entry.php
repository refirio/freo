<?php

/*********************************************************************

 freo | 管理画面 | エントリー管理 (2010/10/08)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['category']) and isset($freo->parameters[2])) {
		$parameters = array();
		$i          = 1;
		while (isset($freo->parameters[++$i])) {
			if (!$freo->parameters[$i]) {
				continue;
			}

			$parameters[] = $freo->parameters[$i];
		}
		$_GET['category'] = implode('/', $parameters);
	}
	if (!isset($_GET['category']) or !preg_match('/^[\w\-\/]+$/', $_GET['category'])) {
		$_GET['category'] = null;
	}
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
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
	if (!isset($_GET['category']) and $condition) {
		$condition = ' WHERE id IS NOT NULL ' . $condition;
	}

	//エントリー取得
	if (isset($_GET['category'])) {
		$stmt = $freo->pdo->prepare('SELECT id, user_id, created, modified, approved, restriction, password, status, display, comment, trackback, code, title, tag, datetime, close, file, image, memo, text, category_id, entry_id FROM ' . FREO_DATABASE_PREFIX . 'entries LEFT JOIN ' . FREO_DATABASE_PREFIX . 'category_sets ON id = entry_id WHERE category_id = :category ' . $condition . ' ORDER BY datetime DESC LIMIT :start, :limit');
		$stmt->bindValue(':category', $_GET['category']);
		$stmt->bindValue(':start',    intval($freo->config['view']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
		$stmt->bindValue(':limit',    intval($freo->config['view']['admin_limit']), PDO::PARAM_INT);
	} else {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries ' . $condition . ' ORDER BY datetime DESC LIMIT :start, :limit');
		$stmt->bindValue(':start', intval($freo->config['view']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
		$stmt->bindValue(':limit', intval($freo->config['view']['admin_limit']), PDO::PARAM_INT);
	}
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$entries = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$entries[$data['id']] = $data;
	}

	//エントリーID取得
	$entry_keys = array_keys($entries);

	//エントリー関連データ取得
	$entry_associates = freo_associate_entry('get', $entry_keys);

	//エントリータグ取得
	$entry_tags = array();
	foreach ($entry_keys as $entry) {
		if (!$entries[$entry]['tag']) {
			continue;
		}

		$entry_tags[$entry] = explode(',', $entries[$entry]['tag']);
	}

	//エントリーファイル取得
	$entry_files = array();
	foreach ($entry_keys as $entry) {
		if (!$entries[$entry]['file']) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_files/' . $entry . '/' . $entries[$entry]['file']);

		$entry_files[$entry] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//エントリーイメージ取得
	$entry_images = array();
	foreach ($entry_keys as $entry) {
		if (!$entries[$entry]['image']) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_images/' . $entry . '/' . $entries[$entry]['image']);

		$entry_images[$entry] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//エントリーテキスト取得
	$entry_texts = array();
	foreach ($entry_keys as $entry) {
		if (!$entries[$entry]['text']) {
			continue;
		}

		list($excerpt, $more) = freo_divide($entries[$entry]['text']);

		$entry_texts[$entry] = array(
			'excerpt' => $excerpt,
			'more'    => $more
		);
	}

	//エントリー数・ページ数取得
	if (isset($_GET['category'])) {
		$stmt = $freo->pdo->prepare('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'entries LEFT JOIN ' . FREO_DATABASE_PREFIX . 'category_sets ON id = entry_id WHERE category_id = :category ' . $condition);
		$stmt->bindValue(':category', $_GET['category']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'entries ' . $condition);
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	$data        = $stmt->fetch(PDO::FETCH_NUM);
	$entry_count = $data[0];
	$entry_page  = ceil($entry_count / $freo->config['view']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'            => freo_token('create'),
		'entries'          => $entries,
		'entry_associates' => $entry_associates,
		'entry_tags'       => $entry_tags,
		'entry_files'      => $entry_files,
		'entry_images'     => $entry_images,
		'entry_texts'      => $entry_texts,
		'entry_count'      => $entry_count,
		'entry_page'       => $entry_page
	));

	return;
}

?>
