<?php

/*********************************************************************

 freo | フィルター取得 | メディア (2013/04/12)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* フィルター取得 */
function freo_filter_media($mode, $medias)
{
	global $freo;

	if (empty($medias)) {
		return array();
	}

	//データ初期化
	$filters = array();
	foreach ($medias as $media) {
		$filters[$media] = false;
	}

	//フィルター取得
	if ($mode == 'nobody' or ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
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

							//フィルターで制限
							if ($values[0] == 'filter') {
								if (empty($_SESSION['filter'])) {
									$filters[$media] = true;
								} elseif (!empty($values[1])) {
									$filters[$media] = true;

									foreach (explode(',', $values[1]) as $filter) {
										if (in_array($filter, array_keys($_SESSION['filter'], true))) {
											$filters[$media] = false;
										}
									}
								}
							}
						}
						fclose($fp);

						if ($filters[$media]) {
							break;
						}
					} else {
						freo_error('ファイル ' . FREO_FILE_DIR . 'media_restrictions/' . $path . '.txt' . ' を読み込めません。');
					}

					break;
				}
			}
		}
	}

	return $filters;
}

?>
