<?php

/*********************************************************************

 freo | 管理画面 | エントリー入力 (2013/08/13)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = 0;
	}

	//権限確認
	if ($freo->user['authority'] != 'root' and $_GET['id']) {
		$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($freo->user['id'] != $data['user_id']) {
				freo_error('このエントリーを編集する権限がありません。');
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
		$_POST['entry']['approved'] = ($freo->user['authority'] == 'root') ? 'yes' : $freo->config['entry']['approve'];

		//日時データ取得
		if (is_array($_POST['entry']['datetime'])) {
			$year   = mb_convert_kana($_POST['entry']['datetime']['year'], 'n', 'UTF-8');
			$month  = mb_convert_kana($_POST['entry']['datetime']['month'], 'n', 'UTF-8');
			$day    = mb_convert_kana($_POST['entry']['datetime']['day'], 'n', 'UTF-8');
			$hour   = mb_convert_kana($_POST['entry']['datetime']['hour'], 'n', 'UTF-8');
			$minute = mb_convert_kana($_POST['entry']['datetime']['minute'], 'n', 'UTF-8');
			$second = mb_convert_kana($_POST['entry']['datetime']['second'], 'n', 'UTF-8');

			$_POST['entry']['datetime'] = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
		}
		if (!$_POST['entry']['close_set']) {
			$_POST['entry']['close'] = null;
		} elseif (is_array($_POST['entry']['close'])) {
			$year   = mb_convert_kana($_POST['entry']['close']['year'], 'n', 'UTF-8');
			$month  = mb_convert_kana($_POST['entry']['close']['month'], 'n', 'UTF-8');
			$day    = mb_convert_kana($_POST['entry']['close']['day'], 'n', 'UTF-8');
			$hour   = mb_convert_kana($_POST['entry']['close']['hour'], 'n', 'UTF-8');
			$minute = mb_convert_kana($_POST['entry']['close']['minute'], 'n', 'UTF-8');
			$second = mb_convert_kana($_POST['entry']['close']['second'], 'n', 'UTF-8');

			$_POST['entry']['close'] = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
		}

		//アップロードデータ初期化
		if (!isset($_FILES['entry']['tmp_name']['file'])) {
			$_FILES['entry']['tmp_name']['file'] = null;
		}
		if (!isset($_FILES['entry']['tmp_name']['image'])) {
			$_FILES['entry']['tmp_name']['image'] = null;
		}

		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'entry\') AND type = \'file\' ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($_FILES['entry_associate']['tmp_name']['option'][$data['id']])) {
				$_FILES['entry_associate']['tmp_name']['option'][$data['id']] = null;
			}
		}

		//アップロードデータ取得
		if (is_uploaded_file($_FILES['entry']['tmp_name']['file'])) {
			$_POST['entry']['file'] = $_FILES['entry']['name']['file'];
		} elseif (!isset($_POST['entry']['file'])) {
			$_POST['entry']['file'] = null;
		}
		if (is_uploaded_file($_FILES['entry']['tmp_name']['image'])) {
			$_POST['entry']['image'] = $_FILES['entry']['name']['image'];
		} elseif (!isset($_POST['entry']['image'])) {
			$_POST['entry']['image'] = null;
		}

		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'entry\') AND type = \'file\' ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (is_uploaded_file($_FILES['entry_associate']['tmp_name']['option'][$data['id']])) {
				$_POST['entry_associate']['option'][$data['id']] = $_FILES['entry_associate']['name']['option'][$data['id']];
			} elseif (!isset($_POST['entry_associate']['option'][$data['id']])) {
				$_POST['entry_associate']['option'][$data['id']] = null;
			}
		}

		//関連データ取得
		if (isset($_POST['entry_associate'])) {
			if (isset($_POST['entry_associate']['option'])) {
				foreach ($_POST['entry_associate']['option'] as $key => $value) {
					if (is_array($value)) {
						$_POST['entry_associate']['option'][$key] = implode("\n", $value);
					}
				}
			}
		} else {
			$_POST['entry_associate'] = array();
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_entry($_GET['id'] ? 'update' : 'insert', $_POST);

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
			if (is_uploaded_file($_FILES['entry']['tmp_name']['file'])) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/entry_files/';

				if (move_uploaded_file($_FILES['entry']['tmp_name']['file'], $temporary_dir . $_FILES['entry']['name']['file'])) {
					chmod($temporary_dir . $_FILES['entry']['name']['file'], FREO_PERMISSION_FILE);

					$file_flag = true;
				} else {
					$freo->smarty->append('errors', 'ファイルをアップロードできません。');
				}

				if ($file_flag) {
					if ($freo->config['entry']['thumbnail']) {
						freo_resize($temporary_dir . $_FILES['entry']['name']['file'], FREO_FILE_DIR . 'temporaries/entry_thumbnails/' . $_FILES['entry']['name']['file'], $freo->config['entry']['thumbnail_width'], $freo->config['entry']['thumbnail_height']);
					}
					if ($freo->config['entry']['original']) {
						freo_resize($temporary_dir . $_FILES['entry']['name']['file'], $temporary_dir . $_FILES['entry']['name']['file'], $freo->config['entry']['original_width'], $freo->config['entry']['original_height']);
					}
				}
			}
			if (is_uploaded_file($_FILES['entry']['tmp_name']['image'])) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/entry_images/';

				if (move_uploaded_file($_FILES['entry']['tmp_name']['image'], $temporary_dir . $_FILES['entry']['name']['image'])) {
					chmod($temporary_dir . $_FILES['entry']['name']['image'], FREO_PERMISSION_FILE);

					$image_flag = true;
				} else {
					$freo->smarty->append('errors', 'ファイルをアップロードできません。');
				}
			}

			$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'entry\') AND type = \'file\' ORDER BY sort, id');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}

			while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				if (is_uploaded_file($_FILES['entry_associate']['tmp_name']['option'][$data['id']])) {
					$temporary_dir = FREO_FILE_DIR . 'temporaries/entry_options/' . $data['id'] . '/';

					if (!freo_mkdir($temporary_dir, FREO_PERMISSION_DIR)) {
						freo_error('ディレクトリ ' . $temporary_dir . ' を作成できません。');
					}

					if (move_uploaded_file($_FILES['entry_associate']['tmp_name']['option'][$data['id']], $temporary_dir . $_FILES['entry_associate']['name']['option'][$data['id']])) {
						chmod($temporary_dir . $_FILES['entry_associate']['name']['option'][$data['id']], FREO_PERMISSION_FILE);

						$option_flags[$data['id']] = true;
					} else {
						$freo->smarty->append('errors', 'ファイルをアップロードできません。');
					}

					if (!empty($option_flags[$data['id']])) {
						if ($freo->config['option']['thumbnail']) {
							freo_resize($temporary_dir . $_FILES['entry_associate']['name']['option'][$data['id']], $temporary_dir . 'thumbnail_' . $_FILES['entry_associate']['name']['option'][$data['id']], $freo->config['option']['thumbnail_width'], $freo->config['option']['thumbnail_height']);
						}
						if ($freo->config['option']['original']) {
							freo_resize($temporary_dir . $_FILES['entry_associate']['name']['option'][$data['id']], $temporary_dir . $_FILES['entry_associate']['name']['option'][$data['id']], $freo->config['option']['original_width'], $freo->config['option']['original_height']);
						}
					}
				}
			}
		}

		if (is_uploaded_file($_FILES['entry']['tmp_name']['file']) and !$file_flag) {
			$_POST['entry']['file'] = null;
		}
		if (is_uploaded_file($_FILES['entry']['tmp_name']['image']) and !$image_flag) {
			$_POST['entry']['image'] = null;
		}

		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'entry\') AND type = \'file\' ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (is_uploaded_file($_FILES['entry_associate']['tmp_name']['option'][$data['id']]) and empty($option_flags[$data['id']])) {
				$_POST['entry_associate']['option'][$data['id']] = null;
			}
		}

		//プレビュー用データ
		$preview = $_POST;

		//エントリータグ取得
		if ($preview['entry']['tag']) {
			$preview['entry_tags'] = explode(',', $preview['entry']['tag']);
		} else {
			$preview['entry_tags'] = array();
		}

		//ファイルデータ取得
		if ($preview['entry']['file'] and empty($preview['entry']['file_remove'])) {
			if (file_exists(FREO_FILE_DIR . 'temporaries/entry_files/' . $preview['entry']['file'])) {
				$file = FREO_FILE_DIR . 'temporaries/entry_files/' . $preview['entry']['file'];
			} elseif (file_exists(FREO_FILE_DIR . 'entry_files/' . $preview['entry']['id'] . '/' . $preview['entry']['file'])) {
				$file = FREO_FILE_DIR . 'entry_files/' . $preview['entry']['id'] . '/' . $preview['entry']['file'];
			} else {
				$file = null;
			}
		} else {
			$file = null;
		}
		if ($file) {
			list($width, $height, $size) = freo_file($file);

			$preview['entry_file'] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
			$preview['entry_path']['file'] = $freo->core['http_url'] . $file;
		} else {
			$preview['entry_file']         = array();
			$preview['entry_path']['file'] = null;
		}

		if ($freo->config['entry']['thumbnail'] and $preview['entry']['file'] and empty($preview['entry']['file_remove'])) {
			if (file_exists(FREO_FILE_DIR . 'temporaries/entry_thumbnails/' . $preview['entry']['file'])) {
				$file = FREO_FILE_DIR . 'temporaries/entry_thumbnails/' . $preview['entry']['file'];
			} elseif (file_exists(FREO_FILE_DIR . 'entry_thumbnails/' . $preview['entry']['id'] . '/' . $preview['entry']['file'])) {
				$file = FREO_FILE_DIR . 'entry_thumbnails/' . $preview['entry']['id'] . '/' . $preview['entry']['file'];
			} else {
				$file = null;
			}
		} else {
			$file = null;
		}
		if ($file) {
			list($width, $height, $size) = freo_file($file);

			$preview['entry_thumbnail'] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
			$preview['entry_path']['thumbnail'] = $freo->core['http_url'] . $file;
		} else {
			$preview['entry_thumbnail']         = array();
			$preview['entry_path']['thumbnail'] = null;
		}

		if ($preview['entry']['image'] and empty($preview['entry']['image_remove'])) {
			if (file_exists(FREO_FILE_DIR . 'temporaries/entry_images/' . $preview['entry']['image'])) {
				$file = FREO_FILE_DIR . 'temporaries/entry_images/' . $preview['entry']['image'];
			} elseif (file_exists(FREO_FILE_DIR . 'entry_images/' . $preview['entry']['id'] . '/' . $preview['entry']['image'])) {
				$file = FREO_FILE_DIR . 'entry_images/' . $preview['entry']['id'] . '/' . $preview['entry']['image'];
			} else {
				$file = null;
			}
		} else {
			$file = null;
		}
		if ($file) {
			list($width, $height, $size) = freo_file($file);

			$preview['entry_image'] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
			$preview['entry_path']['image'] = $freo->core['http_url'] . $file;
		} else {
			$preview['entry_image']         = array();
			$preview['entry_path']['image'] = null;
		}

		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'entry\') AND type = \'file\' ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if ($preview['entry_associate']['option'][$data['id']] and empty($preview['entry_associate']['option_remove'][$data['id']])) {
				if (file_exists(FREO_FILE_DIR . 'temporaries/entry_options/' . $preview['entry_associate']['option'][$data['id']])) {
					$file = FREO_FILE_DIR . 'temporaries/entry_options/' . $preview['entry_associate']['option'][$data['id']];
				} elseif (file_exists(FREO_FILE_DIR . 'entry_options/' . $preview['entry']['id'] . '/' . $data['id'] . '/' . $preview['entry_associate']['option'][$data['id']])) {
					$file = FREO_FILE_DIR . 'entry_options/' . $preview['entry']['id'] . '/' . $data['id'] . '/' . $preview['entry_associate']['option'][$data['id']];
				} else {
					$file = null;
				}

				$preview['entry_path']['option'][$data['id']] = $file;
			}
		}

		//エントリーテキスト取得
		if ($preview['entry']['text']) {
			if (isset($preview['entry_associate']['option'])) {
				list($preview['entry']['text'], $preview['entry_associate']['option']) = freo_option($preview['entry']['text'], $preview['entry_associate']['option'], FREO_FILE_DIR . 'entry_options/' . $preview['entry']['id'] . '/');
			}
			list($excerpt, $more) = freo_divide($preview['entry']['text']);

			$preview['entry_text'] = array(
				'excerpt' => $excerpt,
				'more'    => $more
			);
		} else {
			$preview['entry_text'] = array();
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$entry           = $_POST['entry'];
			$entry_associate = $_POST['entry_associate'];
		} else {
			$_SESSION['input']   = $_POST;
			$_SESSION['preview'] = $preview;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('admin/entry_preview' . ($_GET['id'] ? '?id=' . $_GET['id'] : ''));
			} else {
				//登録処理へ移動
				freo_redirect('admin/entry_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$entry           = $_SESSION['input']['entry'];
			$entry_associate = $_SESSION['input']['entry_associate'];

			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$entry['user_id']  = $data['user_id'];
				$entry['created']  = $data['created'];
				$entry['modified'] = $data['modified'];
			}
		} elseif ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$entry = $data;
			} else {
				freo_error('指定されたエントリーが見つかりません。', '404 Not Found');
			}

			//関連データ取得
			$entry_associates = freo_associate_entry('get', array($_GET['id']));
			$entry_associate  = $entry_associates[$_GET['id']];
		} else {
			//一時ファイル削除
			foreach (array(FREO_FILE_DIR . 'temporaries/entry_files/', FREO_FILE_DIR . 'temporaries/entry_images/') as $temporary_dir) {
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

			//新規データ設定
			$entry = array(
				'status'    => $freo->config['entry']['status'],
				'display'   => $freo->config['entry']['display'],
				'comment'   => $freo->config['comment']['accept_entry'],
				'trackback' => $freo->config['trackback']['accept_entry'],
				'datetime'  => date('Y-m-d H:i:s')
			);
			$entry_associate = array();
		}
	}

	//オプション取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE target IS NULL OR target = \'entry\' ORDER BY sort, id');
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
		'options'      => $options,
		'option_texts' => $option_texts,
		'input'        => array(
			'entry'           => $entry,
			'entry_associate' => $entry_associate
		)
	));

	return;
}

?>
