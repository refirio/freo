<?php

/*********************************************************************

 ページ移動プラグイン (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_display_page_link()
{
	global $freo;

	if (!$freo->smarty->get_template_vars('page')) {
		return;
	}

	$page = $freo->smarty->get_template_vars('page');

	if (!$page['pid']) {
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

	//前ページ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :pid AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND sort < :sort AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY sort DESC LIMIT 1');
	$stmt->bindValue(':pid',  $page['pid']);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$stmt->bindValue(':sort', $page['sort']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$previous = $data;

		//ページフィルター取得
		$previous_filters = freo_filter_page('user', array($previous['id']));
		$previous_filter  = $previous_filters[$previous['id']];

		if ($previous_filter) {
			$previous['comment']   = 'closed';
			$previous['trackback'] = 'closed';
			$previous['title']     = str_replace('[$title]', $previous['title'], $freo->config['page']['filter_title']);
			$previous['file']      = null;
			$previous['image']     = null;
			$previous['memo']      = null;
			$previous['text']      = str_replace('[$text]', $previous['text'], $freo->config['page']['filter_text']);
		}

		//ページ保護データ取得
		$previous_securities = freo_security_page('user', array($previous['id']));
		$previous_security   = $previous_securities[$previous['id']];

		if ($previous_security) {
			$previous['comment']   = 'closed';
			$previous['trackback'] = 'closed';
			$previous['title']     = str_replace('[$title]', $previous['title'], $freo->config['page']['restriction_title']);
			$previous['file']      = null;
			$previous['image']     = null;
			$previous['memo']      = null;
			$previous['text']      = str_replace('[$text]', $previous['text'], $freo->config['page']['restriction_text']);
		}
	} else {
		$previous          = array();
		$previous_filter   = null;
		$previous_security = null;
	}

	//次ページ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :pid AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND sort > :sort AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY sort LIMIT 1');
	$stmt->bindValue(':pid',  $page['pid']);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$stmt->bindValue(':sort', $page['sort']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$next = $data;

		//ページフィルター取得
		$next_filters = freo_filter_page('user', array($next['id']));
		$next_filter  = $next_filters[$next['id']];

		if ($next_filter) {
			$next['comment']   = 'closed';
			$next['trackback'] = 'closed';
			$next['title']     = str_replace('[$title]', $next['title'], $freo->config['page']['filter_title']);
			$next['file']      = null;
			$next['image']     = null;
			$next['memo']      = null;
			$next['text']      = str_replace('[$text]', $next['text'], $freo->config['page']['filter_text']);
		}

		//ページ保護データ取得
		$next_securities = freo_security_page('user', array($next['id']));
		$next_security   = $next_securities[$next['id']];

		if ($next_security) {
			$next['comment']   = 'closed';
			$next['trackback'] = 'closed';
			$next['title']     = str_replace('[$title]', $next['title'], $freo->config['page']['restriction_title']);
			$next['file']      = null;
			$next['image']     = null;
			$next['memo']      = null;
			$next['text']      = str_replace('[$text]', $next['text'], $freo->config['page']['restriction_text']);
		}
	} else {
		$next          = array();
		$next_filter   = null;
		$next_security = null;
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_page_link_previous'          => $previous,
		'plugin_page_link_previous_filter'   => $previous_filter,
		'plugin_page_link_previous_security' => $previous_security,
		'plugin_page_link_next'              => $next,
		'plugin_page_link_next_filter'       => $next_filter,
		'plugin_page_link_next_security'     => $next_security
	));

	return;
}

?>
