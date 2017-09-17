<?php

/*********************************************************************

 エクスポートプラグイン (2012/08/10)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_export()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'admin':
			freo_page_export_admin();
			break;
		case 'admin_post':
			freo_page_export_admin_post();
			break;
		default:
			freo_page_export_default();
	}

	return;
}

/* 管理画面 | エクスポート */
function freo_page_export_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//テーブル取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$query = 'SHOW TABLES';
	} else {
		$query = 'SELECT name FROM sqlite_master WHERE type = \'table\'';
	}
	$stmt = $freo->pdo->query($query);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_exports = array();
	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$plugin_exports[] = $data[0];
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_exports' => $plugin_exports
	));

	return;
}

/* 管理画面 | エクスポート実行 */
function freo_page_export_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//テーブル取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$query = 'SHOW TABLES';
	} else {
		$query = 'SELECT name FROM sqlite_master WHERE type = \'table\'';
	}
	$stmt = $freo->pdo->query($query);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$tables = array();
	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$tables[] = $data[0];
	}

	//データ出力
	$text  = '-- Database: ' . FREO_DATABASE_NAME . ' (' . (FREO_DATABASE_TYPE == 'mysql' ? 'MySQL' : 'SQLite') . ")\n";
	$text .= '-- Datetime: ' . date('Y-m-d H:i:s') . "\n";
	$text .= '-- Host: ' . gethostbyaddr($_SERVER['REMOTE_ADDR']) . "\n";
	$text .= "\n";

	foreach ($tables as $table) {
		if (empty($_POST['target']) or $_POST['target'] == $table) {
			//テーブル定義
			if (FREO_DATABASE_TYPE == 'mysql') {
				$sql  = 'SHOW CREATE TABLE ' . $table . ';';
				$stmt = $freo->pdo->query($sql);
				if (!$stmt) {
					freo_error($freo->pdo->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_NUM)) {
					$text .= "DROP TABLE IF EXISTS $table;\n";
					$text .= $data[1] . ";\n";
					$text .= "\n";
				}
			} else {
				$sql  = 'SELECT sql FROM sqlite_master WHERE tbl_name = \'' . $table . '\';';
				$stmt = $freo->pdo->query($sql);
				if (!$stmt) {
					freo_error($freo->pdo->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_NUM)) {
					$text .= "DROP TABLE IF EXISTS $table;\n";
					$text .= $data[0] . ";\n";
					$text .= "\n";
				}
			}

			//テーブルデータ
			$sql  = 'SELECT * FROM ' . $table . ';';
			$stmt = $freo->pdo->query($sql);
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}

			while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
				$datas = array();
				foreach ($row as $data) {
					if ($data === null) {
						$datas[] = 'NULL';
					} else {
						$datas[] = $freo->pdo->quote($data);
					}
				}
				$text .= "INSERT INTO $table VALUES(" . implode(',', $datas) . ");\n";
			}
			$text .= "\n";
		}
	}

	//出力
	header('Content-Type: text/plain');
	header('Content-Disposition: attachment; filename="' . FREO_DATABASE_NAME . '.sql"');
	echo $text;

	//ログ記録
	freo_log('エクスポートを実行しました。');

	exit;
}

/* エクスポート */
function freo_page_export_default()
{
	global $freo;

	freo_redirect('export/admin');

	return;
}

?>
