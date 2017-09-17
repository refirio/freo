<?php

/*********************************************************************

 インポートプラグイン (2012/08/10)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_import()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'admin':
			freo_page_import_admin();
			break;
		default:
			freo_page_import_default();
	}

	return;
}

/* 管理画面 | インポート */
function freo_page_import_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	$i = 0;

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//データ入力
		if (!$freo->smarty->get_template_vars('errors')) {
			if (is_uploaded_file($_FILES['target']['tmp_name'])) {
				if ($fp = fopen($_FILES['target']['tmp_name'], 'r')) {
					$sql      = '';
					$complete = true;

					$freo->pdo->beginTransaction();

					while ($line = fgets($fp)) {
						$line = str_replace("\r\n", "\n", $line);
						$line = str_replace("\r", "\n", $line);

						if ((substr_count($line, '\'') - substr_count($line, '\\\'')) % 2 != 0) {
							$complete = !$complete;
						}

						$sql .= $line;

						if (preg_match('/;$/', trim($line)) and $complete) {
							$stmt = $freo->pdo->query($sql);
							if (!$stmt) {
								$freo->pdo->rollBack();

								freo_error($freo->pdo->errorInfo());
							}

							$sql = '';
							$i++;
						}
					}
					fclose($fp);

					$freo->pdo->commit();

				} else {
					$freo->smarty->append('errors', 'ファイルを読み込めません。');
				}
			} else {
				$freo->smarty->append('errors', 'ファイルを選択してください。');
			}
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'         => freo_token('create'),
		'plugin_import' => $i
	));

	return;
}

/* インポート */
function freo_page_import_default()
{
	global $freo;

	freo_redirect('import/admin');

	return;
}

?>
