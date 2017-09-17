<?php

/*********************************************************************

 freo | 保護データ取得 | メディア (2012/12/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* 保護データ取得 */
function freo_security_media($mode, $medias)
{
	global $freo;

	if (empty($medias)) {
		return array();
	}

	//データ初期化
	$securities = array();
	foreach ($medias as $media) {
		$securities[$media] = false;
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

							//ユーザー登録で制限
							if ($values[0] == 'user') {
								$securities[$media] = true;

								if ($mode == 'user' and $freo->user['id']) {
									$securities[$media] = false;
								}
							}

							//グループで制限
							if ($values[0] == 'group') {
								if ($mode == 'nobody' or empty($freo->user['groups'])) {
									$securities[$media] = true;
								} elseif (!empty($values[1])) {
									$securities[$media] = true;

									foreach (explode(',', $values[1]) as $group) {
										if (in_array($group, $freo->user['groups'])) {
											$securities[$media] = false;
										}
									}
								}
							}

							//パスワードで制限
							if ($values[0] == 'password') {
								$securities[$media] = true;

								if (!empty($values[1]) and isset($_SESSION['security']['media'])) {
									foreach ($_SESSION['security']['media'] as $file => $flag) {
										if (!$flag) {
											continue;
										}

										$securities[$media] = false;
									}
								}
							}
						}
						fclose($fp);

						if ($securities[$media]) {
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

	return $securities;
}

?>
