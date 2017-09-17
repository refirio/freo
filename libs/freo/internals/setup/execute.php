<?php

/*********************************************************************

 freo | セットアップ | 実行 (2012/11/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('setup?error=1', true);
	}

	//入力データ取得
	if (!empty($_SESSION['input'])) {
		$setup = $_SESSION['input'];
	}

	//パーミッション設定
	if (FREO_PERMISSION_MODE) {
		chmod(FREO_CONFIG_DIR, FREO_PERMISSION_DIR);
		chmod(FREO_CONFIG_DIR . 'plugins/', FREO_PERMISSION_DIR);
		chmod(FREO_TEMPLATE_COMPILE_DIR, FREO_PERMISSION_DIR);
		chmod(FREO_FILE_DIR, FREO_PERMISSION_DIR);

		if ($dir = scandir(FREO_CONFIG_DIR)) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}
				if (!is_file(FREO_CONFIG_DIR . $data)) {
					continue;
				}

				chmod(FREO_CONFIG_DIR . $data, FREO_PERMISSION_DATA);
			}
		} else {
			freo_error('設定ファイルのパーミッションを設定できません。');
		}

		if ($dir = scandir(FREO_CONFIG_DIR . 'plugins/')) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}
				if (!is_file(FREO_CONFIG_DIR . 'plugins/' . $data)) {
					continue;
				}

				chmod(FREO_CONFIG_DIR . 'plugins/' . $data, FREO_PERMISSION_DATA);
			}
		} else {
			freo_error('プラグイン用設定ファイルのパーミッションを設定できません。');
		}

		if ($dir = scandir(FREO_FILE_DIR)) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}
				if (!is_dir(FREO_FILE_DIR . $data)) {
					continue;
				}

				chmod(FREO_FILE_DIR . $data, FREO_PERMISSION_DIR);
			}
		} else {
			freo_error('アップロードファイル格納ディレクトリのパーミッションを設定できません。');
		}

		if ($dir = scandir(FREO_FILE_DIR . 'temporaries/')) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}
				if (!is_dir(FREO_FILE_DIR . 'temporaries/' . $data)) {
					continue;
				}

				chmod(FREO_FILE_DIR . 'temporaries/' . $data, FREO_PERMISSION_DIR);
			}
		} else {
			freo_error('一時ファイル格納ディレクトリのパーミッションを設定できません。');
		}

		if ($dir = scandir(FREO_FILE_DIR . 'plugins/')) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}

				if (is_dir(FREO_FILE_DIR . 'plugins/' . $data)) {
					chmod(FREO_FILE_DIR . 'plugins/' . $data, FREO_PERMISSION_DIR);
				} elseif (is_file(FREO_FILE_DIR . 'plugins/' . $data)) {
					chmod(FREO_FILE_DIR . 'plugins/' . $data, FREO_PERMISSION_FILE);
				}
			}
		} else {
			freo_error('プラグイン用アップロードファイル格納ディレクトリのパーミッションを設定できません。');
		}

		if ($dir = scandir(FREO_FILE_DIR . 'temporaries/plugins/')) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}

				if (is_dir(FREO_FILE_DIR . 'temporaries/plugins/' . $data)) {
					chmod(FREO_FILE_DIR . 'temporaries/plugins/' . $data, FREO_PERMISSION_DIR);
				} elseif (is_file(FREO_FILE_DIR . 'temporaries/plugins/' . $data)) {
					chmod(FREO_FILE_DIR . 'temporaries/plugins/' . $data, FREO_PERMISSION_FILE);
				}
			}
		} else {
			freo_error('プラグイン用一時ファイル格納ディレクトリのパーミッションを設定できません。');
		}

		if (FREO_DATABASE_TYPE != 'mysql') {
			chmod(FREO_DATABASE_DIR, FREO_PERMISSION_DIR);
			chmod(FREO_DATABASE_DIR . FREO_DATABASE_NAME, FREO_PERMISSION_DATA);
		}
	}

	//データベーステーブル存在検証
	if (FREO_DATABASE_TYPE == 'mysql') {
		$query = 'SHOW TABLES';
	} else {
		$query = 'SELECT name FROM sqlite_master WHERE type = \'table\'';
	}
	$stmt = $freo->pdo->query($query);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$table = array();
	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$table[$data[0]] = true;
	}

	//データベーステーブル定義
	if (FREO_DATABASE_TYPE == 'mysql') {
		$queries = array(
			'users'         => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR(20) NOT NULL, authority VARCHAR(20) NOT NULL, password VARCHAR(40) NOT NULL, session VARCHAR(40) UNIQUE, serial VARCHAR(40) UNIQUE, name VARCHAR(255) NOT NULL, mail VARCHAR(80) NOT NULL UNIQUE, url VARCHAR(255), text TEXT, PRIMARY KEY(id))',
			'entries'       => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, user_id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR(20) NOT NULL, restriction VARCHAR(20), password VARCHAR(80), status VARCHAR(20) NOT NULL, display VARCHAR(20) NOT NULL, comment VARCHAR(20) NOT NULL, trackback VARCHAR(20) NOT NULL, code VARCHAR(80) UNIQUE, title VARCHAR(255) NOT NULL, tag VARCHAR(255), datetime DATETIME NOT NULL, close DATETIME, file VARCHAR(80), image VARCHAR(80), memo VARCHAR(255), text LONGTEXT, PRIMARY KEY(id))',
			'pages'         => '(id VARCHAR(80) NOT NULL, pid VARCHAR(80), user_id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR(20) NOT NULL, restriction VARCHAR(20), password VARCHAR(80), status VARCHAR(20) NOT NULL, display VARCHAR(20) NOT NULL, comment VARCHAR(20) NOT NULL, trackback VARCHAR(20) NOT NULL, sort INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, tag VARCHAR(255), datetime DATETIME NOT NULL, close DATETIME, file VARCHAR(80), image VARCHAR(80), memo VARCHAR(255), text LONGTEXT, PRIMARY KEY(id))',
			'groups'        => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, memo TEXT, PRIMARY KEY(id))',
			'group_sets'    => '(group_id VARCHAR(80) NOT NULL, user_id VARCHAR(80), entry_id INT UNSIGNED, page_id VARCHAR(80))',
			'filters'       => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, memo TEXT, PRIMARY KEY(id))',
			'filter_sets'   => '(filter_id VARCHAR(80) NOT NULL, entry_id INT UNSIGNED, page_id VARCHAR(80))',
			'options'       => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, target VARCHAR(20), type VARCHAR(20) NOT NULL, required VARCHAR(20) NOT NULL, validate VARCHAR(20), sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, memo TEXT, text TEXT, PRIMARY KEY(id))',
			'option_sets'   => '(option_id VARCHAR(80) NOT NULL, entry_id INT UNSIGNED, page_id VARCHAR(80), text TEXT NOT NULL)',
			'comments'      => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, entry_id INT UNSIGNED, page_id VARCHAR(80), user_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR(20) NOT NULL, restriction VARCHAR(20), name VARCHAR(255), mail VARCHAR(80), url VARCHAR(255), ip VARCHAR(80) NOT NULL, text TEXT NOT NULL, PRIMARY KEY(id))',
			'trackbacks'    => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, entry_id INT UNSIGNED, page_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR(20) NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, ip VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, text TEXT, PRIMARY KEY(id))',
			'categories'    => '(id VARCHAR(80) NOT NULL, pid VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, display VARCHAR(20) NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, memo TEXT, PRIMARY KEY(id))',
			'category_sets' => '(category_id VARCHAR(80) NOT NULL, entry_id INT UNSIGNED NOT NULL)',
			'informations'  => '(id VARCHAR(80) NOT NULL, entry_id INT UNSIGNED, page_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, text TEXT, PRIMARY KEY(id))',
			'logs'          => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, user_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, ip VARCHAR(80) NOT NULL, plugin VARCHAR(80), text TEXT NOT NULL, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'users'         => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR NOT NULL, authority VARCHAR NOT NULL, password VARCHAR NOT NULL, session VARCHAR UNIQUE, serial VARCHAR UNIQUE, name VARCHAR NOT NULL, mail VARCHAR NOT NULL UNIQUE, url VARCHAR, text TEXT, PRIMARY KEY(id))',
			'entries'       => '(id INTEGER, user_id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR NOT NULL, restriction VARCHAR, password VARCHAR, status VARCHAR NOT NULL, display VARCHAR NOT NULL, comment VARCHAR NOT NULL, trackback VARCHAR NOT NULL, code VARCHAR UNIQUE, title VARCHAR NOT NULL, tag VARCHAR, datetime DATETIME NOT NULL, close DATETIME, file VARCHAR, image VARCHAR, memo VARCHAR, text LONGTEXT, PRIMARY KEY(id))',
			'pages'         => '(id VARCHAR NOT NULL, pid VARCHAR, user_id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR NOT NULL, restriction VARCHAR, password VARCHAR, status VARCHAR NOT NULL, display VARCHAR NOT NULL, comment VARCHAR NOT NULL, trackback VARCHAR NOT NULL, sort INTEGER UNSIGNED NOT NULL, title VARCHAR NOT NULL, tag VARCHAR, datetime DATETIME NOT NULL, close DATETIME, file VARCHAR, image VARCHAR, memo VARCHAR, text LONGTEXT, PRIMARY KEY(id))',
			'groups'        => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, memo TEXT, PRIMARY KEY(id))',
			'group_sets'    => '(group_id VARCHAR NOT NULL, user_id VARCHAR, entry_id INTEGER UNSIGNED, page_id VARCHAR)',
			'filters'       => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, memo TEXT, PRIMARY KEY(id))',
			'filter_sets'   => '(filter_id VARCHAR NOT NULL, entry_id INTEGER UNSIGNED, page_id VARCHAR)',
			'options'       => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, target VARCHAR, type VARCHAR NOT NULL, required VARCHAR NOT NULL, validate VARCHAR(20), sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, memo TEXT, text TEXT, PRIMARY KEY(id))',
			'option_sets'   => '(option_id VARCHAR NOT NULL, entry_id INTEGER UNSIGNED, page_id VARCHAR, text TEXT NOT NULL)',
			'comments'      => '(id INTEGER, entry_id INTEGER UNSIGNED, page_id VARCHAR, user_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR NOT NULL, restriction VARCHAR, name VARCHAR, mail VARCHAR, url VARCHAR, ip VARCHAR NOT NULL, text TEXT NOT NULL, PRIMARY KEY(id))',
			'trackbacks'    => '(id INTEGER, entry_id INTEGER UNSIGNED, page_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, approved VARCHAR NOT NULL, name VARCHAR NOT NULL, url VARCHAR NOT NULL, ip VARCHAR NOT NULL, title VARCHAR NOT NULL, text TEXT, PRIMARY KEY(id))',
			'categories'    => '(id VARCHAR NOT NULL, pid VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, display VARCHAR NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, memo TEXT, PRIMARY KEY(id))',
			'category_sets' => '(category_id VARCHAR NOT NULL, entry_id INTEGER UNSIGNED NOT NULL)',
			'informations'  => '(id VARCHAR NOT NULL, entry_id INTEGER UNSIGNED, page_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, text TEXT, PRIMARY KEY(id))',
			'logs'          => '(id INTEGER, user_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, ip VARCHAR NOT NULL, plugin VARCHAR, text TEXT NOT NULL, PRIMARY KEY(id))'
		);
	}

	//データベーステーブル作成
	foreach ($queries as $name => $query) {
		if (empty($table[FREO_DATABASE_PREFIX . $name])) {
			$stmt = $freo->pdo->query('CREATE TABLE ' . FREO_DATABASE_PREFIX . $name . $query);
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
		}
	}

	//初期データ登録
	if (!empty($setup)) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'users VALUES(:user1, :now1, :now2, \'yes\', \'root\', :password, NULL, NULL, :user2, :mail, NULL, NULL)');
		$stmt->bindValue(':user1',    $setup['user']);
		$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':password', md5($setup['password']));
		$stmt->bindValue(':user2',    $setup['user']);
		$stmt->bindValue(':mail',     $setup['mail']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('セットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('setup?exec=setup', true);

	return;
}

?>
