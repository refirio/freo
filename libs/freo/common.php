<?php

/*********************************************************************

 freo | 共通関数 (2013/10/22)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//グローバル変数定義
$freo = (object)null;

/* セッション開始 */
function freo_session()
{
	global $freo;

	if (FREO_HTTPS_URL and !session_id()) {
		$session_name = session_name();

		if (isset($_POST[$session_name])) {
			session_id($_POST[$session_name]);
		} elseif (isset($_GET[$session_name])) {
			session_id($_GET[$session_name]);
		}
	}

	if (FREO_HTTPS_URL and isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] != 'off') {
		$info = parse_url(FREO_HTTPS_URL);
	} else {
		$info = parse_url(FREO_HTTP_URL);
	}

	session_set_cookie_params(FREO_SESSION_LIFETIME, $info['path']);
	session_cache_limiter(FREO_SESSION_CACHE);
	session_start();

	return;
}

/* テンプレート設定 */
function freo_smarty()
{
	global $freo;

	$freo->smarty = new Smarty();

	$freo->smarty->template_dir      = FREO_TEMPLATE_DIR;
	$freo->smarty->compile_dir       = FREO_TEMPLATE_COMPILE_DIR;
	$freo->smarty->default_modifiers = array('escape');

	return;
}

/* データベース接続 */
function freo_pdo()
{
	global $freo;

	if (FREO_DATABASE_TYPE == 'sqlite3') {
		$dsn = 'sqlite:' . FREO_DATABASE_DIR . FREO_DATABASE_NAME;
	} elseif (FREO_DATABASE_TYPE == 'sqlite2') {
		$dsn = 'sqlite2:' . FREO_DATABASE_DIR . FREO_DATABASE_NAME;
	} else {
		$dsn = 'mysql:dbname=' . FREO_DATABASE_NAME . ';host=' . FREO_DATABASE_HOST . (FREO_DATABASE_PORT ? ';port=' . FREO_DATABASE_PORT : '');
	}

	if (FREO_DATABASE_TYPE == 'mysql') {
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
		);
	} else {
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
		);
	}

	try {
		$freo->pdo = new PDO($dsn, FREO_DATABASE_USER, FREO_DATABASE_PASSWORD, $options);
	} catch (PDOException $e) {
		freo_error($e->getMessage());
	}

	if (FREO_DATABASE_TYPE == 'mysql' and FREO_DATABASE_CHARSET) {
		$stmt = $freo->pdo->query('SET NAMES ' . FREO_DATABASE_CHARSET);
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	return;
}

/* 基本情報取得 */
function freo_core()
{
	global $freo;

	if (FREO_REWRITE_MODE) {
		$http_file  = preg_replace('/\/$/', '', FREO_HTTP_URL);
		$https_file = preg_replace('/\/$/', '', FREO_HTTPS_URL ? FREO_HTTPS_URL : FREO_HTTP_URL);
	} else {
		$http_file  = FREO_HTTP_URL . FREO_MAIN_FILE;
		$https_file = (FREO_HTTPS_URL ? FREO_HTTPS_URL : FREO_HTTP_URL) . FREO_MAIN_FILE;
	}

	$freo->core = array(
		'session_name' => session_name(),
		'session_id'   => session_id(),
		'http_url'     => FREO_HTTP_URL,
		'https_url'    => FREO_HTTPS_URL ? FREO_HTTPS_URL : FREO_HTTP_URL,
		'http_file'    => $http_file,
		'https_file'   => $https_file,
		'version'      => FREO_VERSION,
		'lib'          => null,
		'template'     => null,
		'plugin'       => null
	);

	return;
}

/* ブラウザ情報取得 */
function freo_agent()
{
	global $freo;

	//データ調整
	if (!isset($_SERVER['HTTP_USER_AGENT'])) {
		$_SERVER['HTTP_USER_AGENT'] = null;
	}

	//キャリア取得
	if (isset($_GET['freo']['agent']['career'])) {
		$_SESSION['freo']['agent']['career'] = $_GET['freo']['agent']['career'];
	}
	if (isset($_SESSION['freo']['agent']['career'])) {
		$freo->agent['career'] = $_SESSION['freo']['agent']['career'];
	} elseif (preg_match('/DoCoMo/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['career'] = 'docomo';
	} elseif (preg_match('/(SoftBank|Vodafone)/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['career'] = 'softbank';
	} elseif (preg_match('/KDDI/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['career'] = 'au';
	} elseif (preg_match('/WILLCOM/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['career'] = 'willcom';
	} elseif (preg_match('/emobile/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['career'] = 'emobile';
	} else {
		$freo->agent['career'] = null;
	}

	//接続環境取得
	if (isset($_GET['freo']['agent']['type'])) {
		$_SESSION['freo']['agent']['type'] = $_GET['freo']['agent']['type'];
	}
	if (isset($_SESSION['freo']['agent']['type'])) {
		$freo->agent['type'] = $_SESSION['freo']['agent']['type'];
	} elseif ($freo->agent['career']) {
		$freo->agent['type'] = 'mobile';
	} elseif (preg_match('/Android/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['type'] = 'android';
	} elseif (preg_match('/iPad/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['type'] = 'ipad';
	} elseif (preg_match('/iPhone/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['type'] = 'iphone';
	} elseif (preg_match('/iPod/', $_SERVER['HTTP_USER_AGENT'])) {
		$freo->agent['type'] = 'ipod';
	} else {
		$freo->agent['type'] = 'pc';
	}

	//コンテンツタイプ取得
	if ($freo->agent['career'] == 'au' or $freo->agent['type'] != 'mobile') {
		$freo->agent['content'] = 'text/html';
	} else {
		$freo->agent['content'] = 'application/xhtml+xml';
	}

	//文字コード取得
	if ($freo->agent['career'] == 'softbank' or $freo->agent['type'] != 'mobile') {
		$freo->agent['charset'] = 'utf-8';
	} else {
		$freo->agent['charset'] = 'sjis';
	}

	//フォームメソッド取得
	if ($freo->agent['career'] == 'au') {
		$freo->agent['method'] = 'get';
	} else {
		$freo->agent['method'] = 'post';
	}

	//固体識別情報取得
	if (isset($_SERVER['HTTP_X_DCMGUID'])) {
		$freo->agent['serial'] = $_SERVER['HTTP_X_DCMGUID'];
	} elseif (isset($_SERVER['HTTP_X_UP_SUBNO'])) {
		$freo->agent['serial'] = $_SERVER['HTTP_X_UP_SUBNO'];
	} elseif (isset($_SERVER['HTTP_X_JPHONE_UID'])) {
		$freo->agent['serial'] = $_SERVER['HTTP_X_JPHONE_UID'];
	} else {
		$freo->agent['serial'] = null;
	}

	/* キャリアをIPアドレスで確認 */
	if ($freo->agent['serial']) {
		$career = freo_ip2career();

		if ($career != 'docomo' and $career != 'softbank' and $career != 'au') {
			$freo->agent['serial'] = null;
		}
	}

	return;
}

/* 受信データ正規化 */
function freo_normalize()
{
	global $freo;

	//絵文字コード統一
	if (FREO_PICTOGRAM_MODE) {
		$_GET  = freo_pictogram_unify($_GET);
		$_POST = freo_pictogram_unify($_POST);
	}

	//不正データ削除
	$_GET     = freo_sanitize($_GET);
	$_POST    = freo_sanitize($_POST);
	$_REQUEST = freo_sanitize($_REQUEST);
	$_SERVER  = freo_sanitize($_SERVER);
	$_COOKIE  = freo_sanitize($_COOKIE);

	//不要ホワイトスペース削除
	if (empty($_REQUEST['freo']['trim']) or $_REQUEST['freo']['trim'] != 'keep') {
		$_GET     = freo_trim($_GET);
		$_POST    = freo_trim($_POST);
		$_REQUEST = freo_trim($_REQUEST);
		$_SERVER  = freo_trim($_SERVER);
		$_COOKIE  = freo_trim($_COOKIE);
	}

	//改行コード統一
	$_GET     = freo_unify($_GET);
	$_POST    = freo_unify($_POST);
	$_REQUEST = freo_unify($_REQUEST);
	$_SERVER  = freo_unify($_SERVER);
	$_COOKIE  = freo_unify($_COOKIE);

	//文字コード変換
	if ($freo->agent['charset'] == 'sjis') {
		$_GET     = freo_convert($_GET);
		$_POST    = freo_convert($_POST);
		$_REQUEST = freo_convert($_REQUEST);
		$_SERVER  = freo_convert($_SERVER);
	}

	//POSTデータ設定
	if ($freo->agent['method'] == 'get' and isset($_GET['freo']['method']) and $_GET['freo']['method'] == 'post') {
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$_POST = $_GET;
	}

	//REQUESTデータ設定
	$_REQUEST = array(
		'freo' => array(
			'type'     => isset($_POST['freo']['type'])     ? $_POST['freo']['type']     : (isset($_GET['freo']['type'])    ? $_GET['freo']['type']    : null),
			'mode'     => isset($_POST['freo']['mode'])     ? $_POST['freo']['mode']     : (isset($_GET['freo']['mode'])    ? $_GET['freo']['mode']    : null),
			'work'     => isset($_POST['freo']['work'])     ? $_POST['freo']['work']     : (isset($_GET['freo']['work'])    ? $_GET['freo']['work']    : null),
			'user'     => isset($_POST['freo']['user'])     ? $_POST['freo']['user']     : null,
			'password' => isset($_POST['freo']['password']) ? $_POST['freo']['password'] : null,
			'session'  => isset($_POST['freo']['session'])  ? $_POST['freo']['session']  : (isset($_GET['freo']['session']) ? $_GET['freo']['session'] : null),
			'token'    => isset($_POST['freo']['token'])    ? $_POST['freo']['token']    : (isset($_GET['freo']['token'])   ? $_GET['freo']['token']   : null),
		)
	);

	//接続環境上書き
	if (preg_match('/^[\w\-]+$/', $_REQUEST['freo']['type'])) {
		$freo->agent['type'] = $_REQUEST['freo']['type'];
	}

	return;
}

/* パラメーター取得 */
function freo_parameter()
{
	global $freo;

	//パラメーター解析
	$request_uri = explode('/', strtok($_SERVER['REQUEST_URI'], '?'));
	$script_name = explode('/', $_SERVER['SCRIPT_NAME']);

	for ($i = 0; $i < sizeof($script_name); $i++) {
		if ($request_uri[$i] == $script_name[$i]) {
			unset($request_uri[$i]);
		}
	}
	$freo->parameters = array_values(array_map('urldecode', $request_uri));

	//mod_rewrite対応時
	if (FREO_REWRITE_MODE) {
		if (isset($freo->parameters[count($freo->parameters) - 1])) {
			$file = $freo->parameters[count($freo->parameters) - 1];
		} else {
			$file = null;
		}

		//存在しないファイルを指定された時
		if (strpos($file, '.') and !file_exists(implode('/', $freo->parameters))) {
			$parameters = $freo->parameters;

			$freo_mode = array_shift($parameters);
			$file_mode = array_shift($parameters);

			$file_flag = false;

			if ($freo_mode == 'file') {
				if ($file_mode == 'page') {
					//ページの存在を確認
					$id = implode('/', $parameters);

					if (preg_match('/(.+)\.\w+$/', $id, $matches)) {
						$id = $matches[1];
					}

					$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
					$stmt->bindValue(':id',   $id);
					$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
					$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
					$flag = $stmt->execute();
					if (!$flag) {
						freo_error($stmt->errorInfo());
					}

					if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
						$file_flag = true;
					}
				} elseif ($file_mode == 'view') {
					//エントリーの存在を確認
					$id = $parameters[0];

					if (preg_match('/(.+)\.\w+$/', $id, $matches)) {
						$id = $matches[1];
					}

					if (preg_match('/^\d+$/', $id)) {
						$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
						$stmt->bindValue(':id',   $id, PDO::PARAM_INT);
						$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
						$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
						$flag = $stmt->execute();
						if (!$flag) {
							freo_error($stmt->errorInfo());
						}

						if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
							$file_flag = true;
						}
					} else {
						$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE code = :code AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
						$stmt->bindValue(':code', $id);
						$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
						$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
						$flag = $stmt->execute();
						if (!$flag) {
							freo_error($stmt->errorInfo());
						}

						if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
							$file_flag = true;
						}
					}
				} elseif ($file_mode == 'media') {
					//メディアの存在を確認
					$file = implode('/', $parameters);

					if (file_exists(FREO_FILE_DIR . 'medias/' . $file)) {
						$file_flag = true;
					}
				}
			}

			//該当データが無ければエラー
			if ($file_flag == false) {
				header('HTTP/1.0 404 Not Found');

				if (FREO_ERROR_FILE) {
					require_once FREO_ERROR_FILE;
				} else {
					echo '404 Not Found';
				}

				exit;
			}
		}
	}

	//ルーティング利用時
	if (FREO_ROUTING_MODE) {
		freo_routing_config();
		freo_routing_execute();
	}

	//動作モードを設定
	if (isset($freo->parameters[0])) {
		$_REQUEST['freo']['mode'] = $freo->parameters[0];
	}
	if (isset($freo->parameters[1])) {
		$_REQUEST['freo']['work'] = $freo->parameters[1];
	}

	if ($_REQUEST['freo']['mode'] == '' or preg_match('/[^\w\-]/', $_REQUEST['freo']['mode']) or $_REQUEST['freo']['mode'] == 'freo') {
		$_REQUEST['freo']['mode'] = 'default';
	}
	if ($_REQUEST['freo']['work'] == '' or preg_match('/[^\w\-]/', $_REQUEST['freo']['work']) or $_REQUEST['freo']['work'] == 'freo') {
		$_REQUEST['freo']['work'] = 'default';
	}

	return;
}

/* ユーザー情報取得 */
function freo_user()
{
	global $freo;

	//ユーザー情報をセッションから取得
	if (isset($_SESSION['freo']['user'])) {
		$freo->user = $_SESSION['freo']['user'];
	} else {
		$freo->user = array(
			'id'        => null,
			'authority' => null,
			'groups'    => array()
		);
	}

	//セットアップモードなら以降を処理しない
	if ($_REQUEST['freo']['mode'] == 'setup') {
		return;
	}

	//認証フラグ
	$user_flag = false;

	//固体識別情報で認証
	if (!$freo->user['id'] and $freo->agent['serial']) {
		$stmt = $freo->pdo->prepare('SELECT id, authority FROM ' . FREO_DATABASE_PREFIX . 'users WHERE approved = \'yes\' AND serial = :serial');
		$stmt->bindValue(':serial', md5($freo->agent['serial']));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$freo->user['id']        = $data['id'];
			$freo->user['authority'] = $data['authority'];
		}

		if ($freo->user['id']) {
			$user_flag = true;
		}
	}

	//Cookieで認証
	if (!$freo->user['id'] and !empty($_COOKIE['freo']['session'])) {
		$stmt = $freo->pdo->prepare('SELECT id, authority FROM ' . FREO_DATABASE_PREFIX . 'users WHERE approved = \'yes\' AND session = :session');
		$stmt->bindValue(':session', md5($_COOKIE['freo']['session']));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$freo->user['id']        = $data['id'];
			$freo->user['authority'] = $data['authority'];
		}

		if ($freo->user['id']) {
			$session = uniqid(rand(), true);

			freo_setcookie('freo[session]', $session, time() + FREO_COOKIE_EXPIRE);

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET session = :session WHERE id = :user_id');
			$stmt->bindValue(':session', md5($session));
			$stmt->bindValue(':user_id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			$user_flag = true;
		}
	}

	//ユーザーIDとパスワードで認証
	if (!$freo->user['id'] and $_REQUEST['freo']['user'] and $_REQUEST['freo']['password']) {
		$stmt = $freo->pdo->prepare('SELECT id, authority FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :user AND approved = \'yes\' AND password = :password');
		$stmt->bindValue(':user',     $_REQUEST['freo']['user']);
		$stmt->bindValue(':password', md5($_REQUEST['freo']['password']));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$freo->user['id']        = $data['id'];
			$freo->user['authority'] = $data['authority'];
		}

		if ($freo->user['id']) {
			if ($freo->agent['serial']) {
				$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET serial = :serial WHERE id = :user_id');
				$stmt->bindValue(':serial',  md5($freo->agent['serial']));
				$stmt->bindValue(':user_id', $freo->user['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}
			} elseif ($_REQUEST['freo']['session'] == 'keep') {
				$session = uniqid(rand(), true);

				freo_setcookie('freo[session]', $session, time() + FREO_COOKIE_EXPIRE);

				$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET session = :session WHERE id = :user_id');
				$stmt->bindValue(':session', md5($session));
				$stmt->bindValue(':user_id', $freo->user['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}
			}

			$user_flag = true;
		}
	}

	//認証解除
	if ($freo->user['id'] and $_REQUEST['freo']['session'] == 'logout') {
		if ($freo->agent['serial']) {
			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET serial = NULL WHERE id = :user_id');
			$stmt->bindValue(':user_id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		} else {
			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET session = NULL WHERE id = :user_id');
			$stmt->bindValue(':user_id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			freo_setcookie('freo[session]', null);
		}

		$freo->user = array(
			'id'        => null,
			'authority' => null,
			'groups'    => array()
		);

		$user_flag = true;
	}

	//グループ情報取得
	if ($freo->user['id'] and $user_flag) {
		$stmt = $freo->pdo->prepare('SELECT group_id FROM ' . FREO_DATABASE_PREFIX . 'group_sets WHERE user_id = :user_id');
		$stmt->bindValue(':user_id', $freo->user['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
			$freo->user['groups'][] = $data[0];
		}
	}

	//ユーザー情報をセッションに格納
	if ($user_flag) {
		$_SESSION['freo']['user'] = $freo->user;
	}

	return;
}

/* フィルター設定取得 */
function freo_filter()
{
	global $freo;

	//セットアップモードなら以降を処理しない
	if ($_REQUEST['freo']['mode'] == 'setup') {
		return;
	}

	//フィルター設定取得
	if (empty($_SESSION['filter']) and !empty($_COOKIE['filter'])) {
		$_SESSION['filter'] = $_COOKIE['filter'];
	}

	return;
}

/* 登録情報取得 */
function freo_refer()
{
	global $freo;

	//セットアップモードなら以降を処理しない
	if ($_REQUEST['freo']['mode'] == 'setup') {
		return;
	}

	//ユーザー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users ORDER BY id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$freo->refer['users'] = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$freo->refer['users'][$data['id']] = $data;
	}

	//エントリー取得
	$stmt = $freo->pdo->query('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'entries ORDER BY datetime');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$freo->refer['entries'] = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$freo->refer['entries'][$data['id']] = $data;
	}

	//ページ取得
	$stmt = $freo->pdo->query('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'pages ORDER BY pid, sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$freo->refer['pages'] = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$freo->refer['pages'][$data['id']] = $data;
	}

	//グループ取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'groups ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$freo->refer['groups'] = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$freo->refer['groups'][$data['id']] = $data;
	}

	//フィルター取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'filters ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$freo->refer['filters'] = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$freo->refer['filters'][$data['id']] = $data;
	}

	//オプション取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$freo->refer['options'] = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$freo->refer['options'][$data['id']] = $data;
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$freo->refer['categories'] = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$freo->refer['categories'][$data['id']] = $data;
	}

	return;
}

/* 設定読み込み */
function freo_config()
{
	global $freo;

	//セットアップモードなら以降を処理しない
	if ($_REQUEST['freo']['mode'] == 'setup') {
		return;
	}

	//設定読み込み
	foreach (array(FREO_CONFIG_DIR, FREO_CONFIG_DIR . 'plugins/') as $config_dir) {
		if ($dir = scandir($config_dir)) {
			foreach ($dir as $data) {
				if (!is_file($config_dir . $data) or !preg_match('/^(\w+)\.ini$/', $data, $matches)) {
					continue;
				}

				$configs = parse_ini_file($config_dir . $data);

				foreach ($configs as $key => $value) {
					$value = str_replace('\\\\', "\a", $value);
					$value = str_replace('\n', "\n", $value);
					$value = str_replace("\a", '\\', $value);

					if ($config_dir == FREO_CONFIG_DIR) {
						$freo->config[$matches[1]][$key] = $value;
					} else {
						$freo->config['plugin'][$matches[1]][$key] = $value;
					}
				}
			}
		} else {
			freo_error('設定ファイル格納ディレクトリ ' . $config_dir . ' を開けません。');
		}
	}

	return;
}

/* プラグイン実行 */
function freo_plugin($type)
{
	global $freo;

	//セットアップモードなら以降を処理しない
	if ($_REQUEST['freo']['mode'] == 'setup') {
		return;
	}
	if ($_REQUEST['freo']['work'] == 'setup' and ($type == 'init' or $type == 'begin' or $type == 'display' or $type == 'end')) {
		return;
	}

	//プラグイン実行
	if ($dir = scandir(FREO_MAIN_DIR . 'freo/plugins/')) {
		$plugin = null;

		foreach ($dir as $data) {
			if (is_file(FREO_MAIN_DIR . 'freo/plugins/' . $data) and preg_match("/^$type\.(\w+)\.php$/", $data, $matches)) {
				$id = $matches[1];
			} else {
				continue;
			}

			if ($type != 'config') {
				if (!defined('FREO_PLUGIN_' . strtoupper($id) . '_NAME') or !defined('FREO_PLUGIN_' . strtoupper($id) . '_VERSION')) {
					continue;
				}
				if (defined('FREO_PLUGIN_' . strtoupper($id) . '_LOAD_' . strtoupper($type))) {
					$loads = explode(',', constant('FREO_PLUGIN_' . strtoupper($id) . '_LOAD_' . strtoupper($type)));

					$flag = false;
					foreach ($loads as $load) {
						if (strpos($load, '/')) {
							list($mode, $work) = explode('/', $load);
						} else {
							list($mode, $work) = array($load, null);
						}

						if ($mode and $mode == $_REQUEST['freo']['mode']) {
							$flag = true;

							if ($work and $work != $_REQUEST['freo']['work']) {
								$flag = false;
							}

							if ($flag) {
								break;
							}
						}
					}
					if (!$flag) {
						continue;
					}
				}
				if (defined('FREO_PLUGIN_' . strtoupper($id) . '_UNLOAD_' . strtoupper($type))) {
					$unloads = explode(',', constant('FREO_PLUGIN_' . strtoupper($id) . '_UNLOAD_' . strtoupper($type)));

					$flag = true;
					foreach ($unloads as $unload) {
						if (strpos($unload, '/')) {
							list($mode, $work) = explode('/', $unload);
						} else {
							list($mode, $work) = array($unload, null);
						}

						if ($mode and $mode == $_REQUEST['freo']['mode']) {
							if ($work) {
								if ($work == $_REQUEST['freo']['work']) {
									$flag = false;
								}
							} else {
								$flag = false;
							}
						}
					}
					if (!$flag) {
						continue;
					}
				}
			}

			$freo->core['plugin'] = $id;

			include_once FREO_MAIN_DIR . 'freo/plugins/' . $data;

			if ($type == 'config') {
				$freo->plugin[$id] = array(
					'id'      => $id,
					'name'    => constant('FREO_PLUGIN_' . strtoupper($id) . '_NAME'),
					'version' => constant('FREO_PLUGIN_' . strtoupper($id) . '_VERSION'),
					'admin'   => defined('FREO_PLUGIN_' . strtoupper($id) . '_ADMIN') ? constant('FREO_PLUGIN_' . strtoupper($id) . '_ADMIN') : null
				);

				$freo->core['plugin'] = null;

				continue;
			} else {
				$plugin = 'freo_' . $type . '_' . $id;
			}
			if ($type == 'page') {
				break;
			}

			$plugin();

			$freo->core['plugin'] = null;
		}

		if ($type == 'page' and $plugin and function_exists($plugin)) {
			$plugin_id = $freo->core['plugin'];

			freo_plugin('begin');

			$freo->core['plugin'] = $plugin_id;

			freo_execute($plugin);

			$freo->core['plugin'] = null;

			freo_plugin('end');

			exit;
		}
	} else {
		freo_error('プラグイン格納ディレクトリ ' . FREO_MAIN_DIR . 'freo/plugins/ を開けません。');
	}

	return;
}

/* メインプログラム読み込み */
function freo_require()
{
	global $freo;

	$main_dir = FREO_MAIN_DIR . 'freo/internals/';

	if (file_exists($main_dir . $_REQUEST['freo']['mode'] . '/')) {
		$app_dir = $_REQUEST['freo']['mode'] . '/';
	} else {
		$app_dir = 'default/';
	}
	if (file_exists($main_dir . $app_dir . $_REQUEST['freo']['work'] . '.php')) {
		$app_file = $_REQUEST['freo']['work'] . '.php';
	} else {
		$app_file = 'default.php';
	}

	$freo->core['lib'] = $app_dir . $app_file;

	if (file_exists($main_dir . $app_dir . $app_file)) {
		require_once $main_dir . $app_dir . $app_file;
	}

	if (!function_exists('freo_main')) {
		freo_error('不正なアクセスです。');
	}

	return;
}

/* メインプログラム実行 */
function freo_execute($function = null)
{
	global $freo;

	//キャッシュ判別
	if (FREO_CACHE_MODE and !$freo->user['id'] and $_SERVER['REQUEST_METHOD'] == 'GET') {
		$targets = explode(',', FREO_CACHE_TARGET);

		$flag = false;
		foreach ($targets as $target) {
			if (strpos($target, '/')) {
				list($mode, $work) = explode('/', $target);
			} else {
				list($mode, $work) = array($target, null);
			}

			if ($mode and $mode == $_REQUEST['freo']['mode']) {
				$flag = true;

				if ($work and $work != $_REQUEST['freo']['work']) {
					$flag = false;
				}

				if ($flag) {
					break;
				}
			}
		}
		if ($flag) {
			freo_output(null, null, true);

			if (headers_sent()) {
				return;
			}
		}
	}

	//メイン処理
	if ($function) {
		$function();
	} else {
		freo_main();
	}

	//データ出力
	freo_output();

	return;
}

/* データ出力 */
function freo_output($template = null, $id = null, $cache = null, $error = null)
{
	global $freo;

	//読み込み先決定
	if ($freo->agent['type'] != 'pc' and file_exists($freo->smarty->template_dir . $freo->agent['type'] . 's/')) {
		$target = $freo->agent['type'] . 's/';
	} else {
		$target = null;
	}

	if (!$error) {
		//ヘッダ出力確認
		if (headers_sent()) {
			return;
		}

		//テンプレート決定
		if (!$template) {
			if (function_exists('freo_main')) {
				$template = 'internals/';
			} else {
				$template = 'plugins/';
			}

			$exts = array('xml', 'txt', 'html');
			$file = null;

			if ($freo->parameters) {
				$parameters = $freo->parameters;

				while (!empty($parameters)) {
					$path = implode('/', $parameters);

					foreach ($exts as $ext) {
						if ($freo->smarty->template_exists($target . $template . $path . '.' . $ext)) {
							$file = $path . '.' . $ext;

							break;
						}
					}

					if ($file) {
						break;
					}

					array_pop($parameters);
				}
			}

			if (!$file) {
				foreach (array($_REQUEST['freo']['mode'] . '/', 'default/') as $dir) {
					foreach ($exts as $ext) {
						if ($freo->smarty->template_exists($target . $template . $dir . $_REQUEST['freo']['work'] . '.' . $ext)) {
							$file = $dir . $_REQUEST['freo']['work'] . '.' . $ext;

							break;
						} elseif ($freo->smarty->template_exists($target . $template . $dir . 'default.' . $ext)) {
							$file = $dir . 'default.' . $ext;

							break;
						}
					}

					if ($file) {
						break;
					}
				}
			}

			$template .= $file;
		}
	}

	if ($freo->smarty->template_exists($target . $template)) {
		$template = $target . $template;
	}

	if (!$error) {
		//代替テンプレート調査
		if (!$freo->smarty->template_exists($template)) {
			if (function_exists('freo_main')) {
				$template = 'internals/';
			} else {
				$template = 'plugins/';
			}

			$parameters = $freo->parameters;
			$file       = null;

			while (!empty($parameters)) {
				$path = implode('/', $parameters);

				if ($freo->smarty->template_exists($template . $path . '.html')) {
					$file = $path . '.html';

					break;
				} elseif ($freo->smarty->template_exists($template . $path . '/default.html')) {
					$file = $path . '/default.html';

					break;
				}

				array_pop($parameters);
			}

			$template .= $file;

			if (!$freo->smarty->template_exists($template)) {
				freo_error('テンプレート ' . $freo->smarty->template_dir . $template . ' を読み込めません。');
			}
		}

		//キャッシュID決定
		if (!$id and $freo->parameters) {
			$id = implode('|', $freo->parameters);

			if (!empty($_GET)) {
				$queries = $_GET;

				ksort($queries);

				foreach ($queries as $key => $value) {
					if (is_array($value)) {
						$value = serialize($value);
					}

					$id .= '|' . urlencode($key) . '=' . urlencode($value);
				}
			}
		}

		if ($cache) {
			//キャッシュを設定
			$freo->smarty->caching        = 1;
			$freo->smarty->compile_check  = false;
			$freo->smarty->cache_dir      = FREO_CACHE_DIR;
			$freo->smarty->cache_lifetime = FREO_CACHE_LIFETIME;

			//最終更新前のキャッシュを削除
			$modified_times = array();

			foreach (array('entries', 'pages', 'comments', 'trackbacks') as $table) {
				$stmt = $freo->pdo->query('SELECT modified FROM ' . FREO_DATABASE_PREFIX . $table . ' ORDER BY modified DESC LIMIT 1');
				if (!$stmt) {
					freo_error($freo->pdo->errorInfo());
				}

				$data = $stmt->fetch(PDO::FETCH_ASSOC);
				$modified_times[] = strtotime($data['modified']);
			}

			$passed = time() - max($modified_times);

			if ($passed < FREO_CACHE_LIFETIME) {
				$freo->smarty->clear_all_cache($passed);
			}

			//キャッシュを確認
			if (!$freo->smarty->is_cached($template, $id)) {
				return;
			}
		}
	}

	if (preg_match('/^' . preg_quote($target, '/') . 'internals\/(.+)$/', $template, $matches)) {
		$freo->core['template'] = $matches[1];
	}

	//対応プログラム・テンプレート確認
	if (FREO_ERROR_MODE and ($_REQUEST['freo']['mode'] != 'default' or $_REQUEST['freo']['work'] != 'default') and $freo->core['lib'] == 'default/default.php' and $freo->core['template'] == 'default/default.html') {
		header('HTTP/1.0 404 Not Found');

		if (FREO_ERROR_FILE) {
			require_once FREO_ERROR_FILE;
		} else {
			echo '404 Not Found';
		}

		exit;
	}

	//データ割当
	if (!$cache) {
		$freo->query = freo_query($_GET);

		$freo->smarty->assign('freo', array(
			'core'   => isset($freo->core)   ? $freo->core   : array(),
			'agent'  => isset($freo->agent)  ? $freo->agent  : array(),
			'user'   => isset($freo->user)   ? $freo->user   : array(),
			'refer'  => isset($freo->refer)  ? $freo->refer  : array(),
			'config' => isset($freo->config) ? $freo->config : array(),
			'plugin' => isset($freo->plugin) ? $freo->plugin : array(),
			'query'  => isset($freo->query)  ? $freo->query  : array()
		));
	}
	if (!$error and !$cache) {
		freo_plugin('display');
	}

	//ヘッダ出力
	if (preg_match('/\.xml$/i', $template)) {
		$content = 'text/xml';
	} elseif (preg_match('/\.txt$/i', $template)) {
		$content = 'text/plain';
	} else {
		$content = $freo->agent['content'];
	}
	if ($freo->agent['charset'] == 'sjis') {
		$charset = 'Shift_JIS';
	} else {
		$charset = 'UTF-8';
	}

	header('Content-Type: ' . $content . '; charset=' . $charset);

	if ($freo->agent['type'] == 'mobile') {
		header('Pragma: no-cache');
		header('Cache-Control: no-cache');
	}

	//内容出力
	$output = $freo->smarty->fetch($template, $id);
	$output = freo_cleanup($output);

	if ($freo->agent['charset'] == 'sjis') {
		$output = freo_convert($output, 'SJIS-WIN', 'UTF-8');
	}
	if (FREO_TRANSFER_MODE) {
		$output = freo_transfer_execute($output);
	}
	if (FREO_PICTOGRAM_MODE) {
		$output = freo_pictogram_convert($output);
	}

	echo $output;

	return;
}

/* リダイレクト */
function freo_redirect($redirect, $secure = false)
{
	global $freo;

	freo_plugin('end');

	if (preg_match('/^https?\:\/\//', $redirect)) {
		$url = $redirect;
	} else {
		if (FREO_HTTPS_URL and $secure) {
			$url = $freo->core['https_file'];
		} else {
			$url = $freo->core['http_file'];
		}
		if ($redirect) {
			$url .= '/' . $redirect;
		}

		if ((FREO_HTTPS_URL and isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] != 'off') or $freo->agent['type'] == 'mobile') {
			$url .= preg_match('/\?/', $redirect) ? '&' : '?';
			$url .= $freo->core['session_name'] . '=' . $freo->core['session_id'];
		}
	}

	header('Location: ' . $url);

	exit;
}

/* エラー表示 */
function freo_error($message, $status_code = null, $version = 1.0)
{
	global $freo;

	if (is_array($message)) {
		list($pdo_state, $pdo_code, $pdo_message) = $message;

		$message = $pdo_message;
	}

	$freo->smarty->assign('message', $message);

	if ($status_code) {
		header('HTTP/' . $version . ' ' . $status_code);
	}

	freo_output('error.html', null, null, true);

	exit;
}

/* ログ記録 */
function freo_log($message)
{
	global $freo;

	$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'logs VALUES(NULL, :user_id, :now1, :now2, :ip, :plugin, :message)');
	$stmt->bindValue(':user_id', $freo->user['id']);
	$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
	$stmt->bindValue(':ip',      $_SERVER['REMOTE_ADDR']);
	$stmt->bindValue(':plugin',  $freo->core['plugin']);
	$stmt->bindValue(':message', $message);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($_REQUEST['freo']['mode'] != 'setup') {
		$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'logs WHERE created < :now');
		$stmt->bindValue(':now', date('Y-m-d H:i:s', time() - (60 * 60 * 24 * $freo->config['basis']['log_days'])));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	return;
}

/* ワンタイムトークン */
function freo_token($type, $name = 'default')
{
	global $freo;

	if ($type == 'check') {
		if ($_REQUEST['freo']['token'] and isset($_SESSION['freo']['token'][$name]) and $_REQUEST['freo']['token'] == $_SESSION['freo']['token'][$name]['value']) {
			$flag = true;
		} else {
			$flag = false;
		}

		if (empty($_SESSION['freo']['token'][$name]) or time() - $_SESSION['freo']['token'][$name]['time'] > FREO_TOKEN_SPAN) {
			$_SESSION['freo']['token'][$name] = array();
		}

		return $flag;
	} else {
		if (empty($_SESSION['freo']['token'][$name]) or time() - $_SESSION['freo']['token'][$name]['time'] > FREO_TOKEN_SPAN) {
			$token = md5(uniqid(rand(), true));

			$_SESSION['freo']['token'][$name] = array(
				'value' => $token,
				'time'  => time()
			);
		} else {
			$token = $_SESSION['freo']['token'][$name]['value'];
		}

		return $token;
	}
}

/* エスケープ文字削除 */
function freo_stripslashes($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_stripslashes', $data);
	}

	return stripslashes($data);
}

/* 不正データ削除 */
function freo_sanitize($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_sanitize', $data);
	}

	return str_replace("\0", '', $data);
}

/* 不要ホワイトスペース削除 */
function freo_trim($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_trim', $data);
	}

	return trim($data);
}

/* 不要データ削除 */
function freo_cleanup($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_cleanup', $data);
	}

	return preg_replace("/[\t| ]*<!---->\r*\n*/", '', $data);
}

/* 改行コード統一 */
function freo_unify($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_unify', $data);
	}

	$data = preg_replace("/\r?\n/", "\r", $data);
	$data = preg_replace("/\r/", "\n", $data);

	return $data;
}

/* 文字コード変換 */
function freo_convert($data, $to_encoding = 'UTF-8', $from_encoding = 'UTF-8,SJIS-WIN')
{
	global $freo;

	if (mb_convert_variables($to_encoding, $from_encoding, $data)) {
		return $data;
	} else {
		return array();
	}
}

/* テンプレート割当用クエリー作成 */
function freo_query($data)
{
	global $freo;

	if (is_array($data)) {
		return array_map('freo_query', $data);
	}

	return preg_replace('/[^\w\-\+\/\%]/', '', $data);
}

/* オプション割り当て */
function freo_option($text, $options, $path)
{
	global $freo;

	$clear_flag = true;
	foreach ($options as $key => $value) {
		$flag = false;

		if (preg_match('/\[\$' . $key . '[^\]]*\|text\]/', $text)) {
			$file = $path . $key . '/' . $value;

			if (file_exists($file)) {
				$text = preg_replace('/\[\$' . $key . '((\s+[^\]]+)*)\|text]/', $value, $text);
			}

			$options[$key] = null;

			$flag = true;
		}
		if (preg_match('/\[\$' . $key . '[^\]]*\|url\]/', $text)) {
			$file = $path . $key . '/' . $value;

			if (file_exists($file)) {
				$text = preg_replace('/\[\$' . $key . '((\s+[^\]]+)*)\|url]/', FREO_HTTP_URL . $file, $text);
			}

			$options[$key] = null;

			$flag = true;
		}
		if (preg_match('/\[\$' . $key . '[^\]]*\|image\]/', $text)) {
			$file = $path . $key . '/' . $value;

			if (file_exists($file)) {
				list($width, $height, $size) = freo_file($file);

				if ($width > 0 and $height > 0) {
					$text = preg_replace('/\[\$' . $key . '((\s+[^\]]+)*)\|image]/', '<img src="' . FREO_HTTP_URL . $file . '"$1 />', $text);
				}
			}

			$options[$key] = null;

			$flag = true;
		}
		if (preg_match('/\[\$' . $key . '[^\]]*\]/', $text)) {
			$file      = $path . $key . '/' . $value;
			$thumbnail = $path . $key . '/thumbnail_' . $value;

			if (file_exists($file) and file_exists($thumbnail)) {
				list($width, $height, $size) = freo_file($thumbnail);

				$text = preg_replace('/\[\$' . $key . '((\s+[^\]]+)*)\]/', '<a href="' . FREO_HTTP_URL . $file . '"><img src="' . FREO_HTTP_URL . $thumbnail . '"$1 /></a>', $text);
			} elseif (file_exists($file)) {
				list($width, $height, $size) = freo_file($file);

				if ($width > 0 and $height > 0) {
					$text = preg_replace('/\[\$' . $key . '((\s+[^\]]+)*)\]/', '<a href="' . FREO_HTTP_URL . $file . '"><img src="' . FREO_HTTP_URL . $file . '"$1 /></a>', $text);
				} else {
					$text = preg_replace('/\[\$' . $key . '(\s+[^\]]+)\]/', '<a href="' . FREO_HTTP_URL . $file . '">$1</a>', $text);
					$text = preg_replace('/\[\$' . $key . '\]/', '<a href="' . FREO_HTTP_URL . $file . '">' . FREO_HTTP_URL . $file . '</a>', $text);
				}
			} else {
				$text = preg_replace('/\[\$' . $key . '\]/', nl2br($value), $text);
			}

			$options[$key] = null;

			$flag = true;
		}

		if (!$flag) {
			$clear_flag = false;
		}
	}

	if ($clear_flag) {
		$options = array();
	}

	return array($text, $options);
}

/* テキスト分割 */
function freo_divide($text)
{
	global $freo;

	if (preg_match('/<p>' . FREO_DIVIDE_MARK . '<\/p>/', $text)) {
		list($excerpt, $more) = explode('<p>' . FREO_DIVIDE_MARK . '</p>', $text, 2);
	} elseif (preg_match('/' . FREO_DIVIDE_MARK . '/', $text)) {
		list($excerpt, $more) = explode(FREO_DIVIDE_MARK, $text, 2);
	} else {
		list($excerpt, $more) = array($text, null);
	}

	return array($excerpt, $more);
}

/* ファイル情報取得 */
function freo_file($file)
{
	global $freo;

	if (preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $file)) {
		list($width, $height) = getimagesize($file);
	} else {
		$width  = 0;
		$height = 0;
	}

	$size = filesize($file);

	return array($width, $height, $size);
}

/* ディレクトリ情報取得 */
function freo_directory($directory)
{
	global $freo;

	if (!file_exists($directory)) {
		return 0;
	}

	$size = 0;

	if ($dir = scandir($directory)) {
		foreach ($dir as $data) {
			if ($data == '.' or $data == '..') {
				continue;
			}
			if (is_dir($directory . $data)) {
				$size += freo_directory($directory . $data . '/');
			} elseif (is_file($directory . $data)) {
				$size += filesize($directory . $data);
			}
		}
	} else {
		return 0;
	}

	return $size;
}

/* マイムタイプ取得 */
function freo_mime($file)
{
	global $freo;

	$mime_content_types = array(
		'ez'        => 'application/andrew-inset',
		'atom'      => 'application/atom+xml',
		'atomcat'   => 'application/atomcat+xml',
		'atomsvc'   => 'application/atomsvc+xml',
		'ccxml'     => 'application/ccxml+xml',
		'davmount'  => 'application/davmount+xml',
		'ecma'      => 'application/ecmascript',
		'pfr'       => 'application/font-tdpfr',
		'stk'       => 'application/hyperstudio',
		'js'        => 'application/javascript',
		'json'      => 'application/json',
		'hqx'       => 'application/mac-binhex40',
		'cpt'       => 'application/mac-compactpro',
		'mrc'       => 'application/marc',
		'ma'        => 'application/mathematica',
		'nb'        => 'application/mathematica',
		'mb'        => 'application/mathematica',
		'mathml'    => 'application/mathml+xml',
		'mbox'      => 'application/mbox',
		'mscml'     => 'application/mediaservercontrol+xml',
		'mp4s'      => 'application/mp4',
		'doc'       => 'application/msword',
		'dot'       => 'application/msword',
		'mxf'       => 'application/mxf',
		'bin'       => 'application/octet-stream',
		'dms'       => 'application/octet-stream',
		'lha'       => 'application/octet-stream',
		'lzh'       => 'application/octet-stream',
		'class'     => 'application/octet-stream',
		'so'        => 'application/octet-stream',
		'iso'       => 'application/octet-stream',
		'dmg'       => 'application/octet-stream',
		'dist'      => 'application/octet-stream',
		'distz'     => 'application/octet-stream',
		'pkg'       => 'application/octet-stream',
		'bpk'       => 'application/octet-stream',
		'dump'      => 'application/octet-stream',
		'elc'       => 'application/octet-stream',
		'oda'       => 'application/oda',
		'ogg'       => 'application/ogg',
		'pdf'       => 'application/pdf',
		'pgp'       => 'application/pgp-encrypted',
		'asc'       => 'application/pgp-signature',
		'sig'       => 'application/pgp-signature',
		'prf'       => 'application/pics-rules',
		'p10'       => 'application/pkcs10',
		'p7m'       => 'application/pkcs7-mime',
		'p7c'       => 'application/pkcs7-mime',
		'p7s'       => 'application/pkcs7-signature',
		'cer'       => 'application/pkix-cert',
		'crl'       => 'application/pkix-crl',
		'pkipath'   => 'application/pkix-pkipath',
		'pki'       => 'application/pkixcmp',
		'pls'       => 'application/pls+xml',
		'ai'        => 'application/postscript',
		'eps'       => 'application/postscript',
		'ps'        => 'application/postscript',
		'cww'       => 'application/prs.cww',
		'rdf'       => 'application/rdf+xml',
		'rif'       => 'application/reginfo+xml',
		'rnc'       => 'application/relax-ng-compact-syntax',
		'rl'        => 'application/resource-lists+xml',
		'rs'        => 'application/rls-services+xml',
		'rsd'       => 'application/rsd+xml',
		'rss'       => 'application/rss+xml',
		'rtf'       => 'application/rtf',
		'sbml'      => 'application/sbml+xml',
		'scq'       => 'application/scvp-cv-request',
		'scs'       => 'application/scvp-cv-response',
		'spq'       => 'application/scvp-vp-request',
		'spp'       => 'application/scvp-vp-response',
		'sdp'       => 'application/sdp',
		'setpay'    => 'application/set-payment-initiation',
		'setreg'    => 'application/set-registration-initiation',
		'shf'       => 'application/shf+xml',
		'smi'       => 'application/smil+xml',
		'smil'      => 'application/smil+xml',
		'rq'        => 'application/sparql-query',
		'srx'       => 'application/sparql-results+xml',
		'gram'      => 'application/srgs',
		'grxml'     => 'application/srgs+xml',
		'ssml'      => 'application/ssml+xml',
		'plb'       => 'application/vnd.3gpp.pic-bw-large',
		'psb'       => 'application/vnd.3gpp.pic-bw-small',
		'pvb'       => 'application/vnd.3gpp.pic-bw-var',
		'tcap'      => 'application/vnd.3gpp2.tcap',
		'pwn'       => 'application/vnd.3m.post-it-notes',
		'aso'       => 'application/vnd.accpac.simply.aso',
		'imp'       => 'application/vnd.accpac.simply.imp',
		'acu'       => 'application/vnd.acucobol',
		'atc'       => 'application/vnd.acucorp',
		'acutc'     => 'application/vnd.acucorp',
		'xdp'       => 'application/vnd.adobe.xdp+xml',
		'xfdf'      => 'application/vnd.adobe.xfdf',
		'ami'       => 'application/vnd.amiga.ami',
		'cii'       => 'application/vnd.anser-web-certificate-issue-initiation',
		'fti'       => 'application/vnd.anser-web-funds-transfer-initiation',
		'atx'       => 'application/vnd.antix.game-component',
		'mpkg'      => 'application/vnd.apple.installer+xml',
		'aep'       => 'application/vnd.audiograph',
		'mpm'       => 'application/vnd.blueice.multipass',
		'bmi'       => 'application/vnd.bmi',
		'rep'       => 'application/vnd.businessobjects',
		'cdxml'     => 'application/vnd.chemdraw+xml',
		'mmd'       => 'application/vnd.chipnuts.karaoke-mmd',
		'cdy'       => 'application/vnd.cinderella',
		'cla'       => 'application/vnd.claymore',
		'c4g'       => 'application/vnd.clonk.c4group',
		'c4d'       => 'application/vnd.clonk.c4group',
		'c4f'       => 'application/vnd.clonk.c4group',
		'c4p'       => 'application/vnd.clonk.c4group',
		'c4u'       => 'application/vnd.clonk.c4group',
		'csp'       => 'application/vnd.commonspace',
		'cst'       => 'application/vnd.commonspace',
		'cdbcmsg'   => 'application/vnd.contact.cmsg',
		'cmc'       => 'application/vnd.cosmocaller',
		'clkx'      => 'application/vnd.crick.clicker',
		'clkk'      => 'application/vnd.crick.clicker.keyboard',
		'clkp'      => 'application/vnd.crick.clicker.palette',
		'clkt'      => 'application/vnd.crick.clicker.template',
		'clkw'      => 'application/vnd.crick.clicker.wordbank',
		'wbs'       => 'application/vnd.criticaltools.wbs+xml',
		'pml'       => 'application/vnd.ctc-posml',
		'ppd'       => 'application/vnd.cups-ppd',
		'curl'      => 'application/vnd.curl',
		'rdz'       => 'application/vnd.data-vision.rdz',
		'fe_launch' => 'application/vnd.denovo.fcselayout-link',
		'dna'       => 'application/vnd.dna',
		'mlp'       => 'application/vnd.dolby.mlp',
		'dpg'       => 'application/vnd.dpgraph',
		'dfac'      => 'application/vnd.dreamfactory',
		'mag'       => 'application/vnd.ecowin.chart',
		'nml'       => 'application/vnd.enliven',
		'esf'       => 'application/vnd.epson.esf',
		'msf'       => 'application/vnd.epson.msf',
		'qam'       => 'application/vnd.epson.quickanime',
		'slt'       => 'application/vnd.epson.salt',
		'ssf'       => 'application/vnd.epson.ssf',
		'es3'       => 'application/vnd.eszigno3+xml',
		'et3'       => 'application/vnd.eszigno3+xml',
		'ez2'       => 'application/vnd.ezpix-album',
		'ez3'       => 'application/vnd.ezpix-package',
		'fdf'       => 'application/vnd.fdf',
		'gph'       => 'application/vnd.flographit',
		'ftc'       => 'application/vnd.fluxtime.clip',
		'fm'        => 'application/vnd.framemaker',
		'frame'     => 'application/vnd.framemaker',
		'maker'     => 'application/vnd.framemaker',
		'fnc'       => 'application/vnd.frogans.fnc',
		'ltf'       => 'application/vnd.frogans.ltf',
		'fsc'       => 'application/vnd.fsc.weblaunch',
		'oas'       => 'application/vnd.fujitsu.oasys',
		'oa2'       => 'application/vnd.fujitsu.oasys2',
		'oa3'       => 'application/vnd.fujitsu.oasys3',
		'fg5'       => 'application/vnd.fujitsu.oasysgp',
		'bh2'       => 'application/vnd.fujitsu.oasysprs',
		'ddd'       => 'application/vnd.fujixerox.ddd',
		'xdw'       => 'application/vnd.fujixerox.docuworks',
		'xbd'       => 'application/vnd.fujixerox.docuworks.binder',
		'fzs'       => 'application/vnd.fuzzysheet',
		'txd'       => 'application/vnd.genomatix.tuxedo',
		'kml'       => 'application/vnd.google-earth.kml+xml',
		'kmz'       => 'application/vnd.google-earth.kmz',
		'gqf'       => 'application/vnd.grafeq',
		'gqs'       => 'application/vnd.grafeq',
		'gac'       => 'application/vnd.groove-account',
		'ghf'       => 'application/vnd.groove-help',
		'gim'       => 'application/vnd.groove-identity-message',
		'grv'       => 'application/vnd.groove-injector',
		'gtm'       => 'application/vnd.groove-tool-message',
		'tpl'       => 'application/vnd.groove-tool-template',
		'vcg'       => 'application/vnd.groove-vcard',
		'zmm'       => 'application/vnd.handheld-entertainment+xml',
		'hbci'      => 'application/vnd.hbci',
		'les'       => 'application/vnd.hhe.lesson-player',
		'hpgl'      => 'application/vnd.hp-hpgl',
		'hpid'      => 'application/vnd.hp-hpid',
		'hps'       => 'application/vnd.hp-hps',
		'jlt'       => 'application/vnd.hp-jlyt',
		'pcl'       => 'application/vnd.hp-pcl',
		'pclxl'     => 'application/vnd.hp-pclxl',
		'x3d'       => 'application/vnd.hzn-3d-crossword',
		'mpy'       => 'application/vnd.ibm.minipay',
		'afp'       => 'application/vnd.ibm.modcap',
		'listafp'   => 'application/vnd.ibm.modcap',
		'list3820'  => 'application/vnd.ibm.modcap',
		'irm'       => 'application/vnd.ibm.rights-management',
		'sc'        => 'application/vnd.ibm.secure-container',
		'igl'       => 'application/vnd.igloader',
		'ivp'       => 'application/vnd.immervision-ivp',
		'ivu'       => 'application/vnd.immervision-ivu',
		'xpw'       => 'application/vnd.intercon.formnet',
		'xpx'       => 'application/vnd.intercon.formnet',
		'qbo'       => 'application/vnd.intu.qbo',
		'qfx'       => 'application/vnd.intu.qfx',
		'rcprofile' => 'application/vnd.ipunplugged.rcprofile',
		'irp'       => 'application/vnd.irepository.package+xml',
		'xpr'       => 'application/vnd.is-xpr',
		'jam'       => 'application/vnd.jam',
		'rms'       => 'application/vnd.jcp.javame.midlet-rms',
		'jisp'      => 'application/vnd.jisp',
		'joda'      => 'application/vnd.joost.joda-archive',
		'ktz'       => 'application/vnd.kahootz',
		'ktr'       => 'application/vnd.kahootz',
		'karbon'    => 'application/vnd.kde.karbon',
		'chrt'      => 'application/vnd.kde.kchart',
		'kfo'       => 'application/vnd.kde.kformula',
		'flw'       => 'application/vnd.kde.kivio',
		'kon'       => 'application/vnd.kde.kontour',
		'kpr'       => 'application/vnd.kde.kpresenter',
		'kpt'       => 'application/vnd.kde.kpresenter',
		'ksp'       => 'application/vnd.kde.kspread',
		'kwd'       => 'application/vnd.kde.kword',
		'kwt'       => 'application/vnd.kde.kword',
		'htke'      => 'application/vnd.kenameaapp',
		'kia'       => 'application/vnd.kidspiration',
		'kne'       => 'application/vnd.kinar',
		'knp'       => 'application/vnd.kinar',
		'skp'       => 'application/vnd.koan',
		'skd'       => 'application/vnd.koan',
		'skt'       => 'application/vnd.koan',
		'skm'       => 'application/vnd.koan',
		'lbd'       => 'application/vnd.llamagraphics.life-balance.desktop',
		'lbe'       => 'application/vnd.llamagraphics.life-balance.exchange+xml',
		'123'       => 'application/vnd.lotus-1-2-3',
		'apr'       => 'application/vnd.lotus-approach',
		'pre'       => 'application/vnd.lotus-freelance',
		'nsf'       => 'application/vnd.lotus-notes',
		'org'       => 'application/vnd.lotus-organizer',
		'scm'       => 'application/vnd.lotus-screencam',
		'lwp'       => 'application/vnd.lotus-wordpro',
		'portpkg'   => 'application/vnd.macports.portpkg',
		'mcd'       => 'application/vnd.mcd',
		'mc1'       => 'application/vnd.medcalcdata',
		'cdkey'     => 'application/vnd.mediastation.cdkey',
		'mwf'       => 'application/vnd.mfer',
		'mfm'       => 'application/vnd.mfmp',
		'flo'       => 'application/vnd.micrografx.flo',
		'igx'       => 'application/vnd.micrografx.igx',
		'mif'       => 'application/vnd.mif',
		'daf'       => 'application/vnd.mobius.daf',
		'dis'       => 'application/vnd.mobius.dis',
		'mbk'       => 'application/vnd.mobius.mbk',
		'mqy'       => 'application/vnd.mobius.mqy',
		'msl'       => 'application/vnd.mobius.msl',
		'plc'       => 'application/vnd.mobius.plc',
		'txf'       => 'application/vnd.mobius.txf',
		'mpn'       => 'application/vnd.mophun.application',
		'mpc'       => 'application/vnd.mophun.certificate',
		'xul'       => 'application/vnd.mozilla.xul+xml',
		'cil'       => 'application/vnd.ms-artgalry',
		'asf'       => 'application/vnd.ms-asf',
		'cab'       => 'application/vnd.ms-cab-compressed',
		'xls'       => 'application/vnd.ms-excel',
		'xlm'       => 'application/vnd.ms-excel',
		'xla'       => 'application/vnd.ms-excel',
		'xlc'       => 'application/vnd.ms-excel',
		'xlt'       => 'application/vnd.ms-excel',
		'xlw'       => 'application/vnd.ms-excel',
		'eot'       => 'application/vnd.ms-fontobject',
		'chm'       => 'application/vnd.ms-htmlhelp',
		'ims'       => 'application/vnd.ms-ims',
		'lrm'       => 'application/vnd.ms-lrm',
		'ppt'       => 'application/vnd.ms-powerpoint',
		'pps'       => 'application/vnd.ms-powerpoint',
		'pot'       => 'application/vnd.ms-powerpoint',
		'mpp'       => 'application/vnd.ms-project',
		'mpt'       => 'application/vnd.ms-project',
		'wps'       => 'application/vnd.ms-works',
		'wks'       => 'application/vnd.ms-works',
		'wcm'       => 'application/vnd.ms-works',
		'wdb'       => 'application/vnd.ms-works',
		'wpl'       => 'application/vnd.ms-wpl',
		'xps'       => 'application/vnd.ms-xpsdocument',
		'mseq'      => 'application/vnd.mseq',
		'mus'       => 'application/vnd.musician',
		'msty'      => 'application/vnd.muvee.style',
		'nlu'       => 'application/vnd.neurolanguage.nlu',
		'nnd'       => 'application/vnd.noblenet-directory',
		'nns'       => 'application/vnd.noblenet-sealer',
		'nnw'       => 'application/vnd.noblenet-web',
		'ngdat'     => 'application/vnd.nokia.n-gage.data',
		'n-gage'    => 'application/vnd.nokia.n-gage.symbian.install',
		'rpst'      => 'application/vnd.nokia.radio-preset',
		'rpss'      => 'application/vnd.nokia.radio-presets',
		'edm'       => 'application/vnd.novadigm.edm',
		'edx'       => 'application/vnd.novadigm.edx',
		'ext'       => 'application/vnd.novadigm.ext',
		'odc'       => 'application/vnd.oasis.opendocument.chart',
		'otc'       => 'application/vnd.oasis.opendocument.chart-template',
		'odf'       => 'application/vnd.oasis.opendocument.formula',
		'otf'       => 'application/vnd.oasis.opendocument.formula-template',
		'odg'       => 'application/vnd.oasis.opendocument.graphics',
		'otg'       => 'application/vnd.oasis.opendocument.graphics-template',
		'odi'       => 'application/vnd.oasis.opendocument.image',
		'oti'       => 'application/vnd.oasis.opendocument.image-template',
		'odp'       => 'application/vnd.oasis.opendocument.presentation',
		'otp'       => 'application/vnd.oasis.opendocument.presentation-template',
		'ods'       => 'application/vnd.oasis.opendocument.spreadsheet',
		'ots'       => 'application/vnd.oasis.opendocument.spreadsheet-template',
		'odt'       => 'application/vnd.oasis.opendocument.text',
		'otm'       => 'application/vnd.oasis.opendocument.text-master',
		'ott'       => 'application/vnd.oasis.opendocument.text-template',
		'oth'       => 'application/vnd.oasis.opendocument.text-web',
		'xo'        => 'application/vnd.olpc-sugar',
		'dd2'       => 'application/vnd.oma.dd2+xml',
		'oxt'       => 'application/vnd.openofficeorg.extension',
		'dp'        => 'application/vnd.osgi.dp',
		'prc'       => 'application/vnd.palm',
		'pdb'       => 'application/vnd.palm',
		'pqa'       => 'application/vnd.palm',
		'oprc'      => 'application/vnd.palm',
		'str'       => 'application/vnd.pg.format',
		'ei6'       => 'application/vnd.pg.osasli',
		'efif'      => 'application/vnd.picsel',
		'plf'       => 'application/vnd.pocketlearn',
		'pbd'       => 'application/vnd.powerbuilder6',
		'box'       => 'application/vnd.previewsystems.box',
		'mgz'       => 'application/vnd.proteus.magazine',
		'qps'       => 'application/vnd.publishare-delta-tree',
		'ptid'      => 'application/vnd.pvi.ptid1',
		'qxd'       => 'application/vnd.quark.quarkxpress',
		'qxt'       => 'application/vnd.quark.quarkxpress',
		'qwd'       => 'application/vnd.quark.quarkxpress',
		'qwt'       => 'application/vnd.quark.quarkxpress',
		'qxl'       => 'application/vnd.quark.quarkxpress',
		'qxb'       => 'application/vnd.quark.quarkxpress',
		'mxl'       => 'application/vnd.recordare.musicxml',
		'rm'        => 'application/vnd.rn-realmedia',
		'see'       => 'application/vnd.seemail',
		'sema'      => 'application/vnd.sema',
		'semd'      => 'application/vnd.semd',
		'semf'      => 'application/vnd.semf',
		'ifm'       => 'application/vnd.shana.informed.formdata',
		'itp'       => 'application/vnd.shana.informed.formtemplate',
		'iif'       => 'application/vnd.shana.informed.interchange',
		'ipk'       => 'application/vnd.shana.informed.package',
		'twd'       => 'application/vnd.simtech-mindmapper',
		'twds'      => 'application/vnd.simtech-mindmapper',
		'mmf'       => 'application/vnd.smaf',
		'sdkm'      => 'application/vnd.solent.sdkm+xml',
		'sdkd'      => 'application/vnd.solent.sdkm+xml',
		'dxp'       => 'application/vnd.spotfire.dxp',
		'sfs'       => 'application/vnd.spotfire.sfs',
		'sus'       => 'application/vnd.sus-calendar',
		'susp'      => 'application/vnd.sus-calendar',
		'svd'       => 'application/vnd.svd',
		'xsm'       => 'application/vnd.syncml+xml',
		'bdm'       => 'application/vnd.syncml.dm+wbxml',
		'xdm'       => 'application/vnd.syncml.dm+xml',
		'tao'       => 'application/vnd.tao.intent-module-archive',
		'tmo'       => 'application/vnd.tmobile-livetv',
		'tpt'       => 'application/vnd.trid.tpt',
		'mxs'       => 'application/vnd.triscape.mxs',
		'tra'       => 'application/vnd.trueapp',
		'ufd'       => 'application/vnd.ufdl',
		'ufdl'      => 'application/vnd.ufdl',
		'utz'       => 'application/vnd.uiq.theme',
		'umj'       => 'application/vnd.umajin',
		'unityweb'  => 'application/vnd.unity',
		'uoml'      => 'application/vnd.uoml+xml',
		'vcx'       => 'application/vnd.vcx',
		'vsd'       => 'application/vnd.visio',
		'vst'       => 'application/vnd.visio',
		'vss'       => 'application/vnd.visio',
		'vsw'       => 'application/vnd.visio',
		'vis'       => 'application/vnd.visionary',
		'vsf'       => 'application/vnd.vsf',
		'wbxml'     => 'application/vnd.wap.wbxml',
		'wmlc'      => 'application/vnd.wap.wmlc',
		'wmlsc'     => 'application/vnd.wap.wmlscriptc',
		'wtb'       => 'application/vnd.webturbo',
		'wpd'       => 'application/vnd.wordperfect',
		'wqd'       => 'application/vnd.wqd',
		'stf'       => 'application/vnd.wt.stf',
		'xar'       => 'application/vnd.xara',
		'xfdl'      => 'application/vnd.xfdl',
		'hvd'       => 'application/vnd.yamaha.hv-dic',
		'hvs'       => 'application/vnd.yamaha.hv-script',
		'hvp'       => 'application/vnd.yamaha.hv-voice',
		'saf'       => 'application/vnd.yamaha.smaf-audio',
		'spf'       => 'application/vnd.yamaha.smaf-phrase',
		'cmp'       => 'application/vnd.yellowriver-custom-menu',
		'zaz'       => 'application/vnd.zzazz.deck+xml',
		'vxml'      => 'application/voicexml+xml',
		'hlp'       => 'application/winhlp',
		'wsdl'      => 'application/wsdl+xml',
		'wspolicy'  => 'application/wspolicy+xml',
		'ace'       => 'application/x-ace-compressed',
		'bcpio'     => 'application/x-bcpio',
		'torrent'   => 'application/x-bittorrent',
		'bz'        => 'application/x-bzip',
		'bz2'       => 'application/x-bzip2',
		'boz'       => 'application/x-bzip2',
		'vcd'       => 'application/x-cdlink',
		'chat'      => 'application/x-chat',
		'pgn'       => 'application/x-chess-pgn',
		'cpio'      => 'application/x-cpio',
		'csh'       => 'application/x-csh',
		'dcr'       => 'application/x-director',
		'dir'       => 'application/x-director',
		'dxr'       => 'application/x-director',
		'fgd'       => 'application/x-director',
		'dvi'       => 'application/x-dvi',
		'spl'       => 'application/x-futuresplash',
		'gtar'      => 'application/x-gtar',
		'hdf'       => 'application/x-hdf',
		'latex'     => 'application/x-latex',
		'wmd'       => 'application/x-ms-wmd',
		'wmz'       => 'application/x-ms-wmz',
		'mdb'       => 'application/x-msaccess',
		'obd'       => 'application/x-msbinder',
		'crd'       => 'application/x-mscardfile',
		'clp'       => 'application/x-msclip',
		'exe'       => 'application/x-msdownload',
		'dll'       => 'application/x-msdownload',
		'com'       => 'application/x-msdownload',
		'bat'       => 'application/x-msdownload',
		'msi'       => 'application/x-msdownload',
		'mvb'       => 'application/x-msmediaview',
		'm13'       => 'application/x-msmediaview',
		'm14'       => 'application/x-msmediaview',
		'wmf'       => 'application/x-msmetafile',
		'mny'       => 'application/x-msmoney',
		'pub'       => 'application/x-mspublisher',
		'scd'       => 'application/x-msschedule',
		'trm'       => 'application/x-msterminal',
		'wri'       => 'application/x-mswrite',
		'nc'        => 'application/x-netcdf',
		'cdf'       => 'application/x-netcdf',
		'p12'       => 'application/x-pkcs12',
		'pfx'       => 'application/x-pkcs12',
		'p7b'       => 'application/x-pkcs7-certificates',
		'spc'       => 'application/x-pkcs7-certificates',
		'p7r'       => 'application/x-pkcs7-certreqresp',
		'rar'       => 'application/x-rar-compressed',
		'sh'        => 'application/x-sh',
		'shar'      => 'application/x-shar',
		'swf'       => 'application/x-shockwave-flash',
		'sit'       => 'application/x-stuffit',
		'sitx'      => 'application/x-stuffitx',
		'sv4cpio'   => 'application/x-sv4cpio',
		'sv4crc'    => 'application/x-sv4crc',
		'tar'       => 'application/x-tar',
		'tcl'       => 'application/x-tcl',
		'tex'       => 'application/x-tex',
		'texinfo'   => 'application/x-texinfo',
		'texi'      => 'application/x-texinfo',
		'ustar'     => 'application/x-ustar',
		'src'       => 'application/x-wais-source',
		'der'       => 'application/x-x509-ca-cert',
		'crt'       => 'application/x-x509-ca-cert',
		'xenc'      => 'application/xenc+xml',
		'xhtml'     => 'application/xhtml+xml',
		'xht'       => 'application/xhtml+xml',
		'xml'       => 'application/xml',
		'xsl'       => 'application/xml',
		'dtd'       => 'application/xml-dtd',
		'xop'       => 'application/xop+xml',
		'xslt'      => 'application/xslt+xml',
		'xspf'      => 'application/xspf+xml',
		'mxml'      => 'application/xv+xml',
		'xhvml'     => 'application/xv+xml',
		'xvml'      => 'application/xv+xml',
		'xvm'       => 'application/xv+xml',
		'zip'       => 'application/zip',
		'au'        => 'audio/basic',
		'snd'       => 'audio/basic',
		'mid'       => 'audio/midi',
		'midi'      => 'audio/midi',
		'kar'       => 'audio/midi',
		'rmi'       => 'audio/midi',
		'mp4a'      => 'audio/mp4',
		'mpga'      => 'audio/mpeg',
		'mp2'       => 'audio/mpeg',
		'mp2a'      => 'audio/mpeg',
		'mp3'       => 'audio/mpeg',
		'm2a'       => 'audio/mpeg',
		'm3a'       => 'audio/mpeg',
		'eol'       => 'audio/vnd.digital-winds',
		'lvp'       => 'audio/vnd.lucent.voice',
		'ecelp4800' => 'audio/vnd.nuera.ecelp4800',
		'ecelp7470' => 'audio/vnd.nuera.ecelp7470',
		'ecelp9600' => 'audio/vnd.nuera.ecelp9600',
		'wav'       => 'audio/wav',
		'aif'       => 'audio/x-aiff',
		'aiff'      => 'audio/x-aiff',
		'aifc'      => 'audio/x-aiff',
		'm3u'       => 'audio/x-mpegurl',
		'wax'       => 'audio/x-ms-wax',
		'wma'       => 'audio/x-ms-wma',
		'ram'       => 'audio/x-pn-realaudio',
		'ra'        => 'audio/x-pn-realaudio',
		'rmp'       => 'audio/x-pn-realaudio-plugin',
		'wav'       => 'audio/x-wav',
		'cdx'       => 'chemical/x-cdx',
		'cif'       => 'chemical/x-cif',
		'cmdf'      => 'chemical/x-cmdf',
		'cml'       => 'chemical/x-cml',
		'csml'      => 'chemical/x-csml',
		'pdb'       => 'chemical/x-pdb',
		'xyz'       => 'chemical/x-xyz',
		'bmp'       => 'image/bmp',
		'cgm'       => 'image/cgm',
		'g3'        => 'image/g3fax',
		'gif'       => 'image/gif',
		'ief'       => 'image/ief',
		'jpeg'      => 'image/jpeg',
		'jpg'       => 'image/jpeg',
		'jpe'       => 'image/jpeg',
		'png'       => 'image/png',
		'btif'      => 'image/prs.btif',
		'svg'       => 'image/svg+xml',
		'svgz'      => 'image/svg+xml',
		'tiff'      => 'image/tiff',
		'tif'       => 'image/tiff',
		'psd'       => 'image/vnd.adobe.photoshop',
		'djvu'      => 'image/vnd.djvu',
		'djv'       => 'image/vnd.djvu',
		'dwg'       => 'image/vnd.dwg',
		'dxf'       => 'image/vnd.dxf',
		'fbs'       => 'image/vnd.fastbidsheet',
		'fpx'       => 'image/vnd.fpx',
		'fst'       => 'image/vnd.fst',
		'mmr'       => 'image/vnd.fujixerox.edmics-mmr',
		'rlc'       => 'image/vnd.fujixerox.edmics-rlc',
		'mdi'       => 'image/vnd.ms-modi',
		'npx'       => 'image/vnd.net-fpx',
		'wbmp'      => 'image/vnd.wap.wbmp',
		'xif'       => 'image/vnd.xiff',
		'ras'       => 'image/x-cmu-raster',
		'cmx'       => 'image/x-cmx',
		'ico'       => 'image/x-icon',
		'pcx'       => 'image/x-pcx',
		'pic'       => 'image/x-pict',
		'pct'       => 'image/x-pict',
		'pnm'       => 'image/x-portable-anymap',
		'pbm'       => 'image/x-portable-bitmap',
		'pgm'       => 'image/x-portable-graymap',
		'ppm'       => 'image/x-portable-pixmap',
		'rgb'       => 'image/x-rgb',
		'xbm'       => 'image/x-xbitmap',
		'xpm'       => 'image/x-xpixmap',
		'xwd'       => 'image/x-xwindowdump',
		'eml'       => 'message/rfc822',
		'mime'      => 'message/rfc822',
		'igs'       => 'model/iges',
		'iges'      => 'model/iges',
		'msh'       => 'model/mesh',
		'mesh'      => 'model/mesh',
		'silo'      => 'model/mesh',
		'dwf'       => 'model/vnd.dwf',
		'gdl'       => 'model/vnd.gdl',
		'gtw'       => 'model/vnd.gtw',
		'mts'       => 'model/vnd.mts',
		'vtu'       => 'model/vnd.vtu',
		'wrl'       => 'model/vrml',
		'vrml'      => 'model/vrml',
		'ics'       => 'text/calendar',
		'ifb'       => 'text/calendar',
		'css'       => 'text/css',
		'csv'       => 'text/csv',
		'html'      => 'text/html',
		'htm'       => 'text/html',
		'txt'       => 'text/plain',
		'text'      => 'text/plain',
		'conf'      => 'text/plain',
		'def'       => 'text/plain',
		'list'      => 'text/plain',
		'log'       => 'text/plain',
		'in'        => 'text/plain',
		'dsc'       => 'text/prs.lines.tag',
		'rtx'       => 'text/richtext',
		'sgml'      => 'text/sgml',
		'sgm'       => 'text/sgml',
		'tsv'       => 'text/tab-separated-values',
		't'         => 'text/troff',
		'tr'        => 'text/troff',
		'roff'      => 'text/troff',
		'man'       => 'text/troff',
		'me'        => 'text/troff',
		'ms'        => 'text/troff',
		'uri'       => 'text/uri-list',
		'uris'      => 'text/uri-list',
		'urls'      => 'text/uri-list',
		'fly'       => 'text/vnd.fly',
		'flx'       => 'text/vnd.fmi.flexstor',
		'3dml'      => 'text/vnd.in3d.3dml',
		'spot'      => 'text/vnd.in3d.spot',
		'jad'       => 'text/vnd.sun.j2me.app-descriptor',
		'wml'       => 'text/vnd.wap.wml',
		'wmls'      => 'text/vnd.wap.wmlscript',
		's'         => 'text/x-asm',
		'asm'       => 'text/x-asm',
		'c'         => 'text/x-c',
		'cc'        => 'text/x-c',
		'cxx'       => 'text/x-c',
		'cpp'       => 'text/x-c',
		'h'         => 'text/x-c',
		'hh'        => 'text/x-c',
		'dic'       => 'text/x-c',
		'f'         => 'text/x-fortran',
		'for'       => 'text/x-fortran',
		'f77'       => 'text/x-fortran',
		'f90'       => 'text/x-fortran',
		'p'         => 'text/x-pascal',
		'pas'       => 'text/x-pascal',
		'java'      => 'text/x-java-source',
		'etx'       => 'text/x-setext',
		'uu'        => 'text/x-uuencode',
		'vcs'       => 'text/x-vcalendar',
		'vcf'       => 'text/x-vcard',
		'3gp'       => 'video/3gpp',
		'3g2'       => 'video/3gpp2',
		'h261'      => 'video/h261',
		'h263'      => 'video/h263',
		'h264'      => 'video/h264',
		'jpgv'      => 'video/jpeg',
		'jpm'       => 'video/jpm',
		'jpgm'      => 'video/jpm',
		'mj2'       => 'video/mj2',
		'mjp2'      => 'video/mj2',
		'mp4'       => 'video/mp4',
		'mp4v'      => 'video/mp4',
		'mpg4'      => 'video/mp4',
		'mpeg'      => 'video/mpeg',
		'mpg'       => 'video/mpeg',
		'mpe'       => 'video/mpeg',
		'm1v'       => 'video/mpeg',
		'm2v'       => 'video/mpeg',
		'qt'        => 'video/quicktime',
		'mov'       => 'video/quicktime',
		'fvt'       => 'video/vnd.fvt',
		'mxu'       => 'video/vnd.mpegurl',
		'm4u'       => 'video/vnd.mpegurl',
		'viv'       => 'video/vnd.vivo',
		'fli'       => 'video/x-fli',
		'asf'       => 'video/x-ms-asf',
		'asx'       => 'video/x-ms-asf',
		'wm'        => 'video/x-ms-wm',
		'wmv'       => 'video/x-ms-wmv',
		'wmx'       => 'video/x-ms-wmx',
		'wvx'       => 'video/x-ms-wvx',
		'avi'       => 'video/x-msvideo',
		'movie'     => 'video/x-sgi-movie',
		'ice'       => 'x-conference/x-cooltalk'
	);

	$info = pathinfo($file);

	if (isset($info['extension']) and isset($mime_content_types[$info['extension']])) {
		return $mime_content_types[$info['extension']];
	} else {
		return 'text/plain';
	}
}

/* ディレクトリ作成 */
function freo_mkdir($path, $mode = 0707, $recursive = true)
{
	global $freo;

	if (file_exists($path)) {
		return true;
	}

	if (mkdir($path, $mode, $recursive)) {
		chmod($path, $mode);

		return true;
	} else {
		return false;
	}
}

/* ディレクトリ削除 */
function freo_rmdir($path, $recursive = true)
{
	global $freo;

	if (!file_exists($path)) {
		return true;
	}

	$flag = false;

	if ($dir = scandir($path)) {
		foreach ($dir as $data) {
			if ($data == '.' or $data == '..') {
				continue;
			}
			if (is_dir($path . $data)) {
				if ($recursive and !freo_rmdir($path . $data . '/')) {
					return false;
				}

				$flag = true;
			} elseif (is_file($path . $data)) {
				if (!unlink($path . $data)) {
					return false;
				}
			}
		}
	} else {
		return false;
	}

	if (!$recursive and $flag) {
		return true;
	}

	if (rmdir($path)) {
		return true;
	} else {
		return false;
	}
}

/* ファイル縮小 */
function freo_resize($original, $output, $output_width, $output_height, $flag = false)
{
	global $freo;

	if (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $original)) {
		return;
	}

	list($original_width, $original_height) = freo_file($original);

	if ($original_width > $output_width) {
		$width  = $output_width;
		$height = ($width / $original_width) * $original_height;
	} else {
		$width  = $original_width;
		$height = $original_height;
	}
	if ($height > $output_height) {
		$width  = ($output_height / $height) * $width;
		$height = $output_height;
	}

	if ($original_width == $width and $original_height == $height) {
		if ($flag) {
			readfile($original);
		}

		return;
	}

	if (FREO_IMAGEMAGICK_MODE) {
		if (preg_match('/\.gif$/i', $output)) {
			$type = 'gif';
		} elseif (preg_match('/\.(jpeg|jpg|jpe)$/i', $output)) {
			$type = 'jpg';
		} elseif (preg_match('/\.png$/i', $output)) {
			$type = 'png';
		}

		$option = '-geometry ' . $width . 'x' . $height;

		if ($type == 'jpg') {
			$option .= ' -quality ' . FREO_IMAGEMAGICK_QUALITY;
		}

		$converted = shell_exec(FREO_IMAGEMAGICK_PATH . ' ' . $option . ' ' . $original . ' ' . $type . ':-');

		if ($flag) {
			echo $converted;
		} else {
			file_put_contents($output, $converted);
		}
	} else {
		if (preg_match('/\.gif$/i', $original)) {
			$file = imagecreatefromgif($original);
		} elseif (preg_match('/\.(jpeg|jpg|jpe)$/i', $original)) {
			$file = imagecreatefromjpeg($original);
		} elseif (preg_match('/\.png$/i', $original)) {
			$file = imagecreatefrompng($original);
		}

		$thumbnail = imagecreatetruecolor($width, $height);

		imagecopyresampled($thumbnail, $file, 0, 0, 0, 0, $width, $height, $original_width, $original_height);

		if ($flag) {
			if (preg_match('/\.gif$/i', $output)) {
				imagegif($thumbnail);
			} elseif (preg_match('/\.(jpeg|jpg|jpe)$/i', $output)) {
				imagejpeg($thumbnail, null, FREO_GD_QUALITY);
			} elseif (preg_match('/\.png$/i', $output)) {
				imagepng($thumbnail);
			}
		} else {
			if (preg_match('/\.gif$/i', $output)) {
				imagegif($thumbnail, $output);
			} elseif (preg_match('/\.(jpeg|jpg|jpe)$/i', $output)) {
				imagejpeg($thumbnail, $output, FREO_GD_QUALITY);
			} elseif (preg_match('/\.png$/i', $output)) {
				imagepng($thumbnail, $output);
			}
		}

		imagedestroy($thumbnail);
	}

	return;
}

/* Cookie設定 */
function freo_setcookie($name, $value, $expire = 0, $path = '', $domain = '', $secure = false)
{
	global $freo;

	if (FREO_HTTPS_URL and isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] != 'off') {
		$info = parse_url(FREO_HTTPS_URL);
	} else {
		$info = parse_url(FREO_HTTP_URL);
	}

	if (!$path) {
		$path = $info['path'];
	}
	if (!$domain and $info['host'] != 'localhost') {
		$domain = $info['host'];
	}

	return setcookie($name, $value, $expire, $path, $domain, $secure);
}

/* キャリア判別 */
function freo_ip2career($ip = null)
{
	global $freo;

	if ($ip == null) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	$n = sprintf('%u', ip2long($ip));

	if ($n < 1914044160) {
		if ($n < 1868151808) {
			if ($n < 1036427264) {
				if ($n < 1036419072) {
					if ($n < 1031078144) {
						if ($n >= 998712960 and $n <= 998713087) {
							return 'au';
						}
					} elseif ($n <= 1031078159) {
						return 'au';
					} else {
						if ($n >= 1031078432 and $n <= 1031078447) {
							return 'au';
						}
					}
				} elseif ($n <= 1036421631) {
					return 'willcom';
				} else {
					if ($n < 1036421888) {
						if ($n >= 1036421732 and $n <= 1036421735) {
							return 'willcom';
						}
					} elseif ($n <= 1036421895) {
						return 'willcom';
					} else {
						if ($n < 1036422016) {
						} elseif ($n <= 1036422063) {
							return 'willcom';
						} else {
							if ($n >= 1036422144 and $n <= 1036423167) {
								return 'willcom';
							}
						}
					}
				}
			} elseif ($n <= 1036429055) {
				return 'willcom';
			} else {
				if ($n < 1036803072) {
					if ($n < 1036449792) {
						if ($n >= 1036429312 and $n <= 1036431359) {
							return 'willcom';
						}
					} elseif ($n <= 1036451839) {
						return 'willcom';
					} else {
						if ($n < 1036779520) {
						} elseif ($n <= 1036779775) {
							return 'willcom';
						} else {
							if ($n >= 1036780032 and $n <= 1036781439) {
								return 'willcom';
							}
						}
					}
				} elseif ($n <= 1036804095) {
					return 'willcom';
				} else {
					if ($n < 1867943552) {
						if ($n >= 1867943232 and $n <= 1867943487) {
							return 'au';
						}
					} elseif ($n <= 1867943743) {
						return 'au';
					} else {
						if ($n < 1867943872) {
						} elseif ($n <= 1867943935) {
							return 'au';
						} else {
							if ($n >= 1867944704 and $n <= 1867944735) {
								return 'au';
							}
						}
					}
				}
			}
		} elseif ($n <= 1868152831) {
			return 'docomo';
		} else {
			if ($n < 1913928192) {
				if ($n < 1913926656) {
					if ($n < 1913925888) {
						if ($n >= 1913925888 and $n <= 1913926143) {
							return 'willcom';
						}
					} elseif ($n <= 1913926399) {
						return 'willcom';
					} else {
						if ($n < 1913926144) {
						} elseif ($n <= 1913926655) {
							return 'willcom';
						} else {
							if ($n >= 1913926400 and $n <= 1913926911) {
								return 'willcom';
							}
						}
					}
				} elseif ($n <= 1913927423) {
					return 'willcom';
				} else {
					if ($n < 1913927424) {
						if ($n >= 1913927168 and $n <= 1913927679) {
							return 'willcom';
						}
					} elseif ($n <= 1913927935) {
						return 'willcom';
					} else {
						if ($n < 1913927680) {
						} elseif ($n <= 1913928191) {
							return 'willcom';
						} else {
							if ($n >= 1913927936 and $n <= 1913928447) {
								return 'willcom';
							}
						}
					}
				}
			} elseif ($n <= 1913928703) {
				return 'willcom';
			} else {
				if ($n < 1913929472) {
					if ($n < 1913928704) {
						if ($n >= 1913928448 and $n <= 1913928959) {
							return 'willcom';
						}
					} elseif ($n <= 1913929215) {
						return 'willcom';
					} else {
						if ($n < 1913928960) {
						} elseif ($n <= 1913929471) {
							return 'willcom';
						} else {
							if ($n >= 1913929216 and $n <= 1913929727) {
								return 'willcom';
							}
						}
					}
				} elseif ($n <= 1913929983) {
					return 'willcom';
				} else {
					if ($n < 1913929984) {
						if ($n >= 1913929728 and $n <= 1913930239) {
							return 'willcom';
						}
					} elseif ($n <= 1913930495) {
						return 'willcom';
					} else {
						if ($n < 1913930240) {
						} elseif ($n <= 1913930751) {
							return 'willcom';
						} else {
							if ($n >= 1913930496 and $n <= 1913930751) {
								return 'willcom';
							}
						}
					}
				}
			}
		}
	} elseif ($n <= 1914044191) {
		return 'willcom';
	} else {
		if ($n < 3414870784) {
			if ($n < 2037376768) {
				if ($n < 1990165664) {
					if ($n < 1989727936) {
						if ($n >= 1914044160 and $n <= 1914044191) {
							return 'willcom';
						}
					} elseif ($n <= 1989727999) {
						return 'au';
					} else {
						if ($n >= 1990165248 and $n <= 1990165375) {
							return 'au';
						}
					}
				} elseif ($n <= 1990165695) {
					return 'au';
				} else {
					if ($n < 1990165952) {
						if ($n >= 1990165760 and $n <= 1990165887) {
							return 'au';
						}
					} elseif ($n <= 1990166015) {
						return 'au';
					} else {
						if ($n < 2037375744) {
						} elseif ($n <= 2037375871) {
							return 'au';
						} else {
							if ($n >= 2037375904 and $n <= 2037375935) {
								return 'au';
							}
						}
					}
				}
			} elseif ($n <= 2037376895) {
				return 'au';
			} else {
				if ($n < 2098989824) {
					if ($n < 2070736352) {
						if ($n >= 2070736128 and $n <= 2070736159) {
							return 'softbank';
						}
					} elseif ($n <= 2070736383) {
						return 'softbank';
					} else {
						if ($n < 2089987584) {
						} elseif ($n <= 2089988095) {
							return 'docomo';
						} else {
							if ($n >= 2098987008 and $n <= 2098989311) {
								return 'willcom';
							}
						}
					}
				} elseif ($n <= 2098991615) {
					return 'willcom';
				} else {
					if ($n < 3405602816) {
						if ($n >= 3404050432 and $n <= 3404051455) {
							return 'docomo';
						}
					} elseif ($n <= 3405602847) {
						return 'softbank';
					} else {
						if ($n < 3405603040) {
						} elseif ($n <= 3405603071) {
							return 'softbank';
						} else {
							if ($n >= 3414864896 and $n <= 3414865407) {
								return 'docomo';
							}
						}
					}
				}
			}
		} elseif ($n <= 3414871039) {
			return 'docomo';
		} else {
			if ($n < 3548299392) {
				if ($n < 3534288384) {
					if ($n < 3532785600) {
						if ($n >= 3532169472 and $n <= 3532169727) {
							return 'docomo';
						}
					} elseif ($n <= 3532785663) {
						return 'softbank';
					} else {
						if ($n < 3533263872) {
						} elseif ($n <= 3533264127) {
							return 'docomo';
						} else {
							if ($n >= 3533264384 and $n <= 3533264895) {
								return 'docomo';
							}
						}
					}
				} elseif ($n <= 3534288895) {
					return 'willcom';
				} else {
					if ($n < 3534684544) {
						if ($n >= 3534314496 and $n <= 3534316543) {
							return 'willcom';
						}
					} elseif ($n <= 3534684671) {
						return 'softbank';
					} else {
						if ($n < 3538321632) {
						} elseif ($n <= 3538321647) {
							return 'au';
						} else {
							if ($n >= 3541231616 and $n <= 3541233663) {
								return 'willcom';
							}
						}
					}
				}
			} elseif ($n <= 3548299519) {
				return 'willcom';
			} else {
				if ($n < 3682439424) {
					if ($n < 3681328384) {
						if ($n >= 3681288704 and $n <= 3681292287) {
							return 'willcom';
						}
					} elseif ($n <= 3681328511) {
						return 'au';
					} else {
						if ($n < 3681328640) {
						} elseif ($n <= 3681328671) {
							return 'au';
						} else {
							if ($n >= 3681328680 and $n <= 3681328687) {
								return 'au';
							}
						}
					}
				} elseif ($n <= 3682439551) {
					return 'au';
				} else {
					if ($n < 3682440192) {
						if ($n >= 3682439680 and $n <= 3682439695) {
							return 'au';
						}
					} elseif ($n <= 3682440319) {
						return 'au';
					} else {
						if ($n < 3715563520) {
						} elseif ($n <= 3715566079) {
							return 'willcom';
						} else {
							if ($n >= 3724885632 and $n <= 3724886015) {
								return 'au';
							}
						}
					}
				}
			}
		}
	}

	return 'pc';
}

/* メール送信 */
function freo_mail($to, $subject, $message, $headers = array(), $files = array())
{
	global $freo;

	//データ調整
	$subject = $freo->config['basis']['mail_prefix'] . $subject;

	$subject = mb_convert_kana(freo_unify($subject), 'KV', 'UTF-8');
	$message = mb_convert_kana(freo_unify($message), 'KV', 'UTF-8');

	if (FREO_PICTOGRAM_MODE) {
		$subject = freo_pictogram_except($subject);
		$message = freo_pictogram_except($message);
	}

	//エンコード
	$subject = mb_convert_encoding($subject, 'JIS', 'UTF-8');
	$message = mb_convert_encoding($message, 'JIS', 'UTF-8');

	$subject = '=?iso-2022-jp?B?' . base64_encode($subject) . '?=';

	//バウンダリ文字列を定義
	if (empty($files)) {
		$boundary = null;
	} else {
		$boundary = md5(uniqid(rand(), true));
	}

	//メールボディを定義
	if (empty($files)) {
		$body = $message;
	} else {
		$body  = "--$boundary\n";
		$body .= "Content-Type: text/plain; charset=\"iso-2022-jp\"\n";
		$body .= "Content-Transfer-Encoding: 7bit\n";
		$body .= "\n";
		$body .= "$message\n";

		foreach($files as $file) {
			if (!file_exists($file)) {
				continue;
			}

			$filename = basename($file);

			$body .= "\n";
			$body .= "--$boundary\n";
			$body .= "Content-Type: " . freo_mime($file) . "; name=\"$filename\"\n";
			$body .= "Content-Disposition: attachment; filename=\"$filename\"\n";
			$body .= "Content-Transfer-Encoding: base64\n";
			$body .= "\n";
			$body .= chunk_split(base64_encode(file_get_contents($file))) . "\n";
		}

		$body .= '--' . $boundary . '--';
	}

	//メールヘッダを定義
	if (!isset($headers['X-Mailer'])) {
		$headers['X-Mailer'] = 'freo';
	}
	if (!isset($headers['From'])) {
		$headers['From'] = '"' . mb_encode_mimeheader(mb_convert_kana($freo->config['basis']['mail_name'], 'KV', 'UTF-8')) . '" <' . $freo->config['basis']['mail_from'] . '>';
	}
	if (!isset($headers['MIME-Version'])) {
		$headers['MIME-Version'] = '1.0';
	}
	if (!isset($headers['Content-Type'])) {
		if (empty($files)) {
			$headers['Content-Type'] = 'text/plain; charset="iso-2022-jp"';
		} else {
			$headers['Content-Type'] = 'multipart/mixed; boundary="' . $boundary . '"';
		}
	}
	if (!isset($headers['Content-Transfer-Encoding'])) {
		$headers['Content-Transfer-Encoding'] = '7bit';
	}

	$header = null;
	foreach ($headers as $key => $value) {
		if ($header) {
			$header .= "\n";
		}

		$header .= $key . ': ' . $value;
	}

	//メール送信
	return mail($to, $subject, $body, $header);
}

/* トラックバック送信 */
function freo_trackback($trackback_url, $title, $url, $excerpt, $blog_name, $headers = array())
{
	global $freo;

	//データ調整
	$excerpt = str_replace("\n", '', strip_tags($excerpt));
	$excerpt = strlen($excerpt) > 255 ? mb_strimwidth($excerpt, 0, 252, '...', 'UTF-8') : $excerpt;
	$query   = 'title=' . urlencode($title) . '&url=' . urlencode($url) . '&excerpt=' . urlencode($excerpt) . '&blog_name=' . urlencode($blog_name);

	//HTTPヘッダを定義
	if (!isset($headers['User-Agent'])) {
		$headers['User-Agent'] = 'freo';
	}
	if (!isset($headers['Content-Type'])) {
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';
	}

	$header = null;
	foreach ($headers as $key => $value) {
		$header .= $key . ': ' . $value . "\r\n";
	}

	$info = parse_url($trackback_url);

	$request  = "POST " . $info['path'] . (isset($info['query']) ? '?' . $info['query'] : '') . " HTTP/1.0\r\n";
	$request .= "Host: " . $info['host'] . "\r\n";
	$request .= $header;
	$request .= "Content-Length: " . strlen($query) . "\r\n";
	$request .= "\r\n";
	$request .= "$query\r\n";

	//データ送信
	$data = '';

	if ($sock = fsockopen($info['host'], 80)) {
		fputs($sock, $request);
		while (!feof($sock)) {
			$data .= fgets($sock);
		}
		fclose($sock);
	} else {
		return array(false, 'トラックバック送信先 ' . $trackback_url . ' に接続できません。');
	}

	if (preg_match('/<error>1<\/error>[^<]*<message>(.+)<\/message>/', $data, $matches)) {
		return array(false, 'トラックバック送信先 ' . $trackback_url . ' からエラーメッセージ「' . $matches[1] . '」が返されました。');
	} elseif (!preg_match('/<error>0<\/error>/', $data, $matches)) {
		return array(false, 'トラックバック送信先 ' . $trackback_url . ' から正常な値が返されませんでした。');
	}

	return array(true, null);
}

?>
