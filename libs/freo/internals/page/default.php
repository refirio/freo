<?php

/*********************************************************************

 freo | ページ表示 (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_comment.php';
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_comment.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[1])) {
		$parameters = array();
		$i          = 0;
		while (isset($freo->parameters[++$i])) {
			if (!$freo->parameters[$i]) {
				continue;
			}

			$parameters[] = $freo->parameters[$i];
		}
		$_GET['id'] = implode('/', $parameters);
	}
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	if ($_GET['id']) {
		//リクエストメソッドに応じた処理を実行
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			if (isset($_POST['page'])) {
				//パスワード認証
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND restriction = \'password\' AND password = :password');
				$stmt->bindValue(':id',       $_GET['id']);
				$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
				$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
				$stmt->bindValue(':password', $_POST['page']['password']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$_SESSION['security']['page'][$_GET['id']] = true;

					freo_redirect('page/' . $_GET['id']);
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
						freo_redirect('comment/preview?page_id=' . $_GET['id']);
					} else {
						//登録処理へ移動
						freo_redirect('comment/post?freo%5Btoken%5D=' . freo_token('create') . '&page_id=' . $_GET['id']);
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

		//ページ取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
		$stmt->bindValue(':id',   $_GET['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$page = $data;
		} else {
			freo_error('指定されたページが見つかりません。', '404 Not Found');
		}

		//ページ関連データ取得
		$page_associates = freo_associate_page('get', array($_GET['id']));
		$page_associate  = $page_associates[$_GET['id']];

		//ページフィルター取得
		$page_filters = freo_filter_page('user', array($_GET['id']));
		$page_filter  = $page_filters[$_GET['id']];

		if ($page_filter) {
			$page['comment']   = 'closed';
			$page['trackback'] = 'closed';
			$page['title']     = str_replace('[$title]', $page['title'], $freo->config['page']['filter_title']);
			$page['file']      = null;
			$page['image']     = null;
			$page['memo']      = null;
			$page['text']      = str_replace('[$text]', $page['text'], $freo->config['page']['filter_text']);

			if ($freo->config['page']['filter_option']) {
				$page_associate['option'] = array();
			}
		}

		//ページ保護データ取得
		$page_securities = freo_security_page('user', array($_GET['id']));
		$page_security   = $page_securities[$_GET['id']];

		if ($page_security) {
			$page['comment']   = 'closed';
			$page['trackback'] = 'closed';
			$page['title']     = str_replace('[$title]', $page['title'], $freo->config['page']['restriction_title']);
			$page['file']      = null;
			$page['image']     = null;
			$page['memo']      = null;
			$page['text']      = str_replace('[$text]', $page['text'], $freo->config['page']['restriction_text']);

			if ($freo->config['page']['restriction_option']) {
				$page_associate['option'] = array();
			}
		}

		//ページタグ取得
		if ($page['tag']) {
			$page_tags = explode(',', $page['tag']);
		} else {
			$page_tags = array();
		}

		//ページファイル取得
		if ($page['file']) {
			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_files/' . $page['id'] . '/' . $page['file']);

			$page_file = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		} else {
			$page_file = array();
		}

		//ページーサムネイル取得
		if ($page['file'] and file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $page['id'] . '/' . $page['file'])) {
			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_thumbnails/' . $page['id'] . '/' . $page['file']);

			$page_thumbnail = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		} else {
			$page_thumbnail = array();
		}

		//ページイメージ取得
		if ($page['image']) {
			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_images/' . $page['id'] . '/' . $page['image']);

			$page_image = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		} else {
			$page_image = array();
		}

		//ページテキスト取得
		if ($page['text']) {
			if (isset($page_associate['option'])) {
				list($page['text'], $page_associate['option']) = freo_option($page['text'], $page_associate['option'], FREO_FILE_DIR . 'page_options/' . $page['id'] . '/');
			}
			list($excerpt, $more) = freo_divide($page['text']);

			$page_text = array(
				'excerpt' => $excerpt,
				'more'    => $more
			);
		} else {
			$page_text = array();
		}

		//コメント取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE page_id = :id AND approved = \'yes\' ORDER BY id');
		$stmt->bindValue(':id', $_GET['id']);
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
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'trackbacks WHERE page_id = :id AND approved = \'yes\' ORDER BY id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$trackbacks = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$trackbacks[$data['id']] = $data;
		}

		//データ割当
		$freo->smarty->assign(array(
			'token'          => freo_token('create'),
			'page'           => $page,
			'page_associate' => $page_associate,
			'page_filter'    => $page_filter,
			'page_security'  => $page_security,
			'page_tags'      => $page_tags,
			'page_file'      => $page_file,
			'page_thumbnail' => $page_thumbnail,
			'page_image'     => $page_image,
			'page_text'      => $page_text,
			'comments'       => $comments,
			'trackbacks'     => $trackbacks,
			'input'          => array(
				'comment' => $comment
			)
		));
	} else {
		//検索条件設定
		$condition = null;
		if (isset($_GET['word'])) {
			$words = explode(' ', str_replace('　', ' ', $_GET['word']));

			foreach ($words as $word) {
				$condition .= ' AND (title LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ')';
			}
		}
		if (isset($_GET['user'])) {
			$condition .= ' AND user_id = ' . $freo->pdo->quote($_GET['user']);
		}
		if (isset($_GET['tag'])) {
			$condition .= ' AND (tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%') . ')';
		}
		if (isset($_GET['date'])) {
			if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
				if (FREO_DATABASE_TYPE == 'mysql') {
					$condition .= ' AND DATE_FORMAT(datetime, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
				} else {
					$condition .= ' AND STRFTIME(\'%Y\', datetime) = ' . $freo->pdo->quote($_GET['date']);
				}
			} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
				if (FREO_DATABASE_TYPE == 'mysql') {
					$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
				} else {
					$condition .= ' AND STRFTIME(\'%Y%m\', datetime) = ' . $freo->pdo->quote($_GET['date']);
				}
			} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
				if (FREO_DATABASE_TYPE == 'mysql') {
					$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
				} else {
					$condition .= ' AND STRFTIME(\'%Y%m%d\', datetime) = ' . $freo->pdo->quote($_GET['date']);
				}
			}
		}
		if ($condition == null) {
			$condition .= ' AND display = \'publish\'';
		}

		//制限されたページを一覧に表示しない
		if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
			$page_filters = freo_filter_page('user', array_keys($freo->refer['pages']));
			$page_filters = array_keys($page_filters, true);
			$page_filters = array_map(array($freo->pdo, 'quote'), $page_filters);
			if (!empty($page_filters)) {
				$condition .= ' AND id NOT IN(' . implode(',', $page_filters) . ')';
			}

			$page_securities = freo_security_page('user', array_keys($freo->refer['pages']), array('password'));
			$page_securities = array_keys($page_securities, true);
			$page_securities = array_map(array($freo->pdo, 'quote'), $page_securities);
			if (!empty($page_securities)) {
				$condition .= ' AND id NOT IN(' . implode(',', $page_securities) . ')';
			}
		}

		//ページ取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY pid, sort, id LIMIT :start, :limit');
		$stmt->bindValue(':now1',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':start', intval($freo->config['view']['page_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
		$stmt->bindValue(':limit', intval($freo->config['view']['page_limit']), PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$pages = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$pages[$data['id']] = $data;
		}

		//ページID取得
		$page_keys = array_keys($pages);

		//ページ関連データ取得
		$page_associates = freo_associate_page('get', $page_keys);

		//ページフィルター取得
		$page_filters = freo_filter_page('user', $page_keys);

		foreach ($page_filters as $id => $filter) {
			if (!$filter) {
				continue;
			}

			$pages[$id]['comment']   = 'closed';
			$pages[$id]['trackback'] = 'closed';
			$pages[$id]['title']     = str_replace('[$title]', $pages[$id]['title'], $freo->config['page']['filter_title']);
			$pages[$id]['file']      = null;
			$pages[$id]['image']     = null;
			$pages[$id]['memo']      = null;
			$pages[$id]['text']      = str_replace('[$text]', $pages[$id]['text'], $freo->config['page']['filter_text']);

			if ($freo->config['page']['filter_option']) {
				$page_associates[$id]['option'] = array();
			}
		}

		//ページ保護データ取得
		$page_securities = freo_security_page('user', $page_keys);

		foreach ($page_securities as $id => $security) {
			if (!$security) {
				continue;
			}

			$pages[$id]['comment']   = 'closed';
			$pages[$id]['trackback'] = 'closed';
			$pages[$id]['title']     = str_replace('[$title]', $pages[$id]['title'], $freo->config['page']['restriction_title']);
			$pages[$id]['file']      = null;
			$pages[$id]['image']     = null;
			$pages[$id]['memo']      = null;
			$pages[$id]['text']      = str_replace('[$text]', $pages[$id]['text'], $freo->config['page']['restriction_text']);

			if ($freo->config['page']['restriction_option']) {
				$page_associates[$id]['option'] = array();
			}
		}

		//ページタグ取得
		$page_tags = array();
		foreach ($page_keys as $page) {
			if (!$pages[$page]['tag']) {
				continue;
			}

			$page_tags[$page] = explode(',', $pages[$page]['tag']);
		}

		//ページファイル取得
		$page_files = array();
		foreach ($page_keys as $page) {
			if (!$pages[$page]['file']) {
				continue;
			}

			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_files/' . $page . '/' . $pages[$page]['file']);

			$page_files[$page] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		}

		//ページサムネイル取得
		$page_thumbnails = array();
		foreach ($page_keys as $page) {
			if (!$pages[$page]['file']) {
				continue;
			}
			if (!file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $page . '/' . $pages[$page]['file'])) {
				continue;
			}

			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_thumbnails/' . $page . '/' . $pages[$page]['file']);

			$page_thumbnails[$page] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		}

		//ページイメージ取得
		$page_images = array();
		foreach ($page_keys as $page) {
			if (!$pages[$page]['image']) {
				continue;
			}

			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_images/' . $page . '/' . $pages[$page]['image']);

			$page_images[$page] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		}

		//ページテキスト取得
		$page_texts = array();
		foreach ($page_keys as $page) {
			if (!$pages[$page]['text']) {
				continue;
			}

			if (isset($page_associates[$page]['option'])) {
				list($pages[$page]['text'], $page_associates[$page]['option']) = freo_option($pages[$page]['text'], $page_associates[$page]['option'], FREO_FILE_DIR . 'page_options/' . $page . '/');
			}
			list($excerpt, $more) = freo_divide($pages[$page]['text']);

			$page_texts[$page] = array(
				'excerpt' => $excerpt,
				'more'    => $more
			);
		}

		//ページ数取得
		$stmt = $freo->pdo->prepare('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY pid, sort, id');
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$data       = $stmt->fetch(PDO::FETCH_NUM);
		$page_count = $data[0];
		$page_page  = ceil($page_count / $freo->config['view']['page_limit']);

		//データ割当
		$freo->smarty->assign(array(
			'token'           => freo_token('create'),
			'pages'           => $pages,
			'page_associates' => $page_associates,
			'page_filters'    => $page_filters,
			'page_securities' => $page_securities,
			'page_tags'       => $page_tags,
			'page_files'      => $page_files,
			'page_thumbnails' => $page_thumbnails,
			'page_images'     => $page_images,
			'page_texts'      => $page_texts,
			'page_count'      => $page_count,
			'page_page'       => $page_page
		));
	}

	return;
}

?>
