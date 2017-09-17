<?php

/*********************************************************************

 freo | エントリー表示 (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_comment.php';
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_comment.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_trackback.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[1])) {
		$_GET['id'] = $freo->parameters[1];
	}
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = null;

		if (!isset($_GET['code']) and isset($freo->parameters[1])) {
			$_GET['code'] = $freo->parameters[1];
		}

		if (isset($_GET['code'])) {
			$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE code = :code');
			$stmt->bindValue(':code', $_GET['code']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$_GET['id'] = $data['id'];
			}
		}

		if (!$_GET['id']) {
			freo_error('表示したいエントリーを指定してください。');
		}
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($_POST['entry'])) {
			//パスワード認証
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND restriction = \'password\' AND password = :password');
			$stmt->bindValue(':id',       $_GET['id'], PDO::PARAM_INT);
			$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
			$stmt->bindValue(':password', $_POST['entry']['password']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$_SESSION['security']['entry'][$_GET['id']] = true;

				freo_redirect('view/' . $_GET['id']);
			} else {
				freo_error('パスワードが違います。');
			}
		} else {
			//ワンタイムトークン確認
			if (!freo_token('check')) {
				$freo->smarty->append('errors', '不正なアクセスです。');
			}

			//投稿者データ取得
			if ($freo->user['id']) {
				$_POST['comment']['name'] = null;
				$_POST['comment']['mail'] = null;
				$_POST['comment']['url']  = null;
			}

			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				$errors = freo_validate_comment('insert', $_POST);

				if ($errors) {
					foreach ($errors as $error) {
						$freo->smarty->append('errors', $error);
					}
				}
			}

			//エラー確認
			if ($freo->smarty->get_template_vars('errors')) {
				//エラー表示
				$comment = $_POST['comment'];
			} else {
				$_SESSION['input'] = $_POST;

				if (isset($_POST['preview'])) {
					//プレビューへ移動
					freo_redirect('comment/preview?entry_id=' . $_GET['id']);
				} else {
					//登録処理へ移動
					freo_redirect('comment/post?freo%5Btoken%5D=' . freo_token('create') . '&entry_id=' . $_GET['id']);
				}
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$comment = $_SESSION['input']['comment'];
		} elseif (!empty($_COOKIE['comment'])) {
			//登録者情報復元
			$comment = array(
				'name'    => isset($_COOKIE['comment']['name'])    ? $_COOKIE['comment']['name']    : '',
				'mail'    => isset($_COOKIE['comment']['mail'])    ? $_COOKIE['comment']['mail']    : '',
				'url'     => isset($_COOKIE['comment']['url'])     ? $_COOKIE['comment']['url']     : '',
				'session' => isset($_COOKIE['comment']['session']) ? $_COOKIE['comment']['session'] : ''
			);
		} else {
			//新規データ設定
			$comment = array();
		}
	}

	//エントリー取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
	$stmt->bindValue(':id',   $_GET['id'], PDO::PARAM_INT);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$entry = $data;
	} else {
		freo_error('指定されたエントリーが見つかりません。', '404 Not Found');
	}

	//コード確認
	if ($entry['code'] and !isset($_GET['code'])) {
		freo_redirect('view/' . $entry['code'] . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
	}

	//エントリー関連データ取得
	$entry_associates = freo_associate_entry('get', array($_GET['id']));
	$entry_associate  = $entry_associates[$_GET['id']];

	//エントリーフィルター取得
	$entry_filters = freo_filter_entry('user', array($_GET['id']));
	$entry_filter  = $entry_filters[$_GET['id']];

	if ($entry_filter) {
		$entry['comment']   = 'closed';
		$entry['trackback'] = 'closed';
		$entry['title']     = str_replace('[$title]', $entry['title'], $freo->config['entry']['filter_title']);
		$entry['file']      = null;
		$entry['image']     = null;
		$entry['memo']      = null;
		$entry['text']      = str_replace('[$text]', $entry['text'], $freo->config['entry']['filter_text']);

		if ($freo->config['entry']['filter_option']) {
			$entry_associate['option'] = array();
		}
	}

	//エントリー保護データ取得
	$entry_securities = freo_security_entry('user', array($_GET['id']));
	$entry_security   = $entry_securities[$_GET['id']];

	if ($entry_security) {
		$entry['comment']   = 'closed';
		$entry['trackback'] = 'closed';
		$entry['title']     = str_replace('[$title]', $entry['title'], $freo->config['entry']['restriction_title']);
		$entry['file']      = null;
		$entry['image']     = null;
		$entry['memo']      = null;
		$entry['text']      = str_replace('[$text]', $entry['text'], $freo->config['entry']['restriction_text']);

		if ($freo->config['entry']['restriction_option']) {
			$entry_associate['option'] = array();
		}
	}

	//エントリータグ取得
	if ($entry['tag']) {
		$entry_tags = explode(',', $entry['tag']);
	} else {
		$entry_tags = array();
	}

	//エントリーファイル取得
	if ($entry['file']) {
		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_files/' . $entry['id'] . '/' . $entry['file']);

		$entry_file = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	} else {
		$entry_file = array();
	}

	//エントリーサムネイル取得
	if ($entry['file'] and file_exists(FREO_FILE_DIR . 'entry_thumbnails/' . $entry['id'] . '/' . $entry['file'])) {
		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_thumbnails/' . $entry['id'] . '/' . $entry['file']);

		$entry_thumbnail = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	} else {
		$entry_thumbnail = array();
	}

	//エントリーイメージ取得
	if ($entry['image']) {
		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_images/' . $entry['id'] . '/' . $entry['image']);

		$entry_image = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	} else {
		$entry_image = array();
	}

	//エントリーテキスト取得
	if ($entry['text']) {
		if (isset($entry_associate['option'])) {
			list($entry['text'], $entry_associate['option']) = freo_option($entry['text'], $entry_associate['option'], FREO_FILE_DIR . 'entry_options/' . $entry['id'] . '/');
		}
		list($excerpt, $more) = freo_divide($entry['text']);

		$entry_text = array(
			'excerpt' => $excerpt,
			'more'    => $more
		);
	} else {
		$entry_text = array();
	}

	//コメント取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE entry_id = :id ORDER BY id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$comments = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$comments[$data['id']] = $data;
	}

	//コメント保護データ取得
	$comment_securities = freo_security_comment('user', array_keys($comments));

	foreach ($comment_securities as $id => $security) {
		if (!$security) {
			continue;
		}

		$comments[$id]['user_id'] = null;
		$comments[$id]['name']    = ($comments[$id]['approved'] == 'no') ? $freo->config['comment']['approve_name'] : $freo->config['comment']['restriction_name'];
		$comments[$id]['mail']    = null;
		$comments[$id]['url']     = null;
		$comments[$id]['ip']      = null;
		$comments[$id]['text']    = ($comments[$id]['approved'] == 'no') ? $freo->config['comment']['approve_text'] : $freo->config['comment']['restriction_text'];
	}

	//トラックバック取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'trackbacks WHERE entry_id = :id ORDER BY id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$trackbacks = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$trackbacks[$data['id']] = $data;
	}

	//トラックバック保護データ取得
	$trackback_securities = freo_security_trackback('user', array_keys($trackbacks));

	foreach ($trackback_securities as $id => $security) {
		if (!$security) {
			continue;
		}

		$trackbacks[$id]['name']    = $freo->config['trackback']['approve_name'];
		$trackbacks[$id]['url']     = null;
		$trackbacks[$id]['ip']      = null;
		$trackbacks[$id]['title']   = null;
		$trackbacks[$id]['text']    = $freo->config['trackback']['approve_text'];
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                => freo_token('create'),
		'entry'                => $entry,
		'entry_associate'      => $entry_associate,
		'entry_filter'         => $entry_filter,
		'entry_security'       => $entry_security,
		'entry_tags'           => $entry_tags,
		'entry_file'           => $entry_file,
		'entry_thumbnail'      => $entry_thumbnail,
		'entry_image'          => $entry_image,
		'entry_text'           => $entry_text,
		'comments'             => $comments,
		'comment_securities'   => $comment_securities,
		'trackbacks'           => $trackbacks,
		'trackback_securities' => $trackback_securities,
		'input'                => array(
			'comment' => $comment
		)
	));

	return;
}

?>
