<?php

/*********************************************************************

 freo | ファイル表示 (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_media.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_media.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_media.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['mode']) and isset($freo->parameters[1])) {
		$_GET['mode'] = $freo->parameters[1];
	}
	if (!isset($_GET['mode']) or !preg_match('/^[\w\-]+$/', $_GET['mode'])) {
		freo_error('表示モードを指定してください。');
	}

	if (!isset($_GET['width']) or !preg_match('/^\d+$/', $_GET['width']) or $_GET['width'] < 1) {
		$_GET['width'] = null;
	}
	if (!isset($_GET['height']) or !preg_match('/^\d+$/', $_GET['height']) or $_GET['height'] < 1) {
		$_GET['height'] = null;
	}

	if ($_GET['mode'] == 'page') {
		if (!isset($_GET['id']) and isset($freo->parameters[2])) {
			$parameters = array();
			$i          = 1;
			while (isset($freo->parameters[++$i])) {
				if (!$freo->parameters[$i]) {
					continue;
				}

				$parameters[] = $freo->parameters[$i];
			}
			$_GET['id'] = implode('/', $parameters);

			if (preg_match('/(.+)\.\w+$/', $_GET['id'], $matches)) {
				$_GET['id'] = $matches[1];
			}
		}
		if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
			freo_error('表示したいページを指定してください。');
		}
	} elseif ($_GET['mode'] == 'view') {
		if (!isset($_GET['id']) and isset($freo->parameters[2])) {
			$_GET['id'] = $freo->parameters[2];

			if (preg_match('/(.+)\.\w+$/', $_GET['id'], $matches)) {
				$_GET['id'] = $matches[1];
			}
		}
		if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
			$_GET['id'] = null;

			if (!isset($_GET['code']) and isset($freo->parameters[2])) {
				$_GET['code'] = $freo->parameters[2];

				if (preg_match('/(.+)\.\w+$/', $_GET['code'], $matches)) {
					$_GET['code'] = $matches[1];
				}
			}

			if (isset($_GET['code'])) {
				$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE code = :code');
				$stmt->bindValue(':code',  $_GET['code']);
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
	} elseif ($_GET['mode'] == 'media') {
		if (!isset($_GET['path']) and isset($freo->parameters[2])) {
			$parameters = array();
			$i          = 1;
			while (isset($freo->parameters[++$i])) {
				$parameters[] = $freo->parameters[$i];
			}
			$_GET['path'] = implode('/', $parameters);
		}

		if (!empty($_GET['path']) and !preg_match('/^[\w\-\/\.]+$/', $_GET['path'])) {
			freo_error('表示したいファイルを指定してください。');
		}
	} else {
		freo_error('不正なアクセスです。');
	}

	//パスワード認証
	if (isset($_GET['view']) and $_GET['view'] == 'password') {
		if ($_GET['mode'] == 'page') {
			freo_redirect('page/' . $_GET['id']);
		} elseif ($_GET['mode'] == 'view') {
			freo_redirect('view/' . $_GET['id']);
		} else {
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				//閲覧制限を読み込み
				$paths = explode('/', $_GET['path']);

				while (!empty($paths)) {
					array_pop($paths);

					$path = implode('/', $paths);

					if (file_exists(FREO_FILE_DIR . 'media_restrictions/' . $path . '.txt')) {
						if ($fp = fopen(FREO_FILE_DIR . 'media_restrictions/' . $path . '.txt', 'r')) {
							while ($line = fgets($fp)) {
								$values = explode("\t", trim($line));

								//パスワードで認証
								if ($values[0] == 'password') {
									if (!empty($values[1]) and isset($_POST['media']['password']) and $values[1] == $_POST['media']['password']) {
										$_SESSION['security']['media'][$path . '/'] = true;

										freo_redirect('file/media/' . $_GET['path']);
									} else {
										freo_error('パスワードが違います。');
									}
								}

							}
							fclose($fp);
						} else {
							freo_error('ファイル ' . FREO_FILE_DIR . 'media_restrictions/' . $path . '.txt' . ' を読み込めません。');
						}

						break;
					}
				}
			}

			//データ割当
			$freo->smarty->assign(array(
				'token' => freo_token('create'),
				'media' => $_GET['path']
			));

			//データ出力
			freo_output('internals/file/password.html');

			return;
		}
	}

	//ファイル取得
	if ($_GET['mode'] == 'page') {
		//ページ取得
		if (isset($_GET['type']) and $_GET['type'] == 'image') {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND image IS NOT NULL');
		} else {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND file IS NOT NULL');
		}
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
			freo_error('指定されたファイルが見つかりません。', '404 Not Found');
		}

		//ページフィルター取得
		$page_filters = freo_filter_page('user', array($_GET['id']));
		$page_filter  = $page_filters[$_GET['id']];

		if ($page_filter) {
			freo_error(str_replace('[$title]', $page['title'], $freo->config['page']['filter_title']));
		}

		//ページ保護データ取得
		$page_securities = freo_security_page('user', array($_GET['id']));
		$page_security   = $page_securities[$_GET['id']];

		if ($page_security) {
			freo_error(str_replace('[$title]', $page['title'], $freo->config['page']['restriction_title']));
		}

		//ファイル決定
		if (isset($_GET['type']) and $_GET['type'] == 'image') {
			$filename = FREO_FILE_DIR . 'page_images/' . $page['id'] . '/' . $page['image'];
		} elseif ($freo->config['page']['thumbnail'] and file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $page['id'] . '/' . $page['file']) and isset($_GET['type']) and $_GET['type'] == 'thumbnail') {
			$filename = FREO_FILE_DIR . 'page_thumbnails/' . $page['id'] . '/' . $page['file'];
		} else {
			$filename = FREO_FILE_DIR . 'page_files/' . $page['id'] . '/' . $page['file'];
		}
	} elseif ($_GET['mode'] == 'view') {
		//エントリー取得
		if (isset($_GET['type']) and $_GET['type'] == 'image') {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND image IS NOT NULL');
		} else {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND file IS NOT NULL');
		}
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
			freo_error('指定されたファイルが見つかりません。', '404 Not Found');
		}

		//エントリーフィルター取得
		$entry_filters = freo_filter_entry('user', array($_GET['id']));
		$entry_filter  = $entry_filters[$_GET['id']];

		if ($entry_filter) {
			freo_error(str_replace('[$title]', $entry['title'], $freo->config['entry']['filter_title']));
		}

		//エントリー保護データ取得
		$entry_securities = freo_security_entry('user', array($_GET['id']));
		$entry_security   = $entry_securities[$_GET['id']];

		if ($entry_security) {
			freo_error(str_replace('[$title]', $entry['title'], $freo->config['entry']['restriction_title']));
		}

		//ファイル決定
		if (isset($_GET['type']) and $_GET['type'] == 'image') {
			$filename = FREO_FILE_DIR . 'entry_images/' . $entry['id'] . '/' . $entry['image'];
		} elseif ($freo->config['entry']['thumbnail'] and file_exists(FREO_FILE_DIR . 'entry_thumbnails/' . $entry['id'] . '/' . $entry['file']) and isset($_GET['type']) and $_GET['type'] == 'thumbnail') {
			$filename = FREO_FILE_DIR . 'entry_thumbnails/' . $entry['id'] . '/' . $entry['file'];
		} else {
			$filename = FREO_FILE_DIR . 'entry_files/' . $entry['id'] . '/' . $entry['file'];
		}
	} elseif ($_GET['mode'] == 'media') {
		//メディア取得
		if (!file_exists(FREO_FILE_DIR . 'medias/' . $_GET['path'])) {
			freo_error('指定されたメディアが見つかりません。', '404 Not Found');
		}

		//メディアフィルター取得
		$media_filters = freo_filter_media('user', array($_GET['path']));
		$media_filter  = $media_filters[$_GET['path']];

		if ($media_filter) {
			$media_associates = freo_associate_media('get', array($_GET['path']));
			$media_associate  = $media_associates[$_GET['path']];

			$freo->smarty->assign(array(
				'token'           => freo_token('create'),
				'message'         => $freo->config['media']['filter_message'],
				'media_associate' => $media_associate
			));

			freo_output('internals/file/error.html');

			return;
		}

		//ページ保護データ取得
		$media_securities = freo_security_media('user', array($_GET['path']));
		$media_security   = $media_securities[$_GET['path']];

		if ($media_security) {
			$media_associates = freo_associate_media('get', array($_GET['path']));
			$media_associate  = $media_associates[$_GET['path']];

			$freo->smarty->assign(array(
				'token'           => freo_token('create'),
				'message'         => $freo->config['media']['restriction_message'],
				'media_associate' => $media_associate
			));

			freo_output('internals/file/error.html');

			return;
		}

		//ディレクトリ表示
		if (is_dir(FREO_FILE_DIR . 'medias/' . $_GET['path'])) {
			//閲覧制限の有無を確認
			$restrict_flag = false;

			$media_filters = freo_filter_media('nobody', array($_GET['path']));
			$media_filter  = $media_filters[$_GET['path']];

			if ($media_filter) {
				$restrict_flag = true;
			}

			$media_securities = freo_security_media('nobody', array($_GET['path']));
			$media_security   = $media_securities[$_GET['path']];

			if ($media_security) {
				$restrict_flag = true;
			}

			//メディア取得
			$medias = array();

			if ($dir = scandir(FREO_FILE_DIR . 'medias/' . $_GET['path'])) {
				natcasesort($dir);

				foreach ($dir as $data) {
					if ($data == '.' or $data == '..' or preg_match('/^\./', $data)) {
						continue;
					}

					if (is_file(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $data)) {
						list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $data);

						if (is_file(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'] . $data)) {
							list($thumbnail_width, $thumbnail_height, $thumbnail_size) = freo_file(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'] . $data);

							$thumbnail = array(
								'name'     => $data,
								'datetime' => date('Y-m-d H:i:s', filemtime(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'] . $data)),
								'width'    => $thumbnail_width,
								'height'   => $thumbnail_height,
								'size'     => $thumbnail_size,
							);
						} else {
							$thumbnail = array();
						}

						if (is_file(FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $data . '.txt')) {
							$memo = file_get_contents(FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $data . '.txt');

							if ($memo === false) {
								freo_error('ファイル ' . FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $data . '.txt' . ' 読み込めません。');
							}
						} else {
							$memo = null;
						}

						$medias[] = array(
							'name'      => $data,
							'datetime'  => date('Y-m-d H:i:s', filemtime(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $data)),
							'width'     => $width,
							'height'    => $height,
							'size'      => $size,
							'thumbnail' => $thumbnail,
							'memo'      => $memo
						);
					}
				}
			} else {
				freo_error('メディア格納ディレクトリ ' . FREO_FILE_DIR . 'medias/' . $_GET['path'] . ' を開けません。');
			}

			//データ割当
			$freo->smarty->assign(array(
				'token'       => freo_token('create'),
				'medias'      => $medias,
				'restriction' => $restrict_flag
			));

			//データ出力
			freo_output('internals/file/default.html');

			return;
		}

		//ファイル決定
		if (file_exists(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path']) and isset($_GET['type']) and $_GET['type'] == 'thumbnail') {
			$filename = FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'];
		} elseif (file_exists(FREO_FILE_DIR . 'medias/' . $_GET['path'])) {
			$filename = FREO_FILE_DIR . 'medias/' . $_GET['path'];
		} else {
			$filename = null;
		}
	}

	//変換チェック
	if (($freo->agent['type'] == 'mobile' and preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $filename) and (($_GET['mode'] == 'page' and $freo->config['page']['thumbnail']) or ($_GET['mode'] == 'view' and $freo->config['entry']['thumbnail']))) or ($_GET['width'] and $_GET['height'])) {
		$flag = true;
	} else {
		$flag = false;
	}

	//出力ファイル名決定
	$output = $filename;

	if ($flag) {
		if ($freo->agent['career'] == 'docomo' and preg_match('/\.png$/i', $filename)) {
			$output = basename($filename) . '.gif';
		}
	}

	//データ出力
	header('Content-Type: ' . freo_mime($output));
/*
	if ($freo->agent['type'] == 'mobile') {
		header('Pragma: no-cache');
		header('Cache-Control: no-cache');
	}
*/
	if ($flag) {
		if ($_GET['width'] and $_GET['height']) {
			$max_width  = $_GET['width'];
			$max_height = $_GET['height'];
		} else {
			$max_width  = FREO_MOBILE_FILE_WIDTH;
			$max_height = FREO_MOBILE_FILE_HEIGHT;
		}

		freo_resize($filename, $output, $max_width, $max_height, true);
	} else {
		readfile($filename);
	}

	return;
}

?>
