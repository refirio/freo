<?php

/*********************************************************************

 freo | 管理画面 | 設定編集 (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('admin/config?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/config?error=1');
	}

	//入力データ取得
	$config = $_SESSION['input']['config'];

	//設定内容取得
	$config_flag = false;
	$buffer_flag = false;
	$data        = '';
	$count       = 0;
	$types       = array();

	if ($config['type'] == 'plugin') {
		$dir = FREO_CONFIG_DIR . 'plugins/';
	} else {
		$dir = FREO_CONFIG_DIR;
	}
	if (preg_match('/^[\w\-]+$/', $config['file'])) {
		$file = $config['file'] . '.ini';
	} else {
		$file = null;
	}

	if ($fp = fopen($dir . $file, 'r')) {
		while ($line = fgets($fp)) {
			$line = trim($line);

			if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
				$config_flag = true;

				$data .= $line . "\n";
			} elseif (preg_match('/^;(.+)$/', $line, $matches)) {
				if (!$config_flag) {
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

				$data .= $line . "\n";
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

					if (isset($config['data']['_buffer' . ($count + 1)])) {
						$value = trim($config['data']['_buffer' . ++$count]);

						$buffer_flag = true;
					} else {
						$value = $line;
					}

					$data .= trim($value) . "\n";
				} else {
					switch ($types[$name]) {
						case 'set':
							if (isset($config['data'][$name])) {
								$value = '"' . implode(',', $config['data'][$name]) . '"';
							} else {
								$value = '""';
							}
							break;
						case 'enum':
							if (isset($config['data'][$name])) {
								$value = '"' . $config['data'][$name] . '"';
							}
							break;
						case 'bool':
							if (isset($config['data'][$name])) {
								$value = $config['data'][$name];
							}
							break;
						case 'text':
							if (isset($config['data'][$name])) {
								$value = '"' . preg_replace('/"/', '" FREO_CONFIG_QUOTE "', $config['data'][$name]) . '"';
							}
							break;
						case 'char':
							if (isset($config['data'][$name])) {
								$value = '"' . preg_replace('/"/', '" FREO_CONFIG_QUOTE "', $config['data'][$name]) . '"';
							}
							break;
						case 'int':
							if (isset($config['data'][$name])) {
								$value = $config['data'][$name];
							}
							break;
					}

					$value = str_replace('\\', '\\\\', $value);
					$value = str_replace("\n", '\n', $value);

					$data .= $name . ' = ' . trim($value) . "\n";
				}
			} else {
				$data .= $line . "\n";
			}
		}
		fclose($fp);
	} else {
		freo_error('設定ファイル ' . $dir . $file . ' を読み込めません。');
	}

	//設定内容更新
	if (file_put_contents($dir . $file, $data) === false) {
		freo_error('設定ファイル ' . $dir . $file . ' に書き込めません。');
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('設定を編集しました。');

	//完了画面へ移動
	freo_redirect('admin/config?exec=update&type=' . $config['type'] . '&file=' . $config['file']);

	return;
}

?>
