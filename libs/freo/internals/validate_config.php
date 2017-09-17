<?php

/*********************************************************************

 freo | 入力データ検証 | 設定更新 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* 入力データ検証 */
function freo_validate_config($mode, $input)
{
	global $freo;

	$errors = array();

	//設定ファイルの種類
	if ($input['config']['type'] == '') {
		$errors[] = '設定ファイルの種類が入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['config']['type'])) {
		$errors[] = '設定ファイルの種類は半角英数字で入力してください。';
	}

	//設定ファイル
	if ($input['config']['file'] == '') {
		$errors[] = '設定ファイル名が入力されていません。';
	} elseif (!preg_match('/^[\w\-]+$/', $input['config']['file'])) {
		$errors[] = '設定ファイル名は半角英数字で入力してください。';
	}

	//設定内容
	if (!$errors) {
		$config_flag = false;
		$buffer_flag = false;
		$buffer      = '';
		$count       = 0;
		$types       = array();

		if ($input['config']['type'] == 'plugin') {
			$dir = FREO_CONFIG_DIR . 'plugins/';
		} else {
			$dir = FREO_CONFIG_DIR;
		}
		if (preg_match('/^[\w\-]+$/', $input['config']['file'])) {
			$file = $input['config']['file'] . '.ini';
		} else {
			$file = null;
		}

		if ($fp = fopen($dir . $file, 'r')) {
			while ($line = fgets($fp)) {
				$line = trim($line);

				if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
					$config_flag = true;
				} elseif (preg_match('/^;(.+)$/', $line, $matches)) {
					if ($config_flag) {
						$buffer .= $matches[1];
					} else {
						$define = $matches[1];

						if (preg_match('/^(\S+)\s+(\S+)\s+"(\S+)"$/', $define, $matches)) {
							$name = $matches[1];
							$type = $matches[2];
							$ext  = $matches[3];
						} elseif (preg_match('/^(\S+)\s+(\S+)$/', $define, $matches)) {
							$name = $matches[1];
							$type = $matches[2];
							$ext  = '';
						} else {
							$name = '';
							$type = '';
							$ext  = '';
						}

						if ($name) {
							if (preg_match('/^(\S+)\((\d+)\)$/', $type, $matches)) {
								$type = $matches[1];
								$size = $matches[2];
							} else {
								$size = '';
							}

							$types[$name] = $type;
						}
					}
					if ($buffer_flag) {
						$buffer_flag = false;
					}
				} elseif (preg_match('/^([^\s]+)\s*=\s*(.+)$/', $line, $matches)) {
					if (!$config_flag) {
						continue;
					}

					$name  = $matches[1];
					$value = $matches[2];

					if (empty($types[$name])) {
						if ($buffer_flag) {
							continue;
						}

						if (isset($input['config']['data']['_buffer' . ($count + 1)])) {
							$value = trim($input['config']['data']['_buffer' . ++$count]);

							if (preg_match('/(^|\n);/', $value)) {
								$errors[] = $buffer . 'の行頭に;を書くことはできません。';
							}

							$buffer_flag = true;
						} else {
							$value = $line;
						}
					} else {
						switch ($types[$name]) {
							case 'set':
								break;
							case 'enum':
								break;
							case 'bool':
								if (isset($input['config']['data'][$name])) {
									$value = $input['config']['data'][$name];
								}
								if (!preg_match('/^(On|Off)$/', $value)) {
									$errors[] = $buffer . 'はOn/Offで入力してください。';
								}
								break;
							case 'text':
								break;
							case 'char':
								if (isset($input['config']['data'][$name])) {
									$value = '"' . preg_replace('/"/', '" FREO_CONFIG_QUOTE "', $input['config']['data'][$name]) . '"';
								}
								if (preg_match('/\n/', $value)) {
									$errors[] = $buffer . 'は一行で入力してください。';
								}
								break;
							case 'int':
								if (isset($input['config']['data'][$name])) {
									$value = $input['config']['data'][$name];
								}
								if (!preg_match('/^\d+$/', $value)) {
									$errors[] = $buffer . 'は数値で入力してください。';
								}
								break;
						}
					}

					$buffer = '';
				}
			}
			fclose($fp);
		} else {
			$errors[] = '設定ファイルを読み込めません。';
		}
	}

	return $errors;
}

?>
