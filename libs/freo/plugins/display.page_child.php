<?php

/*********************************************************************

 子ページ表示プラグイン (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_display_page_child()
{
	global $freo;

	if (!$freo->smarty->get_template_vars('page')) {
		return;
	}

	//検索条件設定
	$condition = null;

	//制限されたページを一覧に表示しない
	if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$page_filters = freo_filter_page('user', array_keys($freo->refer['pages']));
		$page_filters = array_keys($page_filters, true);
		$page_filters = array_map(array($freo->pdo, 'quote'), $page_filters);
		if (!empty($page_filters)) {
			$condition .= ' AND id NOT IN(' . implode(',', $page_filters) . ')';
		}

		$page_securities = freo_security_page('user', array_keys($freo->refer['pages']), array('password'));
		$page_securities = array_keys($page_securities, true);
		$page_securities = array_map(array($freo->pdo, 'quote'), $page_securities);
		if (!empty($page_securities)) {
			$condition .= ' AND id NOT IN(' . implode(',', $page_securities) . ')';
		}
	}

	//ページ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY sort, id');
	$stmt->bindValue(':id',   $_GET['id']);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$pages = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$pages[$data['id']] = $data;
	}

	//ページID取得
	$page_keys = array_keys($pages);

	//ページ関連データ取得
	$page_associates = freo_associate_page('get', $page_keys);

	//ページフィルター取得
	$page_filters = freo_filter_page('user', $page_keys);

	foreach ($page_filters as $id => $filter) {
		if (!$filter) {
			continue;
		}

		$pages[$id]['comment']   = 'closed';
		$pages[$id]['trackback'] = 'closed';
		$pages[$id]['title']     = str_replace('[$title]', $pages[$id]['title'], $freo->config['page']['filter_title']);
		$pages[$id]['file']      = null;
		$pages[$id]['image']     = null;
		$pages[$id]['memo']      = null;
		$pages[$id]['text']      = str_replace('[$text]', $pages[$id]['text'], $freo->config['page']['filter_text']);

		if ($freo->config['page']['filter_option']) {
			$page_associates[$id]['option'] = array();
		}
	}

	//ページ保護データ取得
	$page_securities = freo_security_page('user', $page_keys);

	foreach ($page_securities as $id => $security) {
		if (!$security) {
			continue;
		}

		$pages[$id]['comment']   = 'closed';
		$pages[$id]['trackback'] = 'closed';
		$pages[$id]['title']     = str_replace('[$title]', $pages[$id]['title'], $freo->config['page']['restriction_title']);
		$pages[$id]['file']      = null;
		$pages[$id]['image']     = null;
		$pages[$id]['memo']      = null;
		$pages[$id]['text']      = str_replace('[$text]', $pages[$id]['text'], $freo->config['page']['restriction_text']);

		if ($freo->config['page']['restriction_option']) {
			$page_associates[$id]['option'] = array();
		}
	}

	//ページタグ取得
	$page_tags = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['tag']) {
			continue;
		}

		$page_tags[$page] = explode(',', $pages[$page]['tag']);
	}

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

	//ページサムネイル取得
	$page_thumbnails = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['file']) {
			continue;
		}
		if (!file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $page . '/' . $pages[$page]['file'])) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_thumbnails/' . $page . '/' . $pages[$page]['file']);

		$page_thumbnails[$page] = array(
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

	//データ割当
	$freo->smarty->assign(array(
		'plugin_page_childs'           => $pages,
		'plugin_page_child_associates' => $page_associates,
		'plugin_page_child_filters'    => $page_filters,
		'plugin_page_child_securities' => $page_securities,
		'plugin_page_child_tags'       => $page_tags,
		'plugin_page_child_files'      => $page_files,
		'plugin_page_child_thumbnails' => $page_thumbnails,
		'plugin_page_child_images'     => $page_images
	));

	return;
}

?>
