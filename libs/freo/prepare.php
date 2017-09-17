<?php

/*********************************************************************

 freo | 設定編集 (2013/04/10)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/config.php';

//PHP仕様確認
if (isset($_GET['mode']) and $_GET['mode'] == 'checker') {
	require_once FREO_MAIN_DIR . 'freo/checker.php';

	exit;
}

//最低動作環境確認
$errors = array();

if (phpversion() < 5) {
	$errors[] = 'PHP5が利用できません。（現在のバージョンは' . phpversion() . 'です。）';
}
if (!class_exists('pdo')) {
	$errors[] = 'PDOが利用できません。';
}
if (!phpversion('pdo_mysql') and !phpversion('pdo_sqlite')) {
	$errors[] = 'MySQLとSQLiteのいずれも利用できません。';
}
if (!function_exists('mb_language')) {
	$errors[] = 'マルチバイト関数が利用できません。';
}

if (!empty($errors)) {
	$errors[] = '現在の環境ではfreoは動作しません。';

	freo_setup_error(implode('', $errors));
}

//設置URLを反映
if (isset($_POST['token']) and $_POST['token'] == session_id()) {
	//入力内容をチェック
	if ($_POST['url'] == '') {
		freo_setup_error('設置URLが入力されていません。');
	} elseif (strlen($_POST['url']) !== mb_strlen($_POST['url'])) {
		freo_setup_error('設置URLは半角で入力してください。');
	}
	if ($_POST['database_type'] != 'sqlite3' and $_POST['database_type'] != 'sqlite2' and $_POST['database_type'] != 'mysql') {
		freo_setup_error('使用データベースの入力内容が不正です。');
	}
	if ($_POST['database_type'] == 'mysql') {
		if ($_POST['database_host'] != '' and strlen($_POST['database_host']) !== mb_strlen($_POST['database_host'])) {
			freo_setup_error('接続先は半角で入力してください。');
		}
		if ($_POST['database_port'] != '' and strlen($_POST['database_port']) !== mb_strlen($_POST['database_port'])) {
			freo_setup_error('ポートは半角で入力してください。');
		}
		if ($_POST['database_user'] != '' and strlen($_POST['database_user']) !== mb_strlen($_POST['database_user'])) {
			freo_setup_error('ユーザー名は半角で入力してください。');
		}
		if ($_POST['database_password'] != '' and strlen($_POST['database_password']) !== mb_strlen($_POST['database_password'])) {
			freo_setup_error('パスワードは半角で入力してください。');
		}
		if ($_POST['database_charset'] != '' and strlen($_POST['database_charset']) !== mb_strlen($_POST['database_charset'])) {
			freo_setup_error('文字コードは半角で入力してください。');
		}
		if ($_POST['database_name'] != '' and strlen($_POST['database_name']) !== mb_strlen($_POST['database_name'])) {
			freo_setup_error('データベース名は半角で入力してください。');
		}
		if ($_POST['database_prefix'] != '' and strlen($_POST['database_prefix']) !== mb_strlen($_POST['database_prefix'])) {
			freo_setup_error('テーブル名のプレフィックスは半角で入力してください。');
		}
	}

	$_POST['url']               = str_replace("'", "\\'", $_POST['url']);
	$_POST['database_type']     = str_replace("'", "\\'", $_POST['database_type']);
	$_POST['database_host']     = str_replace("'", "\\'", $_POST['database_host']);
	$_POST['database_port']     = str_replace("'", "\\'", $_POST['database_port']);
	$_POST['database_user']     = str_replace("'", "\\'", $_POST['database_user']);
	$_POST['database_password'] = str_replace("'", "\\'", $_POST['database_password']);
	$_POST['database_charset']  = str_replace("'", "\\'", $_POST['database_charset']);
	$_POST['database_name']     = str_replace("'", "\\'", $_POST['database_name']);
	$_POST['database_prefix']   = str_replace("'", "\\'", $_POST['database_prefix']);

	//データベースへ接続テスト
	if ($_POST['database_type'] == 'mysql') {
		try {
			$pdo = new PDO(
				'mysql:dbname=' . $_POST['database_name'] . ';host=' . $_POST['database_host'] . ($_POST['database_port'] ? ';port=' . $_POST['database_port'] : ''),
				$_POST['database_user'],
				$_POST['database_password'],
				array(
					PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
					PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
				)
			);
		} catch (PDOException $e) {
			freo_setup_error('データベースへ接続できません : ' . $e->getMessage());
		}
	}

	$url = parse_url($_POST['url']);

	//設定内容を定義
	$configs = array(
		'FREO_HTTP_URL'          => $_POST['url'],
		'FREO_DATABASE_TYPE'     => $_POST['database_type'],
		'FREO_DATABASE_HOST'     => $_POST['database_host'],
		'FREO_DATABASE_PORT'     => $_POST['database_port'],
		'FREO_DATABASE_USER'     => $_POST['database_user'],
		'FREO_DATABASE_PASSWORD' => $_POST['database_password'],
		'FREO_DATABASE_CHARSET'  => $_POST['database_charset'],
		'FREO_DATABASE_NAME'     => $_POST['database_name'],
		'FREO_DATABASE_PREFIX'   => $_POST['database_prefix']
	);

	//chmod使用確認
	if (defined('FREO_PERMISSION_MODE') and FREO_PERMISSION_MODE and @chmod(FREO_TEMPLATE_COMPILE_DIR, 0707)) {
		$chmod = true;
	} else {
		$chmod = false;
	}

	if ($chmod) {
		//パーミッションを変更
		if (@chmod('config.php', FREO_PERMISSION_FILE) === false) {
			freo_setup_error('設定ファイル config.php のパーミッションを変更できません。');
		}

		//データを読み込み
		$data = file_get_contents('config.php');
		if ($data === false) {
			freo_setup_error('設定ファイル config.php を読み込めません。');
		}

		//データを置換
		foreach ($configs as $key => $value) {
			$before = 'define\(\'' . $key . '\', \'.*\'\);';
			$after  = 'define(\'' . $key . '\', \'' . $value . '\');';

			$data = preg_replace('/' . $before . '/', $after, $data);
		}

		//データを書き込み
		if (file_put_contents('config.php', $data) === false) {
			freo_setup_error('設定ファイル config.php に書き込めません。');
		}

		//パーミッションを変更
		if (@chmod('config.php', FREO_PERMISSION_PHP) === false) {
			freo_setup_error('設定ファイル config.php のパーミッションを変更できません。');
		}

		//パーミッションを変更
		if (@chmod(FREO_JS_DIR . 'common.js', FREO_PERMISSION_FILE) === false) {
			freo_setup_error('外部JSファイル ' . FREO_JS_DIR . 'common.js のパーミッションを変更できません。');
		}

		//データを読み込み
		$data = file_get_contents(FREO_JS_DIR . 'common.js');
		if ($data === false) {
			freo_setup_error('外部JSファイル ' . FREO_JS_DIR . 'common.js を読み込めません。');
		}

		//データを置換
		$before = 'var freo_path = \'.*\';';
		$after  = 'var freo_path = \'' . $url['path'] . '\';';

		$data = preg_replace('/' . $before . '/', $after, $data);

		//データを書き込み
		if (file_put_contents(FREO_JS_DIR . 'common.js', $data) === false) {
			freo_setup_error('外部JSファイル ' . FREO_JS_DIR . 'common.js に書き込めません。');
		}

		//パーミッションを変更
		if (@chmod(FREO_JS_DIR . 'common.js', FREO_PERMISSION_JS) === false) {
			freo_setup_error('外部JSファイル ' . FREO_JS_DIR . 'common.js のパーミッションを変更できません。');
		}
	}

	//データ出力
	echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ja\" lang=\"ja\" dir=\"ltr\">\n";
	echo "<head>\n";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
	echo "<title>設定編集</title>\n";

	if (file_exists(FREO_CSS_DIR . 'common.css')) {
		echo "<link rel=\"stylesheet\" href=\"" . FREO_CSS_DIR . "common.css\" type=\"text/css\" media=\"all\" />\n";
	}
	if (file_exists(FREO_CSS_DIR . 'setup.css')) {
		echo "<link rel=\"stylesheet\" href=\"" . FREO_CSS_DIR . "setup.css\" type=\"text/css\" media=\"all\" />\n";
	}

	echo "</head>\n";
	echo "<body>\n";
	echo "<h1>freo</h1>\n";
	echo "<h2>設定編集</h2>\n";

	if ($chmod) {
		echo "<p>設定ファイルの編集が完了しました。以下のリンクからセットアップに進んでください。</p>\n";
		echo "<p><code>config.php</code> と <code>js/common.js</code> がPHPによって書き換えられているので、必要に応じてダウンロードしておいてください。</p>\n";
		echo "<ul>\n";
		echo "<li><a href=\"index.php/setup\">セットアップ</a></li>\n";
		echo "</ul>\n";
	} else {
		echo "<p>ご利用のサーバーは、<strong>PHPから直接パーミッションを設定することができない</strong>環境のようです。テキストエディタで設定ファイルを編集し、FTPソフトでパーミッションを設定する必要があります。</p>\n";
		echo "<h3>config.phpの編集</h3>\n";
		echo "<p><code>config.php</code> にある設置URLを</p>\n";
		echo "<pre><code>//設置URL\n";
		echo "define('FREO_HTTP_URL', '" . $configs['FREO_HTTP_URL'] . "');</code></pre>\n";
		echo "<p>と設定してください。</p>\n";
		echo "<p>また、データベースの設定を</p>\n";
		echo "<pre><code>//データベースの種類(sqlite3 ... SQLite3 / sqlite2 ... SQLite2 / mysql ... MySQL)\n";
		echo "define('FREO_DATABASE_TYPE', '" . $configs['FREO_DATABASE_TYPE'] . "');\n";
		echo "\n";
		echo "//接続先(MySQL用)\n";
		echo "define('FREO_DATABASE_HOST', '" . $configs['FREO_DATABASE_HOST'] . "');\n";
		echo "\n";
		echo "//ポート(MySQL用)\n";
		echo "define('FREO_DATABASE_PORT', '" . $configs['FREO_DATABASE_PORT'] . "');\n";
		echo "\n";
		echo "//ユーザー名(MySQL用)\n";
		echo "define('FREO_DATABASE_USER', '" . $configs['FREO_DATABASE_USER'] . "');\n";
		echo "\n";
		echo "//パスワード(MySQL用)\n";
		echo "define('FREO_DATABASE_PASSWORD', '" . $configs['FREO_DATABASE_PASSWORD'] . "');\n";
		echo "\n";
		echo "//文字コード(MySQL用)\n";
		echo "define('FREO_DATABASE_CHARSET', '" . $configs['FREO_DATABASE_CHARSET'] . "');\n";
		echo "\n";
		echo "//データベース格納ディレクトリ(SQLite用)\n";
		echo "define('FREO_DATABASE_DIR', 'database/');\n";
		echo "\n";
		echo "//データベース名\n";
		echo "define('FREO_DATABASE_NAME', '" . $configs['FREO_DATABASE_NAME'] . "');\n";
		echo "\n";
		echo "//テーブル名のプレフィックス\n";
		echo "define('FREO_DATABASE_PREFIX', '" . $configs['FREO_DATABASE_PREFIX'] . "');</code></pre>\n";
		echo "<p>と設定してください。（データベースの設定は、もともとの設定内容と同じなら変更の必要はありません。）</p>\n";
		echo "<p>また、セットアップの設定を</p>\n";
		echo "<pre><code>//パーミッション自動設定の利用(true ... 利用する / false ... 利用しない)\n";
		echo "define('FREO_PERMISSION_MODE', false);</code></pre>\n";
		echo "<p>と設定してください。</p>\n";
		echo "<h3>js/common.jsの編集</h3>\n";
		echo "<p><code>js/common.js</code> にある設置パスを</p>\n";
		echo "<pre><code>//設置パス\n";
		echo "var freo_path = '" . $url['path'] . "';</code></pre>\n";
		echo "<p>に設定してください。</p>\n";
		echo "<h3>パーミッションの設定</h3>\n";
		echo "<p>FTPソフトで以下のファイルのパーミッションを設定してください。</p>\n";
		echo "<table summary=\"パーミッション一覧\">\n";
		echo "<tr><th>対象</th><th>パーミッション</th></tr>\n";
		echo "<tr><td><code>configs/</code> 内のファイル</td><td><code>606</code> に設定</td></tr>\n";
		echo "<tr><td><code>configs/plugins/</code> 内のファイル</td><td><code>606</code> に設定</td></tr>\n";
		echo "<tr><td><code>database/</code></td><td><code>707</code> に設定</td></tr>\n";
		echo "<tr><td><code>database/freo.db</code></td><td><code>606</code> に設定</td></tr>\n";
		echo "<tr><td><code>files/</code> 内のディレクトリ</td><td><code>707</code> に設定</td></tr>\n";
		echo "<tr><td><code>files/temporaries/</code> 内のディレクトリ</td><td><code>707</code> に設定</td></tr>\n";
		echo "<tr><td><code>templates_c/</code></td><td><code>707</code> に設定</td></tr>\n";
		echo "</table>\n";
		echo "<h3>セットアップ</h3>\n";
		echo "<p>設定ファイルの編集とパーミッションの設定が完了したら、以下のリンクからセットアップに進んでください。</p>\n";
		echo "<ul>\n";
		echo "<li><a href=\"index.php/setup\">セットアップ</a></li>\n";
		echo "</ul>\n";
	}

	echo "</body>\n";
	echo "</html>\n";

	exit;
}

//設置URLを取得
$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];

if (preg_match('/^(.+)index.php$/', $url, $matches)) {
	$url = $matches[1];
}

//データ出力
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ja\" lang=\"ja\" dir=\"ltr\">\n";
echo "<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
echo "<title>設定編集</title>\n";

if (file_exists(FREO_CSS_DIR . 'common.css')) {
	echo "<link rel=\"stylesheet\" href=\"" . FREO_CSS_DIR . "common.css\" type=\"text/css\" media=\"all\" />\n";
}
if (file_exists(FREO_CSS_DIR . 'setup.css')) {
	echo "<link rel=\"stylesheet\" href=\"" . FREO_CSS_DIR . "setup.css\" type=\"text/css\" media=\"all\" />\n";
}
if (file_exists(FREO_JS_DIR . 'jquery.js')) {
	echo "<script type=\"text/javascript\" src=\"" . FREO_JS_DIR . "jquery.js\"></script>\n";
	echo "<script type=\"text/javascript\">\n";
	echo "$(document).ready(function() {\n";
	echo "	if ($('#database_type').val() != 'mysql') {\n";
	echo "		$('#database_mysql').hide();\n";
	echo "	}\n";
	echo "	$('#database_type').change(function() {\n";
	echo "		if ($(this).val() == 'mysql') {\n";
	echo "			$('#database_mysql').show();\n";
	echo "		} else {\n";
	echo "			$('#database_mysql').hide();\n";
	echo "		}\n";
	echo "	});\n";
	echo "});\n";
	echo "</script>\n";
}

echo "</head>\n";
echo "<body>\n";
echo "<h1>freo</h1>\n";
echo "<h2>設定編集</h2>\n";

echo "<ul>\n";
echo "<li><a href=\"index.php?mode=checker\">PHPの仕様を確認する</a>。</li>\n";
echo "</ul>\n";
echo "<ul>\n";
echo "<li>freoの設定ファイルを編集します。</li>\n";
echo "<li>各情報を入力してください。</li>\n";
echo "</ul>\n";
echo "<form action=\"index.php\" method=\"post\">\n";
echo "<fieldset>\n";
echo "<legend>設定ファイル編集実行フォーム</legend>\n";
echo "<input type=\"hidden\" name=\"token\" value=\"" . session_id() . "\" />\n";

echo "<dl>\n";
echo "<dt>設置URL</dt>\n";
echo "<dd><input type=\"text\" name=\"url\" size=\"30\" value=\"" . $url . "\" /></dd>\n";
echo "<dt>使用データベース</dt>\n";
echo "<dd>\n";
echo "<select name=\"database_type\" id=\"database_type\">\n";

if (phpversion('pdo_sqlite')) {
	echo "<option value=\"sqlite3\">SQLite3</option>\n";
	echo "<option value=\"sqlite2\">SQLite2</option>\n";
}
if (phpversion('pdo_mysql')) {
	echo "<option value=\"mysql\">MySQL</option>\n";
}

echo "</select>\n";
echo "</dd>\n";
echo "</dl>\n";

echo "<dl id=\"database_mysql\">\n";
echo "<dt>接続先</dt>\n";
echo "<dd><input type=\"text\" name=\"database_host\" size=\"30\" value=\"\" /></dd>\n";
echo "<dt>ポート</dt>\n";
echo "<dd><input type=\"text\" name=\"database_port\" size=\"30\" value=\"\" /></dd>\n";
echo "<dt>ユーザー名</dt>\n";
echo "<dd><input type=\"text\" name=\"database_user\" size=\"30\" value=\"\" /></dd>\n";
echo "<dt>パスワード</dt>\n";
echo "<dd><input type=\"text\" name=\"database_password\" size=\"30\" value=\"\" /></dd>\n";
echo "<dt>文字コード</dt>\n";
echo "<dd><input type=\"text\" name=\"database_charset\" size=\"30\" value=\"\" /></dd>\n";
echo "</dl>\n";

echo "<dl>\n";
echo "<dt>データベース名</dt>\n";
echo "<dd><input type=\"text\" name=\"database_name\" size=\"30\" value=\"freo.db\" /></dd>\n";
echo "<dt>テーブル名のプレフィックス</dt>\n";
echo "<dd><input type=\"text\" name=\"database_prefix\" size=\"30\" value=\"freo_\" /></dd>\n";
echo "</dl>\n";

echo "<p><input type=\"submit\" value=\"設定編集\" /></p>\n";
echo "</fieldset>\n";
echo "</form>\n";

echo "</body>\n";
echo "</html>\n";

exit;

/* エラー表示 */
function freo_setup_error($message)
{
	if (is_array($message)) {
		list($pdo_state, $pdo_code, $pdo_message) = $message;

		$message = $pdo_message;
	}

	echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ja\" lang=\"ja\" dir=\"ltr\">\n";
	echo "<head>\n";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
	echo "<title>エラーが発生しました</title>\n";

	if (file_exists(FREO_CSS_DIR . 'common.css')) {
		echo "<link rel=\"stylesheet\" href=\"" . FREO_CSS_DIR . "common.css\" type=\"text/css\" media=\"all\" />\n";
	}

	echo "</head>\n";
	echo "<body>\n";
	echo "<h1>freo</h1>\n";
	echo "<h2>エラーが発生しました</h2>\n";
	echo "<p>" . $message . "</p>\n";
	echo "</body>\n";
	echo "</html>\n";

	exit;
}

?>
