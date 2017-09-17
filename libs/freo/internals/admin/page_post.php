<?php

/*********************************************************************

 freo | 管理画面 | ページ登録 (2013/08/13)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';

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
		freo_redirect('admin/page?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/page?error=1');
	}

	//入力データ取得
	$page           = $_SESSION['input']['page'];
	$page_associate = $_SESSION['input']['page_associate'];

	if ($page['pid'] == '') {
		$page['pid'] = null;
	}
	if ($page['restriction'] == '') {
		$page['restriction'] = null;
	}
	if ($page['password'] == '') {
		$page['password'] = null;
	}
	if ($page['tag'] == '') {
		$page['tag'] = null;
	}
	if ($page['close'] == '') {
		$page['close'] = null;
	}
	if ($page['memo'] == '') {
		$page['memo'] = null;
	}
	if ($page['text'] == '') {
		$page['text'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET pid = :pid, modified = :now, restriction = :restriction, password = :password, status = :status, display = :display, comment = :comment, trackback = :trackback, sort = :sort, title = :title, tag = :tag, datetime = :datetime, close = :close, memo = :memo, text = :text WHERE id = :id');
		$stmt->bindValue(':pid',         $page['pid']);
		$stmt->bindValue(':now',         date('Y-m-d H:i:s'));
		$stmt->bindValue(':restriction', $page['restriction']);
		$stmt->bindValue(':password',    $page['password']);
		$stmt->bindValue(':status',      $page['status']);
		$stmt->bindValue(':display',     $page['display']);
		$stmt->bindValue(':comment',     $page['comment']);
		$stmt->bindValue(':trackback',   $page['trackback']);
		$stmt->bindValue(':sort',        $page['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':title',       $page['title']);
		$stmt->bindValue(':tag',         $page['tag']);
		$stmt->bindValue(':datetime',    $page['datetime']);
		$stmt->bindValue(':close',       $page['close']);
		$stmt->bindValue(':memo',        $page['memo']);
		$stmt->bindValue(':text',        $page['text']);
		$stmt->bindValue(':id',          $page['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$page_associate['id'] = $page['id'];
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'pages VALUES(:id, :pid, :user_id, :now1, :now2, :approved, :restriction, :password, :status, :display, :commentus, :trackback, :sort, :title, :tag, :datetime, :close, NULL, NULL, :memo, :text)');
		$stmt->bindValue(':id',          $page['id']);
		$stmt->bindValue(':pid',         $page['pid']);
		$stmt->bindValue(':user_id',     $freo->user['id']);
		$stmt->bindValue(':now1',        date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',        date('Y-m-d H:i:s'));
		$stmt->bindValue(':approved',    $page['approved']);
		$stmt->bindValue(':restriction', $page['restriction']);
		$stmt->bindValue(':password',    $page['password']);
		$stmt->bindValue(':status',      $page['status']);
		$stmt->bindValue(':display',     $page['display']);
		$stmt->bindValue(':commentus',   $page['comment']);
		$stmt->bindValue(':trackback',   $page['trackback']);
		$stmt->bindValue(':sort',        $page['sort']);
		$stmt->bindValue(':title',       $page['title']);
		$stmt->bindValue(':tag',         $page['tag']);
		$stmt->bindValue(':datetime',    $page['datetime']);
		$stmt->bindValue(':close',       $page['close']);
		$stmt->bindValue(':memo',        $page['memo']);
		$stmt->bindValue(':text',        $page['text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$page_associate['id'] = $page['id'];
	}

	//ファイル保存
	$file_dir      = FREO_FILE_DIR . 'page_files/' . $page['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/page_files/';

	if (($page['file'] and file_exists($temporary_dir . $page['file'])) or isset($page['file_remove'])) {
		if (isset($page['file_remove'])) {
			$file = null;
		} else {
			$file = $page['file'];
		}
		$org_file = $file;

		freo_rmdir($file_dir, false);

		if ($file) {
			if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $file, $file_dir . $file)) {
				if ($freo->config['page']['filename'] and preg_match('/\.(.*)$/', $file, $matches)) {
					$filename = $page['id'] . '.' . $matches[1];

					if (preg_match('/\/([^\/]*)$/', $filename, $matches)) {
						$filename = $matches[1];
					}

					if (rename($file_dir . $file, $file_dir . $filename)) {
						$file = $filename;
					} else {
						freo_error('ファイル ' . $temporary_dir . $file . ' の名前を変更できません。');
					}
				}

				chmod($file_dir . $file, FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ' . $temporary_dir . $file . ' を移動できません。');
			}
		}

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET file = :file WHERE id = :id');
		$stmt->bindValue(':file', $file);
		$stmt->bindValue(':id',   $page['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($freo->config['page']['thumbnail']) {
			$thumbnail_dir = FREO_FILE_DIR . 'page_thumbnails/' . $page['id'] . '/';
			$temporary_dir = FREO_FILE_DIR . 'temporaries/page_thumbnails/';

			freo_rmdir($thumbnail_dir, false);

			if ($org_file and file_exists($temporary_dir . $org_file)) {
				if (!freo_mkdir($thumbnail_dir, FREO_PERMISSION_DIR)) {
					freo_error('ディレクトリ ' . $thumbnail_dir . ' を作成できません。');
				}

				if (rename($temporary_dir . $org_file, $thumbnail_dir . $file)) {
					chmod($thumbnail_dir . $file, FREO_PERMISSION_FILE);
				} else {
					freo_error('ファイル ' . $temporary_dir . $org_file . ' を移動できません。');
				}
			}
		}
	}

	//イメージ保存
	$image_dir     = FREO_FILE_DIR . 'page_images/' . $page['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/page_images/';

	if (($page['image'] and file_exists($temporary_dir . $page['image'])) or isset($page['image_remove'])) {
		if (isset($page['image_remove'])) {
			$image = null;
		} else {
			$image = $page['image'];
		}

		freo_rmdir($image_dir, false);

		if ($image) {
			if (!freo_mkdir($image_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $image_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $image, $image_dir . $image)) {
				if ($freo->config['page']['filename'] and preg_match('/\.(.*)$/', $image, $matches)) {
					$filename = $page['id'] . '.' . $matches[1];

					if (preg_match('/\/([^\/]*)$/', $filename, $matches)) {
						$filename = $matches[1];
					}

					if (rename($image_dir . $image, $image_dir . $filename)) {
						$image = $filename;
					} else {
						freo_error('ファイル ' . $temporary_dir . $image . ' の名前を変更できません。');
					}
				}

				chmod($image_dir . $image, FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ' . $temporary_dir . $image . ' を移動できません。');
			}
		}

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET image = :image WHERE id = :id');
		$stmt->bindValue(':image', $image);
		$stmt->bindValue(':id',    $page['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//オプションファイル保存
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'page\') AND type = \'file\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$option_dir    = FREO_FILE_DIR . 'page_options/' . $page['id'] . '/' . $data['id'] . '/';
		$temporary_dir = FREO_FILE_DIR . 'temporaries/page_options/' . $data['id'] . '/';

		if (($page_associate['option'][$data['id']] and file_exists($temporary_dir . $page_associate['option'][$data['id']])) or isset($page_associate['option_remove'][$data['id']])) {
			if (isset($page_associate['option_remove'][$data['id']])) {
				$option_file = null;
			} else {
				$option_file = $page_associate['option'][$data['id']];
			}
			$org_file = $option_file;

			freo_rmdir($option_dir, false);

			if ($option_file) {
				if (!freo_mkdir($option_dir, FREO_PERMISSION_DIR)) {
					freo_error('ディレクトリ ' . $option_dir . ' を作成できません。');
				}

				if (rename($temporary_dir . $option_file, $option_dir . $option_file)) {
					if ($freo->config['option']['filename'] and preg_match('/\.(.*)$/', $option_file, $matches)) {
						$filename = $data['id'] . '.' . $matches[1];

						if (rename($option_dir . $option_file, $option_dir . $filename)) {
							$option_file = $filename;
						} else {
							freo_error('ファイル ' . $temporary_dir . $option_file . ' の名前を変更できません。');
						}
					}

					chmod($option_dir . $option_file, FREO_PERMISSION_FILE);
				} else {
					freo_error('ファイル ' . $temporary_dir . $option_file . ' を移動できません。');
				}

				$page_associate['option'][$data['id']] = $option_file;
			} else {
				$page_associate['option'][$data['id']] = null;
			}

			if ($freo->config['option']['thumbnail']) {
				$option_file = $org_file;

				if ($option_file and file_exists($temporary_dir . 'thumbnail_' . $option_file)) {
					if (rename($temporary_dir . 'thumbnail_' . $option_file, $option_dir . 'thumbnail_' . $option_file)) {
						if ($freo->config['option']['filename'] and preg_match('/\.(.*)$/', $option_file, $matches)) {
							$filename = $data['id'] . '.' . $matches[1];

							if (rename($option_dir . 'thumbnail_' . $option_file, $option_dir . 'thumbnail_' . $filename)) {
								$option_file = $filename;
							} else {
								freo_error('ファイル ' . $temporary_dir . 'thumbnail_' . $option_file . ' の名前を変更できません。');
							}
						}

						chmod($option_dir . 'thumbnail_' . $option_file, FREO_PERMISSION_FILE);
					} else {
						freo_error('ファイル ' . $temporary_dir . 'thumbnail_' . $option_file . ' を移動できません。');
					}
				}
			}
		}
	}

	$options_dir = FREO_FILE_DIR . 'page_options/' . $page['id'] . '/';

	if (file_exists($options_dir) and $dir = scandir($options_dir)) {
		$option_flag = false;

		foreach ($dir as $data) {
			if ($data == '.' or $data == '..') {
				continue;
			}
			if (is_dir($options_dir . $data)) {
				$option_flag = true;

				break;
			}
		}

		if (!$option_flag) {
			freo_rmdir($options_dir, false);
		}
	}

	//関連データ更新
	freo_associate_page('post', $page_associate);

	//トラックバック送信
	if ($page['trackback_url']) {
		$error_message = '';

		foreach (explode("\n", $page['trackback_url']) as $trackback_url) {
			list($flag, $message) = freo_trackback($trackback_url, $page['title'], $freo->core['http_file'] . '/page/' . $page['id'], $page['text'], $freo->config['basis']['title']);
			if (!$flag) {
				$error_message .= $message;
			}
		}
	} else {
		$error_message = '';
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('ページを編集しました。');
	} else {
		freo_log('ページを新規に登録しました。');
	}

	//トラックバックエラー確認
	if ($error_message) {
		freo_error($error_message . 'ページは登録されました。');
	}

	//ページ管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('admin/page?exec=update&id=' . $page['id'] . ($page['pid'] ? '&pid=' . $page['pid'] : ''));
	} else {
		freo_redirect('admin/page?exec=insert' . ($page['pid'] ? '&pid=' . $page['pid'] : ''));
	}

	return;
}

?>
