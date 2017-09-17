<?php

/*********************************************************************

 freo | 関連データ | メディア (2013/01/09)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* 関連データ */
function freo_associate_media($mode, $data)
{
	global $freo;

	if ($mode == 'get') {
		return freo_associate_media_get($data);
	}

	return;
}

/* 関連データ取得 */
function freo_associate_media_get($medias)
{
	global $freo;

	if (empty($medias)) {
		return array();
	}

	//データ初期化
	$associates = array();
	foreach ($medias as $media) {
		$associates[$media] = array();
	}

	foreach ($medias as $media) {
		//閲覧制限を読み込み
		$paths = explode('/', $media);

		while (!empty($paths)) {
			array_pop($paths);

			$path = implode('/', $paths);

			if (file_exists(FREO_FILE_DIR . 'media_restrictions/' . $path . '.txt')) {
				if ($fp = fopen(FREO_FILE_DIR . 'media_restrictions/' . $path . '.txt', 'r')) {
					while ($line = fgets($fp)) {
						$values = explode("\t", trim($line));

						//グループ情報取得
						if ($values[0] == 'group' and !empty($values[1])) {
							foreach (explode(',', $values[1]) as $group) {
								$associates[$media]['group'][$group] = true;
							}
						}

						//フィルター情報取得
						if ($values[0] == 'filter' and !empty($values[1])) {
							foreach (explode(',', $values[1]) as $filter) {
								$associates[$media]['filter'][$filter] = true;
							}
						}
					}
					fclose($fp);
				} else {
					freo_error('ファイル ' . FREO_FILE_DIR . 'media_restrictions/' . $path . '.txt' . ' を読み込めません。');
				}

				break;
			}
		}
	}

	return $associates;
}

?>
