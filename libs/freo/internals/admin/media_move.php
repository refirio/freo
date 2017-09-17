<?php

/*********************************************************************

 freo | 管理画面 | メディア移動 (2012/12/11)

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

	//パラメータ検証
	if (!isset($_GET['name']) or !preg_match('/^[\w\-\.]+$/', $_GET['name'])) {
		$_GET['name'] = null;
	}
	if (!isset($_GET['path']) or !preg_match('/^[\w\-\/]+$/', $_GET['path'])) {
		$_GET['path'] = null;
	}

	//ワンタイムトークン確認
	if (!freo_token('check', (isset($_GET['type']) ? $_GET['type'] : null))) {
		freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
	}

	if (empty($_GET['directory'])) {
		//ファイル確認
		if (file_exists(FREO_FILE_DIR . 'medias/' . $_POST['media']['path'] . $_POST['media']['file'])) {
			freo_error('ファイル ' . FREO_FILE_DIR . 'medias/' . $_POST['media']['path'] . $_POST['media']['file'] . ' はすでに存在します。');
		}

		//ファイル移動
		if (!rename(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $_GET['name'], FREO_FILE_DIR . 'medias/' . $_POST['media']['path'] . $_POST['media']['file'])) {
			freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
		}

		//サムネイル移動
		if (file_exists(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'] . $_GET['name'])) {
			if (!freo_mkdir(FREO_FILE_DIR . 'media_thumbnails/' . $_POST['media']['path'], FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . FREO_FILE_DIR . 'media_thumbnails/' . $_POST['media']['path'] . ' を作成できません。');
			}

			if (!rename(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'] . $_GET['name'], FREO_FILE_DIR . 'media_thumbnails/' . $_POST['media']['path'] . $_POST['media']['file'])) {
				freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
			}
		}

		//ファイルの説明移動
		if (file_exists(FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $_GET['name'] . '.txt')) {
			if (!freo_mkdir(FREO_FILE_DIR . 'media_memos/' . $_POST['media']['path'], FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . FREO_FILE_DIR . 'media_memos/' . $_POST['media']['path'] . ' を作成できません。');
			}

			if (!rename(FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $_GET['name'] . '.txt', FREO_FILE_DIR . 'media_memos/' . $_POST['media']['path'] . $_POST['media']['file'] . '.txt')) {
				freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
			}
		}
	} else {
		//ディレクトリ確認
		if (file_exists(FREO_FILE_DIR . 'medias/' . $_POST['media']['path'] . $_GET['name'] . '/')) {
			freo_error('ディレクトリ ' . FREO_FILE_DIR . 'medias/' . $_GET['path'] . $_GET['name'] . '/ はすでに存在します。');
		}

		//ディレクトリ移動
		if (!rename(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $_GET['name'] . '/', FREO_FILE_DIR . 'medias/' . $_POST['media']['path'] . $_GET['name'] . '/')) {
			freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
		}

		//サムネイル用ディレクトリ移動
		if (file_exists(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'] . $_GET['name'] . '/')) {
			if (!freo_mkdir(FREO_FILE_DIR . 'media_thumbnails/' . $_POST['media']['path'], FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . FREO_FILE_DIR . 'media_thumbnails/' . $_POST['media']['path'] . ' を作成できません。');
			}

			if (!rename(FREO_FILE_DIR . 'media_thumbnails/' . $_GET['path'] . $_GET['name'] . '/', FREO_FILE_DIR . 'media_thumbnails/' . $_POST['media']['path'] . $_GET['name'] . '/')) {
				freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
			}
		}

		//ファイルの説明用ディレクトリ移動
		if (file_exists(FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $_GET['name'] . '/')) {
			if (!freo_mkdir(FREO_FILE_DIR . 'media_memos/' . $_POST['media']['path'], FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . FREO_FILE_DIR . 'media_memos/' . $_POST['media']['path'] . ' を作成できません。');
			}

			if (!rename(FREO_FILE_DIR . 'media_memos/' . $_GET['path'] . $_GET['name'] . '/', FREO_FILE_DIR . 'media_memos/' . $_POST['media']['path'] . $_GET['name'] . '/')) {
				freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
			}
		}

		//閲覧制限用ファイル移動
		if (file_exists(FREO_FILE_DIR . 'media_restrictions/' . $_GET['path'] . $_GET['name'] . '.txt')) {
			if (!freo_mkdir(FREO_FILE_DIR . 'media_restrictions/' . $_POST['media']['path'], FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . FREO_FILE_DIR . 'media_restrictions/' . $_POST['media']['path'] . ' を作成できません。');
			}

			if (!rename(FREO_FILE_DIR . 'media_restrictions/' . $_GET['path'] . $_GET['name'] . '.txt', FREO_FILE_DIR . 'media_restrictions/' . $_POST['media']['path'] . $_GET['name'] . '.txt')) {
				freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
			}
		}

		//閲覧制限用ディレクトリ移動
		if (file_exists(FREO_FILE_DIR . 'media_restrictions/' . $_GET['path'] . $_GET['name'] . '/')) {
			if (!freo_mkdir(FREO_FILE_DIR . 'media_restrictions/' . $_POST['media']['path'], FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . FREO_FILE_DIR . 'media_restrictions/' . $_POST['media']['path'] . ' を作成できません。');
			}

			if (!rename(FREO_FILE_DIR . 'media_restrictions/' . $_GET['path'] . $_GET['name'] . '/', FREO_FILE_DIR . 'media_restrictions/' . $_POST['media']['path'] . $_GET['name'] . '/')) {
				freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
			}
		}
	}

	//ログ記録
	if (empty($_GET['directory'])) {
		freo_log('ファイルを移動しました。');
	} else {
		freo_log('ディレクトリを移動しました。');
	}

	//メディア管理へ移動
	if (empty($_GET['directory'])) {
		$exec = 'move_file';
	} else {
		$exec = 'move_directory';
	}

	freo_redirect('admin/media?exec=' . $exec . '&name=' . $_GET['name'] . '&path=' . $_GET['path'] . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));

	return;
}

?>
