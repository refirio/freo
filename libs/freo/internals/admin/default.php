<?php

/*********************************************************************

 freo | 管理画面 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ユーザー数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'users');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data          = $stmt->fetch(PDO::FETCH_NUM);
	$user_count    = $data[0];
	$user_created  = $data[1];
	$user_modified = $data[2];

	//エントリー数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'entries');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data           = $stmt->fetch(PDO::FETCH_NUM);
	$entry_count    = $data[0];
	$entry_created  = $data[1];
	$entry_modified = $data[2];

	//ページ数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'pages');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data          = $stmt->fetch(PDO::FETCH_NUM);
	$page_count    = $data[0];
	$page_created  = $data[1];
	$page_modified = $data[2];

	//グループ数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'groups');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data           = $stmt->fetch(PDO::FETCH_NUM);
	$group_count    = $data[0];
	$group_created  = $data[1];
	$group_modified = $data[2];

	//フィルター数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'filters');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data            = $stmt->fetch(PDO::FETCH_NUM);
	$filter_count    = $data[0];
	$filter_created  = $data[1];
	$filter_modified = $data[2];

	//オプション数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'options');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data            = $stmt->fetch(PDO::FETCH_NUM);
	$option_count    = $data[0];
	$option_created  = $data[1];
	$option_modified = $data[2];

	//コメント数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'comments');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data             = $stmt->fetch(PDO::FETCH_NUM);
	$comment_count    = $data[0];
	$comment_created  = $data[1];
	$comment_modified = $data[2];

	//トラックバック数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'trackbacks');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data               = $stmt->fetch(PDO::FETCH_NUM);
	$trackback_count    = $data[0];
	$trackback_created  = $data[1];
	$trackback_modified = $data[2];

	//インフォメーション数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'informations');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data                 = $stmt->fetch(PDO::FETCH_NUM);
	$information_count    = $data[0];
	$information_created  = $data[1];
	$information_modified = $data[2];

	//ログ数・最終登録日時・最終更新日時取得
	$stmt = $freo->pdo->query('SELECT COUNT(*), MAX(created), MAX(modified) FROM ' . FREO_DATABASE_PREFIX . 'logs');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data         = $stmt->fetch(PDO::FETCH_NUM);
	$log_count    = $data[0];
	$log_created  = $data[1];
	$log_modified = $data[2];

	//ユーザー承認数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'users WHERE approved = \'yes\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data          = $stmt->fetch(PDO::FETCH_NUM);
	$user_approved = $data[0];

	//エントリー承認数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data           = $stmt->fetch(PDO::FETCH_NUM);
	$entry_approved = $data[0];

	//ページ承認数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data          = $stmt->fetch(PDO::FETCH_NUM);
	$page_approved = $data[0];

	//コメント承認数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'comments WHERE approved = \'yes\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data             = $stmt->fetch(PDO::FETCH_NUM);
	$comment_approved = $data[0];

	//トラックバック承認数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'trackbacks WHERE approved = \'yes\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data               = $stmt->fetch(PDO::FETCH_NUM);
	$trackback_approved = $data[0];

	//エントリーファイル総容量取得
	$entry_file_size = freo_directory(FREO_FILE_DIR . 'entry_files/');

	//エントリーサムネイル総容量取得
	$entry_thumbnail_size = freo_directory(FREO_FILE_DIR . 'entry_thumbnails/');

	//エントリーイメージ総容量取得
	$entry_image_size = freo_directory(FREO_FILE_DIR . 'entry_images/');

	//エントリーオプションファイル総容量取得
	$entry_option_size = freo_directory(FREO_FILE_DIR . 'entry_options/');

	//ページファイル総容量取得
	$page_file_size = freo_directory(FREO_FILE_DIR . 'page_files/');

	//ページサムネイル総容量取得
	$page_thumbnail_size = freo_directory(FREO_FILE_DIR . 'page_thumbnails/');

	//ページイメージ総容量取得
	$page_image_size = freo_directory(FREO_FILE_DIR . 'page_images/');

	//ページオプションファイル総容量取得
	$page_option_size = freo_directory(FREO_FILE_DIR . 'page_options/');

	//メディアファイル総容量取得
	$media_size = freo_directory(FREO_FILE_DIR . 'medias/');

	//メディアサムネイル総容量取得
	$media_thumbnail_size = freo_directory(FREO_FILE_DIR . 'media_thumbnails/');

	//一時ファイル総容量取得
	$temporary_size = freo_directory(FREO_FILE_DIR . 'temporaries/');

	//プラグインファイル総容量取得
	$plugin_size = freo_directory(FREO_FILE_DIR . 'plugins/');

	//データ割当
	$freo->smarty->assign(array(
		'token'                => freo_token('create'),
		'user_count'           => $user_count,
		'entry_count'          => $entry_count,
		'page_count'           => $page_count,
		'group_count'          => $group_count,
		'filter_count'         => $filter_count,
		'option_count'         => $option_count,
		'comment_count'        => $comment_count,
		'trackback_count'      => $trackback_count,
		'information_count'    => $information_count,
		'log_count'            => $log_count,
		'user_created'         => $user_created,
		'entry_created'        => $entry_created,
		'page_created'         => $page_created,
		'group_created'        => $group_created,
		'filter_created'       => $filter_created,
		'option_created'       => $option_created,
		'comment_created'      => $comment_created,
		'trackback_created'    => $trackback_created,
		'log_created'          => $log_created,
		'information_created'  => $information_created,
		'user_modified'        => $user_modified,
		'entry_modified'       => $entry_modified,
		'page_modified'        => $page_modified,
		'group_modified'       => $group_modified,
		'filter_modified'      => $filter_modified,
		'option_modified'      => $option_modified,
		'comment_modified'     => $comment_modified,
		'trackback_modified'   => $trackback_modified,
		'log_modified'         => $log_modified,
		'information_modified' => $information_modified,
		'user_approved'        => $user_approved,
		'entry_approved'       => $entry_approved,
		'page_approved'        => $page_approved,
		'comment_approved'     => $comment_approved,
		'trackback_approved'   => $trackback_approved,
		'entry_file_size'      => $entry_file_size,
		'entry_thumbnail_size' => $entry_thumbnail_size,
		'entry_image_size'     => $entry_image_size,
		'entry_option_size'    => $entry_option_size,
		'page_file_size'       => $page_file_size,
		'page_thumbnail_size'  => $page_thumbnail_size,
		'page_image_size'      => $page_image_size,
		'page_option_size'     => $page_option_size,
		'media_size'           => $media_size,
		'media_thumbnail_size' => $media_thumbnail_size,
		'temporary_size'       => $temporary_size,
		'plugin_size'          => $plugin_size
	));

	return;
}

?>
