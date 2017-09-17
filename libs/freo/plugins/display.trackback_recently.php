<?php

/*********************************************************************

 新着トラックバック表示プラグイン (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_trackback.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_display_trackback_recently()
{
	global $freo;

	if ($freo->config['plugin']['trackback_recently']['default_limit'] == 0) {
		return;
	}

	//検索条件設定
	$condition = null;

	//制限されたエントリーへのトラックバックを一覧に表示しない
	if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$entry_filters = freo_filter_entry('user', array_keys($freo->refer['entries']));
		$entry_filters = array_keys($entry_filters, true);
		$entry_filters = array_map('intval', $entry_filters);
		if (!empty($entry_filters)) {
			$condition .= ' AND (entry_id IS NULL OR entry_id NOT IN(' . implode(',', $entry_filters) . '))';
		}

		$entry_securities = freo_security_entry('user', array_keys($freo->refer['entries']), array('password'));
		$entry_securities = array_keys($entry_securities, true);
		$entry_securities = array_map('intval', $entry_securities);
		if (!empty($entry_securities)) {
			$condition .= ' AND (entry_id IS NULL OR entry_id NOT IN(' . implode(',', $entry_securities) . '))';
		}
	}

	//制限されたページへのトラックバックを一覧に表示しない
	if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$page_filters = freo_filter_page('user', array_keys($freo->refer['pages']));
		$page_filters = array_keys($page_filters, true);
		$page_filters = array_map(array($freo->pdo, 'quote'), $page_filters);
		if (!empty($page_filters)) {
			$condition .= ' AND (page_id IS NULL OR page_id NOT IN(' . implode(',', $page_filters) . '))';
		}

		$page_securities = freo_security_page('user', array_keys($freo->refer['pages']), array('password'));
		$page_securities = array_keys($page_securities, true);
		$page_securities = array_map(array($freo->pdo, 'quote'), $page_securities);
		if (!empty($page_securities)) {
			$condition .= ' AND (page_id IS NULL OR page_id NOT IN(' . implode(',', $page_securities) . '))';
		}
	}

	//トラックバック取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'trackbacks WHERE id IS NOT NULL ' . $condition . ' ORDER BY id DESC LIMIT :limit');
	$stmt->bindValue(':limit', intval($freo->config['plugin']['trackback_recently']['default_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$trackbacks = array();
	$entries    = array();
	$pages      = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($data['entry_id']) {
			$entries[] = intval($data['entry_id']);
		} elseif ($data['page_id']) {
			$pages[] = $freo->pdo->quote($data['page_id']);
		}

		$trackbacks[$data['id']] = $data;
	}

	//トラックバック保護データ取得
	$trackback_securities = freo_security_trackback('user', array_keys($trackbacks));

	foreach ($trackback_securities as $id => $security) {
		if (!$security) {
			continue;
		}

		$trackbacks[$id]['name']  = $freo->config['trackback']['approve_name'];
		$trackbacks[$id]['url']   = null;
		$trackbacks[$id]['ip']    = null;
		$trackbacks[$id]['title'] = null;
		$trackbacks[$id]['text']  = $freo->config['trackback']['approve_text'];
	}

	//エントリータイトル取得
	if (!empty($entries)) {
		$stmt = $freo->pdo->query('SELECT id, title FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id IN(' . implode(',', $entries) . ')');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$entries = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$entries[$data['id']] = $data;
		}
	}

	//ページタイトル取得
	if (!empty($pages)) {
		$stmt = $freo->pdo->query('SELECT id, title FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id IN(' . implode(',', $pages) . ')');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$pages = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$pages[$data['id']] = $data;
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_trackback_recentries'          => $trackbacks,
		'plugin_trackback_recently_securities' => $trackback_securities,
		'plugin_trackback_recently_entries'    => $entries,
		'plugin_trackback_recently_pages'      => $pages
	));

	return;
}

?>
