<?php

/*********************************************************************

 freo | 管理画面 | メディア登録 (2012/12/11)

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

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
	}

	//ワンタイムトークン確認
	if (!freo_token('check', (isset($_GET['type']) ? $_GET['type'] : null))) {
		freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
	}

	//入力データ取得
	$media = $_SESSION['input']['media'];

	//アップロード先取得
	$file_dir        = FREO_FILE_DIR . 'medias/' . ($media['path'] ? $media['path'] . '/' : '');
	$thumbnail_dir   = FREO_FILE_DIR . 'media_thumbnails/' . ($media['path'] ? $media['path'] . '/' : '');
	$memo_dir        = FREO_FILE_DIR . 'media_memos/' . ($media['path'] ? $media['path'] . '/' : '');
	$restriction_dir = FREO_FILE_DIR . 'media_restrictions/' . ($media['path'] ? $media['path'] . '/' : '');

	if ($media['exec'] == 'restrict_directory') {
		$restriction = null;

		if ($media['restriction'] == 'user') {
			$restriction .= "user\t\n";
		}
		if ($media['restriction'] == 'group') {
			$restriction .= "group\t" . (!empty($media['group']) ? implode(',', array_keys($media['group'])) : '') . "\n";
		}
		if ($media['restriction'] == 'password') {
			$restriction .= "password\t" . $media['password'] . "\n";
		}
		if (!empty($media['filter'])) {
			$restriction .= "filter\t" . implode(',', array_keys($media['filter'])) . "\n";
		}

		//閲覧制限登録
		if ($restriction == null) {
			if (file_exists($file_dir . $media['directory'] . '/.htaccess') and !unlink($file_dir . $media['directory'] . '/.htaccess')) {
				freo_error('ファイル ' . $file_dir . $media['directory'] . '/.htaccess' . ' を削除できません。');
			}
			if (file_exists($thumbnail_dir . $media['directory'] . '/.htaccess') and !unlink($thumbnail_dir . $media['directory'] . '/.htaccess')) {
				freo_error('ファイル ' . $thumbnail_dir . $media['directory'] . '/.htaccess' . ' を削除できません。');
			}

			if (file_exists($restriction_dir . $media['directory'] . '.txt')) {
				if (!unlink($restriction_dir . $media['directory'] . '.txt')) {
					freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
				}
			}
		} else {
			if (!file_put_contents($file_dir . $media['directory'] . '/.htaccess', "Deny from all\n")) {
				freo_error('ファイル ' . $file_dir . $media['directory'] . '/.htaccess' . ' に書き込めません。');
			}
			if (file_exists($thumbnail_dir . $media['directory'] . '/') and !file_put_contents($thumbnail_dir . $media['directory'] . '/.htaccess', "Deny from all\n")) {
				freo_error('ファイル ' . $thumbnail_dir . $media['directory'] . '/.htaccess' . ' に書き込めません。');
			}

			if (!freo_mkdir($restriction_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $restriction_dir . ' を作成できません。');
			}

			if (file_put_contents($restriction_dir . $media['directory'] . '.txt', $restriction) === false) {
				freo_error('ファイル ' . $restriction_dir . $media['directory'] . '.txt' . ' に書き込めません。');
			}
		}
	} elseif ($media['exec'] == 'rename_directory') {
		//ディレクトリ名変更
		if (!rename($file_dir . $media['directory_org'], $file_dir . $media['directory'])) {
			freo_error('ディレクトリ ' . $file_dir . $media['directory_org'] . ' の名前を変更できません。');
		}

		//サムネイル用ディレクトリ名変更
		if (file_exists($thumbnail_dir . $media['directory_org'])) {
			if (!rename($thumbnail_dir . $media['directory_org'], $thumbnail_dir . $media['directory'])) {
				freo_error('ディレクトリ ' . $thumbnail_dir . $media['directory_org'] . ' の名前を変更できません。');
			}
		}

		//ファイルの説明用ディレクトリ名変更
		if (file_exists($memo_dir . $media['directory_org'])) {
			if (!rename($memo_dir . $media['directory_org'], $memo_dir . $media['directory'])) {
				freo_error('ディレクトリ ' . $memo_dir . $media['directory_org'] . ' の名前を変更できません。');
			}
		}

		//閲覧制限用ファイル名変更
		if (file_exists($restriction_dir . $media['directory_org'] . '.txt')) {
			if (!rename($restriction_dir . $media['directory_org'] . '.txt', $restriction_dir . $media['directory'] . '.txt')) {
				freo_error('ディレクトリ ' . $restriction_dir . $media['directory_org'] . '.txt' . ' の名前を変更できません。');
			}
		}

		//閲覧制限用ディレクトリ名変更
		if (file_exists($restriction_dir . $media['directory_org'])) {
			if (!rename($restriction_dir . $media['directory_org'], $restriction_dir . $media['directory'])) {
				freo_error('ディレクトリ ' . $restriction_dir . $media['directory_org'] . ' の名前を変更できません。');
			}
		}
	} elseif ($media['exec'] == 'insert_directory') {
		//ディレクトリ作成
		if (!freo_mkdir($file_dir . $media['directory'], FREO_PERMISSION_DIR)) {
			freo_error('ディレクトリ ' . $file_dir . $media['directory'] . ' を作成できません。');
		}
	} elseif ($media['exec'] == 'rename_file') {
		//ファイル名変更
		if (!rename($file_dir . $media['file_org'], $file_dir . $media['file'])) {
			freo_error('ファイル ' . $file_dir . $media['file_org'] . ' の名前を変更できません。');
		}

		//サムネイル名変更
		if (file_exists($thumbnail_dir . $media['file_org'])) {
			if (!rename($thumbnail_dir . $media['file_org'], $thumbnail_dir . $media['file'])) {
				freo_error('ファイル ' . $thumbnail_dir . $media['file_org'] . ' の名前を変更できません。');
			}
		}

		//ファイルの説明名変更
		if (file_exists($memo_dir . $media['file_org'] . '.txt')) {
			if (!rename($memo_dir . $media['file_org'] . '.txt', $memo_dir . $media['file'] . '.txt')) {
				freo_error('ファイル ' . $memo_dir . $media['file_org'] . ' の名前を変更できません。');
			}
		}
	} elseif ($media['exec'] == 'insert_file') {
		//ファイル作成
		if (file_put_contents($file_dir . $media['file'], '') === false) {
			freo_error('ファイル ' . $file_dir . $media['file'] . ' を作成できません。');
		}

		chmod($file_dir . $media['file'], FREO_PERMISSION_FILE);
	} elseif ($media['exec'] == 'edit_memo') {
		//ファイルの説明登録
		if ($media['memo'] == '') {
			if (file_exists($memo_dir . $media['file'] . '.txt')) {
				if (!unlink($memo_dir . $media['file'] . '.txt')) {
					freo_redirect('admin/media?error=1' . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));
				}
			}
		} else {
			if (!freo_mkdir($memo_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $memo_dir . ' を作成できません。');
			}

			if (file_put_contents($memo_dir . $media['file'] . '.txt', $media['memo']) === false) {
				freo_error('ファイル ' . $memo_dir . $media['file'] . '.txt' . ' に書き込めません。');
			}
		}
	} elseif ($media['exec'] == 'edit_file') {
		//ファイル編集
		if (file_put_contents($file_dir . $media['file'], $media['text']) === false) {
			freo_error('ファイル ' . $file_dir . $media['file'] . ' に書き込めません。');
		}
	} elseif ($media['exec'] == 'edit_thumbnail') {
		//サムネイル削除
		if (file_exists($thumbnail_dir . $media['file_org'])) {
			if (!unlink($thumbnail_dir . $media['file_org'])) {
				freo_error('ファイル ' . $thumbnail_dir . $media['file_org'] . ' を削除できません。');
			}
		}

		//サムネイル保存
		if (isset($media['thumbnail'])) {
			$temporary_dir = FREO_FILE_DIR . 'temporaries/media_thumbnails/';

			if (!freo_mkdir($thumbnail_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $thumbnail_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $media['thumbnail'], $thumbnail_dir . $media['file_org'])) {
				chmod($thumbnail_dir . $media['file_org'], FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ' . $temporary_dir . $media['thumbnail'] . ' を移動できません。');
			}
		}
	} else {
		//ファイル削除
		if (isset($media['file_org'])) {
			if (!unlink(FREO_FILE_DIR . 'medias/' . ($media['path'] ? $media['path'] . '/' : '') . $media['file_org'])) {
				freo_error('ファイル ' . $file_dir . $media['file_org'] . ' を削除できません。');
			}
		}

		//サムネイル削除
		if (isset($media['file_org']) and file_exists(FREO_FILE_DIR . 'media_thumbnails/' . ($media['path'] ? $media['path'] . '/' : '') . $media['file_org'])) {
			if (!unlink(FREO_FILE_DIR . 'media_thumbnails/' . ($media['path'] ? $media['path'] . '/' : '') . $media['file_org'])) {
				freo_error('ファイル ' . $file_dir . $media['file_org'] . ' を削除できません。');
			}
		}

		//ファイル保存
		if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
			freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
		}

		//アップロード項目数取得
		$file_count = count($media['file']);

		//現在日時取得
		$now = time();

		for ($i = 0; $i < $file_count; $i++) {
			if ($media['file'][$i] == '') {
				continue;
			}

			$org_media = $media['file'][$i];

			if (rename(FREO_FILE_DIR . 'temporaries/medias/' . $media['file'][$i], $file_dir . $media['file'][$i])) {
				if ($freo->config['media']['filename'] and preg_match('/\.(.*)$/', $media['file'][$i], $matches)) {
					$filename = date('YmdHis', $now + $i) . '.' . $matches[1];

					if (rename($file_dir . $media['file'][$i], $file_dir . $filename)) {
						$media['file'][$i] = $filename;
					} else {
						freo_error('ファイル ' . $file_dir . $media['file'][$i] . ' の名前を変更できません。');
					}
				}

				chmod($file_dir . $media['file'][$i], FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ' . FREO_FILE_DIR . 'temporaries/medias/' . $media['file'][$i] . ' を移動できません。');
			}

			if ($freo->config['media']['thumbnail']) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/media_thumbnails/';

				if ($org_media and file_exists($temporary_dir . $org_media)) {
					if (!freo_mkdir($thumbnail_dir, FREO_PERMISSION_DIR)) {
						freo_error('ディレクトリ ' . $thumbnail_dir . ' を作成できません。');
					}

					if (rename($temporary_dir . $org_media, $thumbnail_dir . $media['file'][$i])) {
						chmod($thumbnail_dir . $media['file'][$i], FREO_PERMISSION_FILE);
					} else {
						freo_error('ファイル ' . $temporary_dir . $org_media . ' を移動できません。');
					}
				}
			}
		}

		//ファイルの説明名変更
		if (isset($media['file_org']) and file_exists(FREO_FILE_DIR . 'media_memos/' . ($media['path'] ? $media['path'] . '/' : '') . $media['file_org'] . '.txt')) {
			if (!rename(FREO_FILE_DIR . 'media_memos/' . ($media['path'] ? $media['path'] . '/' : '') . $media['file_org'] . '.txt', FREO_FILE_DIR . 'media_memos/' . ($media['path'] ? $media['path'] . '/' : '') . $media['file'][0] . '.txt')) {
				freo_error('ファイル ' . $memo_dir . $media['file_org'] . ' の名前を変更できません。');
			}
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if ($media['exec'] == 'restrict_directory') {
		freo_log('ディレクトリの閲覧制限を設定しました。');
	} elseif ($media['exec'] == 'rename_directory') {
		freo_log('ディレクトリ名を変更しました。');
	} elseif ($media['exec'] == 'insert_directory') {
		freo_log('ディレクトリを新規に作成しました。');
	} elseif ($media['exec'] == 'rename_file') {
		freo_log('ファイル名を変更しました。');
	} elseif ($media['exec'] == 'insert_file') {
		freo_log('ファイルを新規に作成しました。');
	} elseif ($media['exec'] == 'edit_memo') {
		freo_log('ファイルの説明を登録しました。');
	} elseif ($media['exec'] == 'edit_file') {
		freo_log('ファイルを編集しました。');
	} elseif ($media['exec'] == 'edit_thumbnail') {
		freo_log('サムネイル画像を登録しました。');
	} else {
		freo_log('ファイルを新規に登録しました。');
	}

	//メディア管理へ移動
	if ($media['exec'] == 'restrict_directory') {
		$exec = 'restrict_directory';
	} elseif ($media['exec'] == 'rename_directory') {
		$exec = 'rename_directory';
	} elseif ($media['exec'] == 'insert_directory') {
		$exec = 'insert_directory';
	} elseif ($media['exec'] == 'rename_file') {
		$exec = 'rename_file';
	} elseif ($media['exec'] == 'insert_file') {
		$exec = 'insert_file';
	} elseif ($media['exec'] == 'edit_memo') {
		$exec = 'edit_memo';
	} elseif ($media['exec'] == 'edit_file') {
		$exec = 'edit_file';
	} elseif ($media['exec'] == 'edit_thumbnail') {
		$exec = 'edit_thumbnail';
	} else {
		$exec = 'insert';
	}

	freo_redirect('admin/media?exec=' . $exec . (isset($media['path']) ? '&path=' . $media['path'] : '') . (isset($_GET['type']) ? '&type=' . $_GET['type'] : ''));

	return;
}

?>
