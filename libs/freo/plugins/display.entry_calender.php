<?php

/*********************************************************************

 エントリーカレンダー表示プラグイン (2013/01/02)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_display_entry_calender()
{
	global $freo;

	//表示年月日取得
	if (isset($_GET['date'])) {
		$date = $_GET['date'];
	} else {
		$date = null;
	}

	if (preg_match('/^(\d\d\d\d)$/', $date, $matches)) {
		$year  = $matches[1];
		$month = 1;
		$day   = 1;
	} elseif (preg_match('/^(\d\d\d\d)(\d\d)$/', $date, $matches)) {
		$year  = $matches[1];
		$month = $matches[2];
		$day   = 1;
	} elseif (preg_match('/^(\d\d\d\d)(\d\d)\d\d$/', $date, $matches)) {
		$year  = $matches[1];
		$month = $matches[2];
		$day   = 1;
	} else {
		$year  = date('Y');
		$month = date('m');
		$day   = 1;
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
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT datetime FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND DATE_FORMAT(datetime, \'%Y%m\') = :month AND (close IS NULL OR close >= :now2) ' . $condition);
	} else {
		$stmt = $freo->pdo->prepare('SELECT datetime FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND STRFTIME(\'%Y%m\', datetime) = :month AND (close IS NULL OR close >= :now2) ' . $condition);
	}
	$stmt->bindValue(':now1',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':month', $year . $month);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$entry_days = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (preg_match('/^\d\d\d\d-\d\d-(\d\d)/', $data['datetime'], $matches)) {
			$entry_days[intval($matches[1])] = true;
		}
	}

	//祝日定義（2000年～2020年）
	$holidays = Array(
		'2000' => '0101,0110,0211,0320,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2001' => '0101,0108,0211,0212,0320,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2002' => '0101,0114,0211,0321,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2003' => '0101,0113,0211,0321,0429,0503,0504,0505,0721,0915,0923,1013,1103,1123,1124,1223',
		'2004' => '0101,0112,0211,0320,0429,0503,0504,0505,0719,0920,0923,1011,1103,1123,1223',
		'2005' => '0101,0110,0211,0320,0321,0429,0503,0504,0505,0718,0919,0923,1010,1103,1123,1223',
		'2006' => '0101,0102,0109,0211,0321,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2007' => '0101,0108,0211,0212,0321,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2008' => '0101,0114,0211,0320,0429,0503,0504,0505,0506,0721,0915,0923,1013,1103,1123,1124,1223',
		'2009' => '0101,0112,0211,0320,0429,0503,0504,0505,0506,0720,0921,0922,0923,1012,1103,1123,1223',
		'2010' => '0101,0111,0211,0321,0322,0429,0503,0504,0505,0719,0920,0923,1011,1103,1123,1223',
		'2011' => '0101,0110,0211,0321,0429,0503,0504,0505,0718,0919,0923,1010,1103,1123,1223',
		'2012' => '0101,0102,0109,0211,0320,0429,0430,0503,0504,0505,0716,0917,0922,1008,1103,1123,1223,1224',
		'2013' => '0101,0114,0211,0320,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2014' => '0101,0113,0211,0321,0429,0503,0504,0505,0506,0721,0915,0923,1013,1103,1123,1124,1223',
		'2015' => '0101,0112,0211,0321,0429,0503,0504,0505,0506,0720,0921,0922,0923,1012,1103,1123,1223',
		'2016' => '0101,0111,0211,0320,0321,0429,0503,0504,0505,0718,0919,0922,1010,1103,1123,1223',
		'2017' => '0101,0102,0109,0211,0320,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2018' => '0101,0108,0211,0212,0321,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2019' => '0101,0114,0211,0321,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2020' => '0101,0113,0211,0320,0429,0503,0504,0505,0506,0720,0921,0922,1012,1103,1123,1223'
	);

	//投稿日一覧取得
	$key  = date('w', strtotime("$year-$month-01"));
	$last = date('t', strtotime("$year-$month-01"));
	$type = '';

	for ($i = 0; $i < 42; $i++) {
		if ($i == $key) {
			$type = 'day';
		} elseif ($day > $last) {
			$type = '';
		}
		if ($i == 35 and !$type) {
			break;
		}

		if ($type and $i % 7 == 0) {
			$type = 'sunday';
		} elseif ($type and $i % 7 == 6) {
			$type = 'satday';
		} elseif ($type) {
			$type = 'day';
		}

		if ($type) {
			if (isset($holidays[$year]) and strpos($holidays[$year], sprintf('%02d%02d', $month, $day)) !== false) {
				$type = 'sunday';
			} elseif (strpos($freo->config['plugin']['entry_calender']['holiday_yymmdd'], sprintf('%04d%02d%02d', $year, $month, $day)) !== false) {
				$type = 'sunday';
			} elseif (strpos($freo->config['plugin']['entry_calender']['holiday_mmdd'], sprintf('%02d%02d', $month, $day)) !== false) {
				$type = 'sunday';
			} elseif (strpos($freo->config['plugin']['entry_calender']['holiday_dd'], sprintf('%02d', $day)) !== false) {
				$type = 'sunday';
			}
		}

		$calenders[] = array(
			'day'  => $day,
			'date' => sprintf('%04d%02d%02d', $year, $month, $day),
			'type' => $type,
			'flag' => isset($entry_days[$day]) ? true : false
		);

		if ($type) {
			$day++;
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_entry_calenders'         => $calenders,
		'plugin_entry_calender_year'     => $year,
		'plugin_entry_calender_month'    => $month,
		'plugin_entry_calender_previous' => date('Ym', strtotime('-1 month', strtotime($year . '-' . $month . '-01'))),
		'plugin_entry_calender_next'     => date('Ym', strtotime('+1 month', strtotime($year . '-' . $month . '-01')))
	));

	return;
}

?>
