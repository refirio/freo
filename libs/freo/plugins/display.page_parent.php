<?php

/*********************************************************************

 親ページ表示プラグイン (2012/07/24)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_display_page_parent()
{
	global $freo;

	if (!$freo->smarty->get_template_vars('page')) {
		return;
	}

	//表示ページ取得
	$page = $freo->smarty->get_template_vars('page');
	$pid  = $page['pid'];

	//ページ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
	$stmt->bindValue(':id',   $pid);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$page = $data;
	} else {
		return;
	}

	//ページ関連データ取得
	$page_associates = freo_associate_page('get', array($page['id']));
	$page_associate  = $page_associates[$page['id']];

	//ページフィルター取得
	$page_filters = freo_filter_page('user', array($page['id']));
	$page_filter  = $page_filters[$page['id']];

	if ($page_filter) {
		$page['comment']   = 'closed';
		$page['trackback'] = 'closed';
		$page['title']     = str_replace('[$title]', $page['title'], $freo->config['page']['filter_title']);
		$page['file']      = null;
		$page['image']     = null;
		$page['memo']      = null;
		$page['text']      = str_replace('[$text]', $page['text'], $freo->config['page']['filter_text']);

		if ($freo->config['page']['filter_option']) {
			$page_associates['option'] = array();
		}
	}

	//ページ保護データ取得
	$page_securities = freo_security_page('user', array($page['id']));
	$page_security   = $page_securities[$page['id']];

	if ($page_security) {
		$page['comment']   = 'closed';
		$page['trackback'] = 'closed';
		$page['title']     = str_replace('[$title]', $page['title'], $freo->config['page']['restriction_title']);
		$page['file']      = null;
		$page['image']     = null;
		$page['memo']      = null;
		$page['text']      = str_replace('[$text]', $page['text'], $freo->config['page']['restriction_text']);

		if ($freo->config['page']['restriction_option']) {
			$page_associate['option'] = array();
		}
	}

	//ページタグ取得
	if ($page['tag']) {
		$page_tag = explode(',', $page['tag']);
	} else {
		$page_tag = array();
	}

	//ページファイル取得
	if ($page['file']) {
		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_files/' . $page['id'] . '/' . $page['file']);

		$page_file = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	} else {
		$page_file = array();
	}

	//ページーサムネイル取得
	if ($page['file'] and file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $page['id'] . '/' . $page['file'])) {
		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_thumbnails/' . $page['id'] . '/' . $page['file']);

		$page_thumbnail = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	} else {
		$page_thumbnail = array();
	}

	//ページイメージ取得
	if ($page['image']) {
		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_images/' . $page['id'] . '/' . $page['image']);

		$page_image = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	} else {
		$page_image = array();
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_page_parent'           => $page,
		'plugin_page_parent_associate' => $page_associate,
		'plugin_page_parent_filter'    => $page_filter,
		'plugin_page_parent_security'  => $page_security,
		'plugin_page_parent_tag'       => $page_tag,
		'plugin_page_parent_file'      => $page_file,
		'plugin_page_parent_thumbnail' => $page_thumbnail,
		'plugin_page_parent_image'     => $page_image
	));

	return;
}

?>
