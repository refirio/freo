<?php

/*********************************************************************

 freo | 管理画面 | ページ入力 (2013/08/13)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}
	if (!isset($_GET['pid']) or !preg_match('/^[\w\-\/]+$/', $_GET['pid'])) {
		$_GET['pid'] = null;
	}

	//権限確認
	if ($freo->user['authority'] != 'root' and $_GET['id']) {
		$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($freo->user['id'] != $data['user_id']) {
				freo_error('このページを編集する権限がありません。');
			}
		}
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//承認データ取得
		$_POST['page']['approved'] = ($freo->user['authority'] == 'root') ? 'yes' : $freo->config['page']['approve'];

		//日時データ取得
		if (is_array($_POST['page']['datetime'])) {
			$year   = mb_convert_kana($_POST['page']['datetime']['year'], 'n', 'UTF-8');
			$month  = mb_convert_kana($_POST['page']['datetime']['month'], 'n', 'UTF-8');
			$day    = mb_convert_kana($_POST['page']['datetime']['day'], 'n', 'UTF-8');
			$hour   = mb_convert_kana($_POST['page']['datetime']['hour'], 'n', 'UTF-8');
			$minute = mb_convert_kana($_POST['page']['datetime']['minute'], 'n', 'UTF-8');
			$second = mb_convert_kana($_POST['page']['datetime']['second'], 'n', 'UTF-8');

			$_POST['page']['datetime'] = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
		}
		if (!$_POST['page']['close_set']) {
			$_POST['page']['close'] = null;
		} elseif (is_array($_POST['page']['close'])) {
			$year   = mb_convert_kana($_POST['page']['close']['year'], 'n', 'UTF-8');
			$month  = mb_convert_kana($_POST['page']['close']['month'], 'n', 'UTF-8');
			$day    = mb_convert_kana($_POST['page']['close']['day'], 'n', 'UTF-8');
			$hour   = mb_convert_kana($_POST['page']['close']['hour'], 'n', 'UTF-8');
			$minute = mb_convert_kana($_POST['page']['close']['minute'], 'n', 'UTF-8');
			$second = mb_convert_kana($_POST['page']['close']['second'], 'n', 'UTF-8');

			$_POST['page']['close'] = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
		}

		//並び順取得
		if ($_POST['page']['sort'] != '') {
			$_POST['page']['sort'] = mb_convert_kana($_POST['page']['sort'], 'n', 'UTF-8');
		}

		//アップロードデータ初期化
		if (!isset($_FILES['page']['tmp_name']['file'])) {
			$_FILES['page']['tmp_name']['file'] = null;
		}
		if (!isset($_FILES['page']['tmp_name']['image'])) {
			$_FILES['page']['tmp_name']['image'] = null;
		}

		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'page\') AND type = \'file\' ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($_FILES['page_associate']['tmp_name']['option'][$data['id']])) {
				$_FILES['page_associate']['tmp_name']['option'][$data['id']] = null;
			}
		}

		//アップロードデータ取得
		if (is_uploaded_file($_FILES['page']['tmp_name']['file'])) {
			$_POST['page']['file'] = $_FILES['page']['name']['file'];
		} elseif (!isset($_POST['page']['file'])) {
			$_POST['page']['file'] = null;
		}
		if (is_uploaded_file($_FILES['page']['tmp_name']['image'])) {
			$_POST['page']['image'] = $_FILES['page']['name']['image'];
		} elseif (!isset($_POST['page']['image'])) {
			$_POST['page']['image'] = null;
		}

		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'page\') AND type = \'file\' ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (is_uploaded_file($_FILES['page_associate']['tmp_name']['option'][$data['id']])) {
				$_POST['page_associate']['option'][$data['id']] = $_FILES['page_associate']['name']['option'][$data['id']];
			} elseif (!isset($_POST['page_associate']['option'][$data['id']])) {
				$_POST['page_associate']['option'][$data['id']] = null;
			}
		}

		//関連データ取得
		if (isset($_POST['page_associate'])) {
			if (isset($_POST['page_associate']['option'])) {
				foreach ($_POST['page_associate']['option'] as $key => $value) {
					if (is_array($value)) {
						$_POST['page_associate']['option'][$key] = implode("\n", $value);
					}
				}
			}
		} else {
			$_POST['page_associate'] = array();
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_page($_GET['id'] ? 'update' : 'insert', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//ファイルアップロード
		$file_flag    = false;
		$image_flag   = false;
		$option_flags = array();

		if (!$freo->smarty->get_template_vars('errors')) {
			if (is_uploaded_file($_FILES['page']['tmp_name']['file'])) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/page_files/';

				if (move_uploaded_file($_FILES['page']['tmp_name']['file'], $temporary_dir . $_FILES['page']['name']['file'])) {
					chmod($temporary_dir . $_FILES['page']['name']['file'], FREO_PERMISSION_FILE);

					$file_flag = true;
				} else {
					$freo->smarty->append('errors', 'ファイルをアップロードできません。');
				}

				if ($file_flag) {
					if ($freo->config['page']['thumbnail']) {
						freo_resize($temporary_dir . $_FILES['page']['name']['file'], FREO_FILE_DIR . 'temporaries/page_thumbnails/' . $_FILES['page']['name']['file'], $freo->config['page']['thumbnail_width'], $freo->config['page']['thumbnail_height']);
					}
					if ($freo->config['page']['original']) {
						freo_resize($temporary_dir . $_FILES['page']['name']['file'], $temporary_dir . $_FILES['page']['name']['file'], $freo->config['page']['original_width'], $freo->config['page']['original_height']);
					}
				}
			}
			if (is_uploaded_file($_FILES['page']['tmp_name']['image'])) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/page_images/';

				if (move_uploaded_file($_FILES['page']['tmp_name']['image'], $temporary_dir . $_FILES['page']['name']['image'])) {
					chmod($temporary_dir . $_FILES['page']['name']['image'], FREO_PERMISSION_FILE);

					$image_flag = true;
				} else {
					$freo->smarty->append('errors', 'ファイルをアップロードできません。');
				}
			}

			$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'page\') AND type = \'file\' ORDER BY sort, id');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}

			while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				if (is_uploaded_file($_FILES['page_associate']['tmp_name']['option'][$data['id']])) {
					$temporary_dir = FREO_FILE_DIR . 'temporaries/page_options/' . $data['id'] . '/';

					if (!freo_mkdir($temporary_dir, FREO_PERMISSION_DIR)) {
						freo_error('ディレクトリ ' . $temporary_dir . ' を作成できません。');
					}

					if (move_uploaded_file($_FILES['page_associate']['tmp_name']['option'][$data['id']], $temporary_dir . $_FILES['page_associate']['name']['option'][$data['id']])) {
						chmod($temporary_dir . $_FILES['page_associate']['name']['option'][$data['id']], FREO_PERMISSION_FILE);

						$option_flags[$data['id']] = true;
					} else {
						$freo->smarty->append('errors', 'ファイルをアップロードできません。');
					}

					if (!empty($option_flags[$data['id']])) {
						if ($freo->config['option']['thumbnail']) {
							freo_resize($temporary_dir . $_FILES['page_associate']['name']['option'][$data['id']], $temporary_dir . 'thumbnail_' . $_FILES['page_associate']['name']['option'][$data['id']], $freo->config['option']['thumbnail_width'], $freo->config['option']['thumbnail_height']);
						}
						if ($freo->config['option']['original']) {
							freo_resize($temporary_dir . $_FILES['page_associate']['name']['option'][$data['id']], $temporary_dir . $_FILES['page_associate']['name']['option'][$data['id']], $freo->config['option']['original_width'], $freo->config['option']['original_height']);
						}
					}
				}
			}
		}

		if (is_uploaded_file($_FILES['page']['tmp_name']['file']) and !$file_flag) {
			$_POST['page']['file'] = null;
		}
		if (is_uploaded_file($_FILES['page']['tmp_name']['image']) and !$image_flag) {
			$_POST['page']['image'] = null;
		}

		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'page\') AND type = \'file\' ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (is_uploaded_file($_FILES['page_associate']['tmp_name']['option'][$data['id']]) and empty($option_flags[$data['id']])) {
				$_POST['page_associate']['option'][$data['id']] = null;
			}
		}

		//プレビュー用データ
		$preview = $_POST;

		//ページタグ取得
		if ($preview['page']['tag']) {
			$preview['page_tags'] = explode(',', $preview['page']['tag']);
		} else {
			$preview['page_tags'] = array();
		}

		//ファイルデータ取得
		if ($preview['page']['file'] and empty($preview['page']['file_remove'])) {
			if (file_exists(FREO_FILE_DIR . 'temporaries/page_files/' . $preview['page']['file'])) {
				$file = FREO_FILE_DIR . 'temporaries/page_files/' . $preview['page']['file'];
			} elseif (file_exists(FREO_FILE_DIR . 'page_files/' . $preview['page']['id'] . '/' . $preview['page']['file'])) {
				$file = FREO_FILE_DIR . 'page_files/' . $preview['page']['id'] . '/' . $preview['page']['file'];
			} else {
				$file = null;
			}
		} else {
			$file = null;
		}
		if ($file) {
			list($width, $height, $size) = freo_file($file);

			$preview['page_file'] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
			$preview['page_path']['file'] = $freo->core['http_url'] . $file;
		} else {
			$preview['page_file']         = array();
			$preview['page_path']['file'] = null;
		}

		if ($freo->config['page']['thumbnail'] and $preview['page']['file'] and empty($preview['page']['file_remove'])) {
			if (file_exists(FREO_FILE_DIR . 'temporaries/page_thumbnails/' . $preview['page']['file'])) {
				$file = FREO_FILE_DIR . 'temporaries/page_thumbnails/' . $preview['page']['file'];
			} elseif (file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $preview['page']['id'] . '/' . $preview['page']['file'])) {
				$file = FREO_FILE_DIR . 'page_thumbnails/' . $preview['page']['id'] . '/' . $preview['page']['file'];
			} else {
				$file = null;
			}
		} else {
			$file = null;
		}
		if ($file) {
			list($width, $height, $size) = freo_file($file);

			$preview['page_thumbnail'] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
			$preview['page_path']['thumbnail'] = $freo->core['http_url'] . $file;
		} else {
			$preview['page_thumbnail']         = array();
			$preview['page_path']['thumbnail'] = null;
		}

		if ($preview['page']['image'] and empty($preview['page']['image_remove'])) {
			if (file_exists(FREO_FILE_DIR . 'temporaries/page_images/' . $preview['page']['image'])) {
				$file = FREO_FILE_DIR . 'temporaries/page_images/' . $preview['page']['image'];
			} elseif (file_exists(FREO_FILE_DIR . 'page_images/' . $preview['page']['id'] . '/' . $preview['page']['image'])) {
				$file = FREO_FILE_DIR . 'page_images/' . $preview['page']['id'] . '/' . $preview['page']['image'];
			} else {
				$file = null;
			}
		} else {
			$file = null;
		}
		if ($file) {
			list($width, $height, $size) = freo_file($file);

			$preview['page_image'] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
			$preview['page_path']['image'] = $freo->core['http_url'] . $file;
		} else {
			$preview['page_image']         = array();
			$preview['page_path']['image'] = null;
		}

		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'page\') AND type = \'file\' ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($preview['page_associate']['option'][$data['id']] and empty($preview['page_associate']['option_remove'][$data['id']])) {
				if (file_exists(FREO_FILE_DIR . 'temporaries/page_options/' . $preview['page_associate']['option'][$data['id']])) {
					$file = FREO_FILE_DIR . 'temporaries/page_options/' . $preview['page_associate']['option'][$data['id']];
				} elseif (file_exists(FREO_FILE_DIR . 'page_options/' . $preview['page']['id'] . '/' . $data['id'] . '/' . $preview['page_associate']['option'][$data['id']])) {
					$file = FREO_FILE_DIR . 'page_options/' . $preview['page']['id'] . '/' . $data['id'] . '/' . $preview['page_associate']['option'][$data['id']];
				} else {
					$file = null;
				}

				$preview['page_path']['option'][$data['id']] = $file;
			}
		}

		//ページテキスト取得
		if ($preview['page']['text']) {
			if (isset($preview['page_associate']['option'])) {
				list($preview['page']['text'], $preview['page_associate']['option']) = freo_option($preview['page']['text'], $preview['page_associate']['option'], FREO_FILE_DIR . 'page_options/' . $preview['page']['id'] . '/');
			}
			list($excerpt, $more) = freo_divide($preview['page']['text']);

			$preview['page_text'] = array(
				'excerpt' => $excerpt,
				'more'    => $more
			);
		} else {
			$preview['page_text'] = array();
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$page           = $_POST['page'];
			$page_associate = $_POST['page_associate'];
		} else {
			$_SESSION['input']   = $_POST;
			$_SESSION['preview'] = $preview;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('admin/page_preview' . ($_GET['id'] ? '?id=' . $_GET['id'] : ''));
			} else {
				//登録処理へ移動
				freo_redirect('admin/page_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$page           = $_SESSION['input']['page'];
			$page_associate = $_SESSION['input']['page_associate'];

			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$page['user_id']  = $data['user_id'];
				$page['created']  = $data['created'];
				$page['modified'] = $data['modified'];
			}
		} elseif ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$page = $data;
			} else {
				freo_error('指定されたページが見つかりません。', '404 Not Found');
			}

			//関連データ取得
			$page_associates = freo_associate_page('get', array($_GET['id']));
			$page_associate  = $page_associates[$_GET['id']];
		} else {
			//一時ファイル削除
			foreach (array(FREO_FILE_DIR . 'temporaries/page_files/', FREO_FILE_DIR . 'temporaries/page_images/') as $temporary_dir) {
				if ($dir = scandir($temporary_dir)) {
					foreach ($dir as $data) {
						if (is_file($temporary_dir . $data)) {
							unlink($temporary_dir . $data);
						}
					}
				} else {
					$freo->smarty->append('errors', '一時ファイルを削除できません。');
				}
			}

			//並び順初期値取得
			if (isset($_GET['pid'])) {
				$stmt = $freo->pdo->prepare('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :pid');
				$stmt->bindValue(':pid', $_GET['pid']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}
			} else {
				$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid IS NULL');
				if (!$stmt) {
					freo_error($freo->pdo->errorInfo());
				}
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$page = array(
				'pid'       => $_GET['pid'],
				'status'    => $freo->config['page']['status'],
				'display'   => $freo->config['page']['display'],
				'comment'   => $freo->config['comment']['accept_page'],
				'trackback' => $freo->config['trackback']['accept_page'],
				'datetime'  => date('Y-m-d H:i:s'),
				'sort'      => $sort
			);
			$page_associate = array();
		}
	}

	//ページID取得
	$stmt = $freo->pdo->query('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'pages ORDER BY id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$pages = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$pages[$data['id']] = $data;
	}

	//オプション取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE target IS NULL OR target = \'page\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$options = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$options[$data['id']] = $data;
	}

	//オプションID取得
	$option_keys = array_keys($options);

	//オプション初期値取得
	$option_texts = array();
	foreach ($option_keys as $option) {
		if (!$options[$option]['text']) {
			continue;
		}

		foreach (explode("\n", $options[$option]['text']) as $text) {
			if ($text == '') {
				continue;
			}

			$option_texts[$option][] = $text;
		}
	}
	//データ割当
	$freo->smarty->assign(array(
		'token'        => freo_token('create'),
		'pages'        => $pages,
		'options'      => $options,
		'option_texts' => $option_texts,
		'input'        => array(
			'page'           => $page,
			'page_associate' => $page_associate
		)
	));

	return;
}

?>
