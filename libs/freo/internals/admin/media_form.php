<?php

/*********************************************************************

 freo | 管理画面 | メディア入力 (2012/12/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_media.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['path']) or !preg_match('/^[\w\-\/]+$/', $_GET['path'])) {
		$_GET['path'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check', (isset($_GET['type']) ? $_GET['type'] : null))) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		if ($_POST['media']['exec'] == 'restrict_directory') {
			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				$errors = freo_validate_media('restrict_directory', $_POST);

				if ($errors) {
					foreach ($errors as $error) {
						$freo->smarty->append('errors', $error);
					}
				}
			}
		} elseif ($_POST['media']['exec'] == 'insert_directory' or $_POST['media']['exec'] == 'rename_directory') {
			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				$errors = freo_validate_media('directory', $_POST);

				if ($errors) {
					foreach ($errors as $error) {
						$freo->smarty->append('errors', $error);
					}
				}
			}
		} elseif ($_POST['media']['exec'] == 'insert_file' or $_POST['media']['exec'] == 'rename_file') {
			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				$errors = freo_validate_media('file', $_POST);

				if ($errors) {
					foreach ($errors as $error) {
						$freo->smarty->append('errors', $error);
					}
				}
			}
		} elseif ($_POST['media']['exec'] == 'edit_memo') {
		} elseif ($_POST['media']['exec'] == 'edit_file') {
		} elseif ($_POST['media']['exec'] == 'edit_thumbnail') {
			//アップロードデータ初期化
			if (!isset($_FILES['media']['tmp_name']['thumbnail'])) {
				$_FILES['media']['tmp_name']['thumbnail'] = null;
			}

			//アップロードデータ取得
			if (is_uploaded_file($_FILES['media']['tmp_name']['thumbnail'])) {
				$_POST['media']['thumbnail'] = $_FILES['media']['name']['thumbnail'];
			}

			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				$errors = freo_validate_media('thumbnail', $_POST);

				if ($errors) {
					foreach ($errors as $error) {
						$freo->smarty->append('errors', $error);
					}
				}
			}

			//ファイルアップロード
			if (!$freo->smarty->get_template_vars('errors')) {
				if (is_uploaded_file($_FILES['media']['tmp_name']['thumbnail'])) {
					$temporary_dir = FREO_FILE_DIR . 'temporaries/media_thumbnails/';

					if (move_uploaded_file($_FILES['media']['tmp_name']['thumbnail'], $temporary_dir . $_FILES['media']['name']['thumbnail'])) {
						chmod($temporary_dir . $_FILES['media']['name']['thumbnail'], FREO_PERMISSION_FILE);
					} else {
						$freo->smarty->append('errors', 'ファイルをアップロードできません。');
					}
				}
			}
		} else {
			//アップロード項目数取得
			$file_count = count($_FILES['media']['tmp_name']['file']);

			//アップロードデータ初期化
			for ($i = 0; $i < $file_count; $i++) {
				if (!isset($_FILES['media']['tmp_name']['file'][$i])) {
					$_FILES['media']['tmp_name']['file'][$i] = null;
				}
			}

			//アップロードデータ取得
			for ($i = 0; $i < $file_count; $i++) {
				if (is_uploaded_file($_FILES['media']['tmp_name']['file'][$i])) {
					$_POST['media']['file'][$i] = $_FILES['media']['name']['file'][$i];
				} else {
					$_POST['media']['file'][$i] = null;
				}
			}

			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				$errors = freo_validate_media(!empty($_POST['media']['file_org']) ? 'update' : 'insert', $_POST);

				if ($errors) {
					foreach ($errors as $error) {
						$freo->smarty->append('errors', $error);
					}
				}
			}

			//ファイルアップロード
			for ($i = 0; $i < $file_count; $i++) {
				$file_flag = false;

				if (!$freo->smarty->get_template_vars('errors')) {
					if (is_uploaded_file($_FILES['media']['tmp_name']['file'][$i])) {
						$temporary_dir = FREO_FILE_DIR . 'temporaries/medias/';

						if (move_uploaded_file($_FILES['media']['tmp_name']['file'][$i], $temporary_dir . $_FILES['media']['name']['file'][$i])) {
							chmod($temporary_dir . $_FILES['media']['name']['file'][$i], FREO_PERMISSION_FILE);

							if (preg_match('/' . FREO_ASCII_FILE . '/i', $_FILES['media']['name']['file'][$i])) {
								$data = file_get_contents($temporary_dir . $_FILES['media']['name']['file'][$i]);

								if ($data) {
									if (file_put_contents($temporary_dir . $_FILES['media']['name']['file'][$i], freo_unify($data)) === false) {
										$freo->smarty->append('errors', 'ファイルの改行コードを変更できません。');
									}
								}
							}

							$file_flag = true;
						} else {
							$freo->smarty->append('errors', 'ファイルをアップロードできません。');
						}

						if ($file_flag) {
							if ($freo->config['media']['thumbnail']) {
								$thumbnail_width  = isset($_POST['media']['thumbnail_width'])  ? $_POST['media']['thumbnail_width']  : $freo->config['media']['thumbnail_width'];
								$thumbnail_height = isset($_POST['media']['thumbnail_height']) ? $_POST['media']['thumbnail_height'] : $freo->config['media']['thumbnail_height'];

								freo_resize($temporary_dir . $_FILES['media']['name']['file'][$i], FREO_FILE_DIR . 'temporaries/media_thumbnails/' . $_FILES['media']['name']['file'][$i], $thumbnail_width, $thumbnail_height);
							}
							if ($freo->config['media']['original']) {
								freo_resize($temporary_dir . $_FILES['media']['name']['file'][$i], $temporary_dir . $_FILES['media']['name']['file'][$i], $freo->config['media']['original_width'], $freo->config['media']['original_height']);
							}
						}
					}
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$media = $_POST['media'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('admin/media_post?freo%5Btoken%5D=' . freo_token('create', (isset($_GET['type']) ? $_GET['type'] : null)) . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
		}
	} else {
		//新規データ設定
		$media = array(
			'thumbnail_width'  => $freo->config['media']['thumbnail_width'],
			'thumbnail_height' => $freo->config['media']['thumbnail_height']
		);
	}

	//画像ファイルを確認
	if (isset($_GET['name']) and preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_GET['name'])) {
		if (file_exists(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $_GET['name'])) {
			$media['file_image'] = true;
		}
		if (file_exists(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'] . $_GET['name'])) {
			$media['thumbnail_image'] = true;
		}
	}

	//サムネイル画像登録フォーム
	if (isset($_GET['name']) and preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_GET['name'])) {
		$media['exec'] = 'edit_thumbnail';
	}

	//テキストファイルを読み込み
	if (isset($_GET['name']) and preg_match('/' . FREO_ASCII_FILE . '/i', $_GET['name'])) {
		$media['exec'] = 'edit_file';
		$media['text'] = file_get_contents(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $_GET['name']);

		if ($media['text'] === false) {
			freo_error('ファイル ' . FREO_FILE_DIR . 'medias/' . $_GET['path'] . $_GET['name'] . ' を読み込めません。');
		}
	}

	//ファイルの説明を読み込み
	if (isset($_GET['name']) and file_exists(FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $_GET['name'] . '.txt')) {
		$media['memo'] = file_get_contents(FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $_GET['name'] . '.txt');

		if ($media['memo'] === false) {
			freo_error('ファイル ' . FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $_GET['name'] . '.txt' . ' を読み込めません。');
		}
	}

	//閲覧制限を読み込み
	if (isset($_GET['directory']) and isset($_GET['name']) and file_exists(FREO_FILE_DIR . 'media_restrictions/' . $_GET['path'] . $_GET['name'] . '.txt')) {
		if ($fp = fopen(FREO_FILE_DIR . 'media_restrictions/' . $_GET['path'] . $_GET['name'] . '.txt', 'r')) {
			$restrictions = array();

			while ($line = fgets($fp)) {
				$values = explode("\t", trim($line));

				if ($values[0] == 'user') {
					$media['restriction'] = 'user';
				}
				if ($values[0] == 'group') {
					$media['restriction'] = 'group';

					if (!empty($values[1])) {
						foreach (explode(',', $values[1]) as $group) {
							$media['group'][$group] = true;
						}
					}
				}
				if ($values[0] == 'password') {
					$media['restriction'] = 'password';

					if (!empty($values[1])) {
						$media['password']    = $values[1] . "\n";
					}
				}
				if ($values[0] == 'filter') {
					if (!empty($values[1])) {
						foreach (explode(',', $values[1]) as $filter) {
							$media['filter'][$filter] = true;
						}
					}
				}
			}
			fclose($fp);
		} else {
			freo_error('ファイル ' . FREO_FILE_DIR . 'media_restrictions/' . $_GET['path'] . $_GET['name'] . '.txt' . ' を読み込めません。');
		}
	}

	//ディレクトリ取得
	$dirs = freo_main_get_dir(FREO_FILE_DIR . 'medias/');

	$directories = array();
	foreach ($dirs as $dir) {
		$directories[] = preg_replace('/^' . preg_quote(FREO_FILE_DIR . 'medias/', '/') . '/', '', $dir);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create', (isset($_GET['type']) ? $_GET['type'] : null)),
		'directories' => $directories,
		'input' => array(
			'media' => $media
		)
	));

	if (isset($_GET['type']) and $_GET['type'] == 'iframe') {
		//データ出力
		freo_output('internals/admin/iframe_media_form.html');
	}

	return;
}

/* ディレクトリ取得 */
function freo_main_get_dir($path)
{
	global $freo;

	$files = array();

	if (!file_exists($path)) {
		return $files;
	}

	if ($dir = scandir($path)) {
		natcasesort($dir);

		$tmp_directories = array();

		foreach ($dir as $data) {
			if ($data == '.' or $data == '..') {
				continue;
			}

			if (is_dir($path . $data)) {
				$tmp_directories[] = $data;
			}
		}

		$dir = $tmp_directories;
	}

	foreach ($dir as $data) {
		$files = array_merge($files, array($path . $data . '/'), freo_main_get_dir($path . $data . '/'));
	}

	return $files;
}

?>
