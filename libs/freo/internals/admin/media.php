<?php

/*********************************************************************

 freo | 管理画面 | メディア管理 (2012/12/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//表示ディレクトリの記憶
	if (isset($_GET['type']) and $_GET['type'] == 'iframe') {
		if (isset($_GET['path'])) {
			$_SESSION['admin']['media']['path'] = $_GET['path'];
		}
		if (!isset($_GET['path']) and isset($_SESSION['admin']['media']['path'])) {
			$_GET['path'] = $_SESSION['admin']['media']['path'];
		}
	}

	//パラメータ検証
	if (!isset($_GET['path']) or !preg_match('/^[\w\-\/]+$/', $_GET['path'])) {
		$_GET['path'] = null;
	}

	//親ディレクトリ取得
	$path = $_GET['path'];

	if (preg_match('/(.+)\/$/', $path, $matches)) {
		$path = $matches[1];
	}
	$pos = strrpos($path, '/');

	if ($pos > 0) {
		$parent = substr($path, 0, $pos) . '/';
	} else {
		$parent = '';
	}

	//閲覧制限確認
	$restriction = false;

	$paths = explode('/', $path);

	while (!empty($paths)) {
		$path = implode('/', $paths);

		if (file_exists(FREO_FILE_DIR . 'media_restrictions/' . $path . '.txt')) {
			$restriction = true;

			break;
		}

		array_pop($paths);
	}

	//メディア取得
	$directories = array();
	$files       = array();

	if ($dir = scandir(FREO_FILE_DIR . 'medias/' . $_GET['path'])) {
		natcasesort($dir);

		foreach ($dir as $data) {
			if ($data == '.' or $data == '..') {
				continue;
			}

			if (is_dir(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $data)) {
				$directories[] = array(
					'name'        => $data,
					'datetime'    => date('Y-m-d H:i:s', filemtime(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $data)),
					'restriction' => (is_file(FREO_FILE_DIR . 'media_restrictions/' . $_GET['path'] . $data . '.txt') ? true : false)
				);
			} else {
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

				$files[] = array(
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
		'token'       => freo_token('create', (isset($_GET['type']) ? $_GET['type'] : null)),
		'parent'      => $parent,
		'restriction' => $restriction,
		'directories' => $directories,
		'files'       => $files
	));

	if (isset($_GET['type']) and $_GET['type'] == 'iframe') {
		//データ出力
		freo_output('internals/admin/iframe_media.html');
	}

	return;
}

?>
