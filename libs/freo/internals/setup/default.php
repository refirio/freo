<?php

/*********************************************************************

 freo | セットアップ (2012/09/30)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_setup.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//リクエストメソッドに応じた処理を実行
	$errors = array();

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$errors[] = '不正なアクセスです。';
		}

		//入力データ検証
		if (empty($freo->user['authority']) and !$errors) {
			$errors = freo_validate_setup('insert', $_POST);
		}

		//エラー確認
		if (!$errors) {
			//セットアップ実行へ移動
			if (empty($freo->user['authority'])) {
				$_SESSION['input'] = $_POST['setup'];
			}

			freo_redirect('setup/execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ出力
	echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ja\" lang=\"ja\" dir=\"ltr\">\n";
	echo "<head>\n";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
	echo "<title>セットアップ</title>\n";

	if (file_exists(FREO_CSS_DIR)) {
		echo "<link rel=\"stylesheet\" href=\"" . FREO_HTTP_URL . FREO_CSS_DIR . "common.css\" type=\"text/css\" media=\"all\" />\n";
		echo "<link rel=\"stylesheet\" href=\"" . FREO_HTTP_URL . FREO_CSS_DIR . "setup.css\" type=\"text/css\" media=\"all\" />\n";
	}
	if (file_exists(FREO_JS_DIR)) {
		echo "<script type=\"text/javascript\" src=\"" . FREO_HTTP_URL . FREO_JS_DIR . "jquery.js\"></script>\n";
		echo "<script type=\"text/javascript\" src=\"" . FREO_HTTP_URL . FREO_JS_DIR . "common.js\"></script>\n";
		echo "<script type=\"text/javascript\" src=\"" . FREO_HTTP_URL . FREO_JS_DIR . "setup.js\"></script>\n";
	}

	echo "</head>\n";
	echo "<body>\n";
	echo "<h1>freo</h1>\n";

	if (isset($_GET['error']) and $_GET['error'] == 1) {
		echo "<p>不正なアクセスです。</p>\n";
	} elseif ($errors) {
		echo "<p>エラーが発生しました。</p>\n";
		echo "<ul>\n";

		foreach ($errors as $error) {
			echo "<li>$error</li>\n";
		}

		echo "</ul>\n";
	} elseif (isset($_GET['exec']) and $_GET['exec'] == 'setup') {
		echo "<p>セットアップが完了しました。</p>\n";
	}

	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users');

	if (!$stmt or !($data = $stmt->fetch(PDO::FETCH_NUM))) {
		echo "<p>メールアドレスと、設定したいユーザーIDとパスワードを入力してください。</p>\n";
		echo "<form action=\"" . ($freo->core['https_file'] ? $freo->core['https_file'] : $freo->core['http_file']) . "/setup\" method=\"post\">\n";
		echo "<fieldset>\n";
		echo "<legend>セットアップ実行フォーム</legend>\n";
		echo "<input type=\"hidden\" name=\"freo[token]\" value=\"" . freo_token('create') . "\" />\n";
		echo "<dl>\n";
		echo "<dt>メールアドレス</dt>\n";
		echo "<dd><input type=\"text\" name=\"setup[mail]\" size=\"30\" value=\"" . (isset($_POST['setup']['mail']) ? htmlspecialchars($_POST['setup']['mail'], ENT_QUOTES) : '') . "\" /></dd>\n";
		echo "<dt>ユーザーID</dt>\n";
		echo "<dd><input type=\"text\" name=\"setup[user]\" size=\"20\" value=\"" . (isset($_POST['setup']['user']) ? htmlspecialchars($_POST['setup']['user'], ENT_QUOTES) : '') . "\" /></dd>\n";
		echo "<dt>パスワード</dt>\n";
		echo "<dd><input type=\"password\" name=\"setup[password]\" size=\"20\" value=\"" . (isset($_POST['setup']['password']) ? htmlspecialchars($_POST['setup']['password'], ENT_QUOTES) : '') . "\" /></dd>\n";
		echo "</dl>\n";
		echo "<p><input type=\"submit\" value=\"セットアップ実行\" /></p>\n";
		echo "</fieldset>\n";
		echo "</form>\n";
	} else {
		if (isset($_GET['exec']) and $_GET['exec'] == 'setup') {
			echo "<ul>\n";
			echo "<li><a href=\"" . $freo->core['http_file'] . "\">記事一覧画面を表示</a></li>\n";
			echo "<li><a href=\"" . ($freo->core['https_file'] ? $freo->core['https_file'] : $freo->core['http_file']) . "/login\">管理画面にログイン</a></li>\n";
			echo "</ul>\n";
		} elseif (isset($freo->user['authority']) and $freo->user['authority'] == 'root') {
			echo "<p>セットアップを実行します。</p>\n";
			echo "<form action=\"" . ($freo->core['https_file'] ? $freo->core['https_file'] : $freo->core['http_file']) . "/setup\" method=\"post\">\n";
			echo "<fieldset>\n";
			echo "<legend>セットアップ実行フォーム</legend>\n";
			echo "<input type=\"hidden\" name=\"freo[token]\" value=\"" . freo_token('create') . "\" />\n";
			echo "<p><input type=\"submit\" value=\"セットアップ実行\" /></p>\n";
			echo "</fieldset>\n";
			echo "</form>\n";
		} else {
			echo "<p>管理者ページにログイン後、再度アクセスしてください。</p>\n";
		}
	}

	echo "</body>\n";
	echo "</html>\n";

	return;
}

?>
