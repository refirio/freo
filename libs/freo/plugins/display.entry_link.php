<?php

/*********************************************************************

 エントリー移動プラグイン (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_display_entry_link()
{
	global $freo;

	if (!$freo->smarty->get_template_vars('entry')) {
		return;
	}

	$entry = $freo->smarty->get_template_vars('entry');

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

	//前エントリー取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND datetime < :datetime ' . $condition . ' ORDER BY datetime DESC LIMIT 1');
	$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
	$stmt->bindValue(':datetime', $entry['datetime']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$previous = $data;

		//エントリーフィルター取得
		$previous_filters = freo_filter_entry('user', array($previous['id']));
		$previous_filter  = $previous_filters[$previous['id']];

		if ($previous_filter) {
			$previous['comment']   = 'closed';
			$previous['trackback'] = 'closed';
			$previous['title']     = str_replace('[$title]', $previous['title'], $freo->config['entry']['filter_title']);
			$previous['file']      = null;
			$previous['image']     = null;
			$previous['memo']      = null;
			$previous['text']      = str_replace('[$text]', $previous['text'], $freo->config['entry']['filter_text']);
		}

		//エントリー保護データ取得
		$previous_securities = freo_security_entry('user', array($previous['id']));
		$previous_security   = $previous_securities[$previous['id']];

		if ($previous_security) {
			$previous['comment']   = 'closed';
			$previous['trackback'] = 'closed';
			$previous['title']     = str_replace('[$title]', $previous['title'], $freo->config['entry']['restriction_title']);
			$previous['file']      = null;
			$previous['image']     = null;
			$previous['memo']      = null;
			$previous['text']      = str_replace('[$text]', $previous['text'], $freo->config['entry']['restriction_text']);
		}
	} else {
		$previous          = array();
		$previous_filter   = null;
		$previous_security = null;
	}

	//次エントリー取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND datetime > :datetime ' . $condition . ' ORDER BY datetime LIMIT 1');
	$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
	$stmt->bindValue(':datetime', $entry['datetime']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$next = $data;

		//エントリーフィルター取得
		$next_filters = freo_filter_entry('user', array($next['id']));
		$next_filter  = $next_filters[$next['id']];

		if ($next_filter) {
			$next['comment']   = 'closed';
			$next['trackback'] = 'closed';
			$next['title']     = str_replace('[$title]', $next['title'], $freo->config['entry']['filter_title']);
			$next['file']      = null;
			$next['image']     = null;
			$next['memo']      = null;
			$next['text']      = str_replace('[$text]', $next['text'], $freo->config['entry']['filter_text']);
		}

		//エントリー保護データ取得
		$next_securities = freo_security_entry('user', array($next['id']));
		$next_security   = $next_securities[$next['id']];

		if ($next_security) {
			$next['comment']   = 'closed';
			$next['trackback'] = 'closed';
			$next['title']     = str_replace('[$title]', $next['title'], $freo->config['entry']['restriction_title']);
			$next['file']      = null;
			$next['image']     = null;
			$next['memo']      = null;
			$next['text']      = str_replace('[$text]', $next['text'], $freo->config['entry']['restriction_text']);
		}
	} else {
		$next          = array();
		$next_filter   = null;
		$next_security = null;
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_entry_link_previous'          => $previous,
		'plugin_entry_link_previous_filter'   => $previous_filter,
		'plugin_entry_link_previous_security' => $previous_security,
		'plugin_entry_link_next'              => $next,
		'plugin_entry_link_next_filter'       => $next_filter,
		'plugin_entry_link_next_security'     => $next_security
	));

	return;
}

?>
