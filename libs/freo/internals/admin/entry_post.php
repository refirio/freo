<?php

/*********************************************************************

 freo | 管理画面 | エントリー登録 (2013/08/13)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';

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
		freo_redirect('admin/entry?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/entry?error=1');
	}

	//入力データ取得
	$entry           = $_SESSION['input']['entry'];
	$entry_associate = $_SESSION['input']['entry_associate'];

	if ($entry['restriction'] == '') {
		$entry['restriction'] = null;
	}
	if ($entry['password'] == '') {
		$entry['password'] = null;
	}
	if ($entry['code'] == '') {
		$entry['code'] = null;
	}
	if ($entry['tag'] == '') {
		$entry['tag'] = null;
	}
	if ($entry['close'] == '') {
		$entry['close'] = null;
	}
	if ($entry['memo'] == '') {
		$entry['memo'] = null;
	}
	if ($entry['text'] == '') {
		$entry['text'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'entries SET modified = :now, restriction = :restriction, password = :password, status = :status, display = :display, comment = :comment, trackback = :trackback, code = :code, title = :title, tag = :tag, datetime = :datetime, close = :close, memo = :memo, text = :text WHERE id = :id');
		$stmt->bindValue(':now',         date('Y-m-d H:i:s'));
		$stmt->bindValue(':restriction', $entry['restriction']);
		$stmt->bindValue(':password',    $entry['password']);
		$stmt->bindValue(':status',      $entry['status']);
		$stmt->bindValue(':display',     $entry['display']);
		$stmt->bindValue(':comment',     $entry['comment']);
		$stmt->bindValue(':trackback',   $entry['trackback']);
		$stmt->bindValue(':code',        $entry['code']);
		$stmt->bindValue(':title',       $entry['title']);
		$stmt->bindValue(':tag',         $entry['tag']);
		$stmt->bindValue(':datetime',    $entry['datetime']);
		$stmt->bindValue(':close',       $entry['close']);
		$stmt->bindValue(':memo',        $entry['memo']);
		$stmt->bindValue(':text',        $entry['text']);
		$stmt->bindValue(':id',          $entry['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$entry_associate['id'] = $entry['id'];
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'entries VALUES(NULL, :user_id, :now1, :now2, :approved, :restriction, :password, :status, :display, :commentus, :trackback, :code, :title, :tag, :datetime, :close, NULL, NULL, :memo, :text)');
		$stmt->bindValue(':user_id',     $freo->user['id']);
		$stmt->bindValue(':now1',        date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',        date('Y-m-d H:i:s'));
		$stmt->bindValue(':approved',    $entry['approved']);
		$stmt->bindValue(':restriction', $entry['restriction']);
		$stmt->bindValue(':password',    $entry['password']);
		$stmt->bindValue(':status',      $entry['status']);
		$stmt->bindValue(':display',     $entry['display']);
		$stmt->bindValue(':commentus',   $entry['comment']);
		$stmt->bindValue(':trackback',   $entry['trackback']);
		$stmt->bindValue(':code',        $entry['code']);
		$stmt->bindValue(':title',       $entry['title']);
		$stmt->bindValue(':tag',         $entry['tag']);
		$stmt->bindValue(':datetime',    $entry['datetime']);
		$stmt->bindValue(':close',       $entry['close']);
		$stmt->bindValue(':memo',        $entry['memo']);
		$stmt->bindValue(':text',        $entry['text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$entry['id']           = $freo->pdo->lastInsertId();
		$entry_associate['id'] = $freo->pdo->lastInsertId();
	}

	//ファイル保存
	$file_dir      = FREO_FILE_DIR . 'entry_files/' . $entry['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/entry_files/';

	if (($entry['file'] and file_exists($temporary_dir . $entry['file'])) or isset($entry['file_remove'])) {
		if (isset($entry['file_remove'])) {
			$file = null;
		} else {
			$file = $entry['file'];
		}
		$org_file = $file;

		freo_rmdir($file_dir);

		if ($file) {
			if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $file, $file_dir . $file)) {
				if ($freo->config['entry']['filename'] and preg_match('/\.(.*)$/', $file, $matches)) {
					$filename = $entry['id'] . '.' . $matches[1];

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

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'entries SET file = :file WHERE id = :id');
		$stmt->bindValue(':file', $file);
		$stmt->bindValue(':id',   $entry['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($freo->config['entry']['thumbnail']) {
			$thumbnail_dir = FREO_FILE_DIR . 'entry_thumbnails/' . $entry['id'] . '/';
			$temporary_dir = FREO_FILE_DIR . 'temporaries/entry_thumbnails/';

			freo_rmdir($thumbnail_dir);

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
	$image_dir     = FREO_FILE_DIR . 'entry_images/' . $entry['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/entry_images/';

	if (($entry['image'] and file_exists($temporary_dir . $entry['image'])) or isset($entry['image_remove'])) {
		if (isset($entry['image_remove'])) {
			$image = null;
		} else {
			$image = $entry['image'];
		}

		freo_rmdir($image_dir);

		if ($image) {
			if (!freo_mkdir($image_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $image_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $image, $image_dir . $image)) {
				if ($freo->config['entry']['filename'] and preg_match('/\.(.*)$/', $image, $matches)) {
					$filename = $entry['id'] . '.' . $matches[1];

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

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'entries SET image = :image WHERE id = :id');
		$stmt->bindValue(':image', $image);
		$stmt->bindValue(':id',    $entry['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//オプションファイル保存
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options WHERE (target IS NULL OR target = \'entry\') AND type = \'file\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$option_dir    = FREO_FILE_DIR . 'entry_options/' . $entry['id'] . '/' . $data['id'] . '/';
		$temporary_dir = FREO_FILE_DIR . 'temporaries/entry_options/' . $data['id'] . '/';

		if (($entry_associate['option'][$data['id']] and file_exists($temporary_dir . $entry_associate['option'][$data['id']])) or isset($entry_associate['option_remove'][$data['id']])) {
			if (isset($entry_associate['option_remove'][$data['id']])) {
				$option_file = null;
			} else {
				$option_file = $entry_associate['option'][$data['id']];
			}
			$org_file = $option_file;

			freo_rmdir($option_dir);

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

				$entry_associate['option'][$data['id']] = $option_file;
			} else {
				$entry_associate['option'][$data['id']] = null;
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

	$options_dir = FREO_FILE_DIR . 'entry_options/' . $entry['id'] . '/';

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
			freo_rmdir($options_dir);
		}
	}

	//関連データ更新
	freo_associate_entry('post', $entry_associate);

	//トラックバック送信
	if ($entry['trackback_url']) {
		$error_message = '';

		foreach (explode("\n", $entry['trackback_url']) as $trackback_url) {
			list($flag, $message) = freo_trackback($trackback_url, $entry['title'], $freo->core['http_file'] . '/view/' . $entry['id'], $entry['text'], $freo->config['basis']['title']);
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
		freo_log('エントリーを編集しました。');
	} else {
		freo_log('エントリーを新規に登録しました。');
	}

	//トラックバックエラー確認
	if ($error_message) {
		freo_error($error_message . 'エントリーは登録されました。');
	}

	//エントリー管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('admin/entry?exec=update&id=' . $entry['id']);
	} else {
		freo_redirect('admin/entry?exec=insert');
	}

	return;
}

?>
