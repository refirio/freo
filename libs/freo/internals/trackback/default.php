<?php

/*********************************************************************

 freo | トラックバック | 登録 (2011/02/04)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_trackback.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	$errors = array();

	//リクエストメソッド検証
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		$errors[] = 'Request Method is Not Post';
	}

	//パラメータ検証
	if (!isset($_POST['id']) and isset($freo->parameters[1])) {
		$parameters = array();
		$i          = 0;
		while (isset($freo->parameters[++$i])) {
			if (!$freo->parameters[$i]) {
				continue;
			}

			$parameters[] = $freo->parameters[$i];
		}
		$_POST['id'] = implode('/', $parameters);
	}
	if (!isset($_POST['id'])) {
		$errors[] = 'No ID (id)';
	}

	if (isset($_POST['blog_name'])) {
		$_POST['name'] = mb_strlen($_POST['blog_name'], 'UTF-8') > 80 ? mb_strimwidth($_POST['blog_name'], 0, 80, null, 'UTF-8') : $_POST['blog_name'];
	} else {
		$_POST['name'] = null;
	}
	if (isset($_POST['title'])) {
		$_POST['title'] = mb_strlen($_POST['title'], 'UTF-8') > 80 ? mb_strimwidth($_POST['title'], 0, 80, null, 'UTF-8') : $_POST['title'];
	} else {
		$_POST['title'] = ' ';
	}
	if (isset($_POST['excerpt'])) {
		$_POST['text'] = mb_strlen($_POST['excerpt'], 'UTF-8') > 5000 ? mb_strimwidth($_POST['excerpt'], 0, 5000, null, 'UTF-8') : $_POST['excerpt'];
	} else {
		$_POST['text'] = ' ';
	}

	if (preg_match('/^\d+$/', $_POST['id'])) {
		$entry_id = $_POST['id'];
		$page_id  = null;
	} else {
		$entry_id = null;
		$page_id  = $_POST['id'];
	}

	//対象データ検証
	if ($entry_id) {
		$stmt = $freo->pdo->prepare('SELECT trackback FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now))');
		$stmt->bindValue(':id',  $_POST['id'], PDO::PARAM_INT);
	} else {
		$stmt = $freo->pdo->prepare('SELECT trackback FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now))');
		$stmt->bindValue(':id',  $_POST['id']);
	}
	$stmt->bindValue(':now', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($data['trackback'] == 'closed' or $data['trackback'] == 'view') {
			$errors[] = 'Forbidden Post';
		}
	} else {
		$errors[] = 'No Data';
	}

	if ($errors) {
		$freo->smarty->assign('message', $errors[0]);

		freo_output('internals/trackback/error.xml');

		exit;
	}

	//入力データ検証
	if (empty($errors)) {
		$errors = freo_validate_trackback('insert', array('trackback' => $_POST));
	}

	if (!empty($errors)) {
		$freo->smarty->assign('message', $errors[0]);

		freo_output('internals/trackback/error.xml');

		exit;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'trackbacks VALUES(NULL, :entry_id, :page_id, :now1, :now2, :approved, :name, :url, :ip, :title, :text)');
	$stmt->bindValue(':entry_id', $entry_id);
	$stmt->bindValue(':page_id',  $page_id);
	$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
	$stmt->bindValue(':approved', $freo->config['trackback']['approve']);
	$stmt->bindValue(':name',     $_POST['blog_name']);
	$stmt->bindValue(':url',      $_POST['url']);
	$stmt->bindValue(':ip',       $_SERVER['REMOTE_ADDR']);
	$stmt->bindValue(':title',    $_POST['title']);
	$stmt->bindValue(':text',     $_POST['text']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('トラックバックを新規に登録しました。');

	return;
}

?>
