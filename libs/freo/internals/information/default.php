<?php

/*********************************************************************

 freo | インフォメーション (2012/07/20)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//インフォメーションチェック
	if (!$freo->config['view']['information']) {
		freo_redirect('default');
	}

	//インフォメーション取得
	$information                 = array();
	$information_entry           = array();
	$information_entry_associate = array();
	$information_entry_security  = null;
	$information_entry_filter    = null;
	$information_entry_tags      = array();
	$information_entry_file      = array();
	$information_entry_thumbnail = array();
	$information_entry_image     = array();
	$information_entry_text      = array();
	$information_page            = array();
	$information_page_associate  = array();
	$information_page_security   = null;
	$information_page_filter     = null;
	$information_page_tags       = array();
	$information_page_file       = array();
	$information_page_thumbnail  = array();
	$information_page_image      = array();
	$information_page_text       = array();

	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'informations WHERE id = :id');
	$stmt->bindValue(':id', 'default');
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$information = $data;

		if ($information['entry_id']) {
			//エントリー取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
			$stmt->bindValue(':id',   $information['entry_id'], PDO::PARAM_INT);
			$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$information_entry = $data;

				//エントリー関連データ取得
				$information_entry_associates = freo_associate_entry('get', array($information['entry_id']));
				$information_entry_associate  = $information_entry_associates[$information['entry_id']];

				//エントリーフィルター取得
				$information_entry_filters = freo_filter_entry('user', array($information['entry_id']));
				$information_entry_filter  = $information_entry_filters[$information['entry_id']];

				if ($information_entry_filter) {
					$information_entry['comment']   = 'closed';
					$information_entry['trackback'] = 'closed';
					$information_entry['title']     = str_replace('[$title]', $information_entry['title'], $freo->config['entry']['filter_title']);
					$information_entry['file']      = null;
					$information_entry['image']     = null;
					$information_entry['memo']      = null;
					$information_entry['text']      = str_replace('[$text]', $information_entry['text'], $freo->config['entry']['filter_text']);

					if ($freo->config['entry']['filter_option']) {
						$information_entry_associate['option'] = array();
					}
				}

				//エントリー保護データ取得
				$information_entry_securities = freo_security_entry('user', array($information['entry_id']));
				$information_entry_security   = $information_entry_securities[$information['entry_id']];

				if ($information_entry_security) {
					$information_entry['comment']   = 'closed';
					$information_entry['trackback'] = 'closed';
					$information_entry['title']     = str_replace('[$title]', $information_entry['title'], $freo->config['entry']['restriction_title']);
					$information_entry['file']      = null;
					$information_entry['image']     = null;
					$information_entry['memo']      = null;
					$information_entry['text']      = str_replace('[$text]', $information_entry['text'], $freo->config['entry']['restriction_text']);

					if ($freo->config['entry']['restriction_option']) {
						$information_entry_associate['option'] = array();
					}
				}

				//エントリータグ取得
				if ($information_entry['tag']) {
					$information_entry_tags = explode(',', $information_entry['tag']);
				} else {
					$information_entry_tags = array();
				}

				//エントリーファイル取得
				if ($information_entry['file']) {
					list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_files/' . $information_entry['id'] . '/' . $information_entry['file']);

					$information_entry_file = array(
						'width'  => $width,
						'height' => $height,
						'size'   => $size
					);
				} else {
					$information_entry_file = array();
				}

				//エントリーサムネイル取得
				if ($information_entry['file'] and file_exists(FREO_FILE_DIR . 'entry_thumbnails/' . $information_entry['id'] . '/' . $information_entry['file'])) {
					list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_thumbnails/' . $information_entry['id'] . '/' . $information_entry['file']);

					$information_entry_thumbnail = array(
						'width'  => $width,
						'height' => $height,
						'size'   => $size
					);
				} else {
					$information_entry_thumbnail = array();
				}

				//エントリーイメージ取得
				if ($information_entry['image']) {
					list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_images/' . $information_entry['id'] . '/' . $information_entry['image']);

					$information_entry_image = array(
						'width'  => $width,
						'height' => $height,
						'size'   => $size
					);
				} else {
					$information_entry_image = array();
				}

				//エントリーテキスト取得
				if ($information_entry['text']) {
					if (isset($information_entry_associate['option'])) {
						list($information_entry['text'], $information_entry_associate['option']) = freo_option($information_entry['text'], $information_entry_associate['option'], FREO_FILE_DIR . 'entry_options/' . $information_entry['id'] . '/');
					}
					list($excerpt, $more) = freo_divide($information_entry['text']);

					$information_entry_text = array(
						'excerpt' => $excerpt,
						'more'    => $more
					);
				} else {
					$information_entry_text = array();
				}
			}
		}

		if ($information['page_id']) {
			//ページ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
			$stmt->bindValue(':id',   $information['page_id']);
			$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$information_page = $data;

				//ページ関連データ取得
				$information_page_associates = freo_associate_page('get', array($information['page_id']));
				$information_page_associate  = $information_page_associates[$information['page_id']];

				//ページフィルター取得
				$information_page_filters = freo_filter_page('user', array($information['page_id']));
				$information_page_filter  = $information_page_filters[$information['page_id']];

				if ($information_page_filter) {
					$information_page['comment']   = 'closed';
					$information_page['trackback'] = 'closed';
					$information_page['title']     = str_replace('[$title]', $information_page['title'], $freo->config['page']['filter_title']);
					$information_page['file']      = null;
					$information_page['image']     = null;
					$information_page['memo']      = null;
					$information_page['text']      = str_replace('[$text]', $information_page['title'], $freo->config['page']['filter_text']);

					if ($freo->config['page']['filter_option']) {
						$information_page_associate['option'] = array();
					}
				}

				//ページ保護データ取得
				$information_page_securities = freo_security_page('user', array($information['page_id']));
				$information_page_security   = $information_page_securities[$information['page_id']];

				if ($information_page_security) {
					$information_page['comment']   = 'closed';
					$information_page['trackback'] = 'closed';
					$information_page['title']     = str_replace('[$title]', $information_page['title'], $freo->config['page']['restriction_title']);
					$information_page['file']      = null;
					$information_page['image']     = null;
					$information_page['memo']      = null;
					$information_page['text']      = str_replace('[$text]', $information_page['title'], $freo->config['page']['restriction_text']);

					if ($freo->config['page']['restriction_option']) {
						$information_page_associate['option'] = array();
					}
				}

				//ページタグ取得
				if ($information_page['tag']) {
					$information_page_tags = explode(',', $information_page['tag']);
				} else {
					$information_page_tags = array();
				}

				//ページファイル取得
				if ($information_page['file']) {
					list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_files/' . $information_page['id'] . '/' . $information_page['file']);

					$information_page_file = array(
						'width'  => $width,
						'height' => $height,
						'size'   => $size
					);
				} else {
					$information_page_file = array();
				}

				//ページーサムネイル取得
				if ($information_page['file'] and file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $information_page['id'] . '/' . $information_page['file'])) {
					list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_thumbnails/' . $information_page['id'] . '/' . $information_page['file']);

					$information_page_thumbnail = array(
						'width'  => $width,
						'height' => $height,
						'size'   => $size
					);
				} else {
					$information_page_thumbnail = array();
				}

				//ページイメージ取得
				if ($information_page['image']) {
					list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_images/' . $information_page['id'] . '/' . $information_page['image']);

					$information_page_image = array(
						'width'  => $width,
						'height' => $height,
						'size'   => $size
					);
				} else {
					$information_page_image = array();
				}

				//ページテキスト取得
				if ($information_page['text']) {
					if (isset($information_page_associate['option'])) {
						list($information_page['text'], $information_page_associate['option']) = freo_option($information_page['text'], $information_page_associate['option'], FREO_FILE_DIR . 'page_options/' . $information_page['id'] . '/');
					}
					list($excerpt, $more) = freo_divide($information_page['text']);

					$information_page_text = array(
						'excerpt' => $excerpt,
						'more'    => $more
					);
				} else {
					$information_page_text = array();
				}
			}
		}

		//インフォメーションテキスト取得
		if ($information['text']) {
			list($excerpt, $more) = freo_divide($information['text']);

			$information_text = array(
				'excerpt' => $excerpt,
				'more'    => $more
			);
		} else {
			$information_text = array();
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'information'                 => $information,
		'information_text'            => $information_text,
		'information_entry'           => $information_entry,
		'information_entry_associate' => $information_entry_associate,
		'information_entry_filter'    => $information_entry_filter,
		'information_entry_security'  => $information_entry_security,
		'information_entry_tags'      => $information_entry_tags,
		'information_entry_file'      => $information_entry_file,
		'information_entry_thumbnail' => $information_entry_thumbnail,
		'information_entry_image'     => $information_entry_image,
		'information_entry_text'      => $information_entry_text,
		'information_page'            => $information_page,
		'information_page_associate'  => $information_page_associate,
		'information_page_filter'     => $information_page_filter,
		'information_page_security'   => $information_page_security,
		'information_page_tags'       => $information_page_tags,
		'information_page_file'       => $information_page_file,
		'information_page_thumbnail'  => $information_page_thumbnail,
		'information_page_image'      => $information_page_image,
		'information_page_text'       => $information_page_text
	));

	return;
}

?>
