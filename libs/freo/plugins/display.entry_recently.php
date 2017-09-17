<?php

/*********************************************************************

 新着エントリー表示プラグイン (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_display_entry_recently()
{
	global $freo;

	if ($freo->config['plugin']['entry_recently']['default_limit'] == 0) {
		return;
	}

	//検索条件設定
	$condition = null;

	//制限されたエントリーを一覧に表示しない
	if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$entry_filters = freo_filter_entry('user', array_keys($freo->refer['entries']));
		$entry_filters = array_keys($entry_filters, true);
		$entry_filters = array_map('intval', $entry_filters);
		if (!empty($entry_filters)) {
			$condition .= ' AND id NOT IN(' . implode(',', $entry_filters) . ')';
		}

		$entry_securities = freo_security_entry('user', array_keys($freo->refer['entries']), array('password'));
		$entry_securities = array_keys($entry_securities, true);
		$entry_securities = array_map('intval', $entry_securities);
		if (!empty($entry_securities)) {
			$condition .= ' AND id NOT IN(' . implode(',', $entry_securities) . ')';
		}
	}

	//エントリー取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY datetime DESC LIMIT :limit');
	$stmt->bindValue(':now1',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':limit', intval($freo->config['plugin']['entry_recently']['default_limit']), PDO::PARAM_INT);
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

	//エントリーフィルター取得
	$entry_filters = freo_filter_entry('user', $entry_keys);

	foreach ($entry_filters as $id => $filter) {
		if (!$filter) {
			continue;
		}

		$entries[$id]['comment']   = 'closed';
		$entries[$id]['trackback'] = 'closed';
		$entries[$id]['title']     = str_replace('[$title]', $entries[$id]['title'], $freo->config['entry']['filter_title']);
		$entries[$id]['file']      = null;
		$entries[$id]['image']     = null;
		$entries[$id]['memo']      = null;
		$entries[$id]['text']      = str_replace('[$text]', $entries[$id]['text'], $freo->config['entry']['filter_text']);

		if ($freo->config['entry']['filter_option']) {
			$entry_associates[$id]['option'] = array();
		}
	}

	//エントリー保護データ取得
	$entry_securities = freo_security_entry('user', $entry_keys);

	foreach ($entry_securities as $id => $security) {
		if (!$security) {
			continue;
		}

		$entries[$id]['title'] = str_replace('[$title]', $entries[$id]['title'], $freo->config['entry']['restriction_title']);
		$entries[$id]['file']  = null;
		$entries[$id]['image'] = null;
		$entries[$id]['memo']  = null;
		$entries[$id]['text']  = str_replace('[$text]', $entries[$id]['text'], $freo->config['entry']['restriction_text']);

		if ($freo->config['entry']['restriction_option']) {
			$entry_associates[$id]['option'] = array();
		}
	}

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

	//エントリーサムネイル取得
	$entry_thumbnails = array();
	foreach ($entry_keys as $entry) {
		if (!$entries[$entry]['file']) {
			continue;
		}
		if (!file_exists(FREO_FILE_DIR . 'entry_thumbnails/' . $entry . '/' . $entries[$entry]['file'])) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_thumbnails/' . $entry . '/' . $entries[$entry]['file']);

		$entry_thumbnails[$entry] = array(
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

		if (isset($entry_associates[$entry]['option'])) {
			list($entries[$entry]['text'], $entry_associates[$entry]['option']) = freo_option($entries[$entry]['text'], $entry_associates[$entry]['option'], FREO_FILE_DIR . 'entry_options/' . $entry . '/');
		}
		list($excerpt, $more) = freo_divide($entries[$entry]['text']);

		$entry_texts[$entry] = array(
			'excerpt' => $excerpt,
			'more'    => $more
		);
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_entry_recentries'          => $entries,
		'plugin_entry_recently_associates' => $entry_associates,
		'plugin_entry_recently_filters'    => $entry_filters,
		'plugin_entry_recently_securities' => $entry_securities,
		'plugin_entry_recently_tags'       => $entry_tags,
		'plugin_entry_recently_files'      => $entry_files,
		'plugin_entry_recently_thumbnails' => $entry_thumbnails,
		'plugin_entry_recently_images'     => $entry_images,
		'plugin_entry_recently_texts'      => $entry_texts
	));

	return;
}

?>
