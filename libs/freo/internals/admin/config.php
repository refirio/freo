<?php

/*********************************************************************

 freo | 管理画面 | 設定管理 (2013/08/13)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_config.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['type']) or !preg_match('/^[\w\-]+$/', $_GET['type'])) {
		$_GET['type'] = null;
	}
	if (!isset($_GET['file']) or !preg_match('/^[\w\-]+$/', $_GET['file'])) {
		$_GET['file'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_config('update', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//エラー確認
		if (!$freo->smarty->get_template_vars('errors')) {
			//プレビューへ移動
			$_SESSION['input'] = $_POST;

			freo_redirect('admin/config_post?freo%5Btoken%5D=' . freo_token('create') . '&type=' . $_GET['type'] . '&file=' . $_GET['file']);
		}
	}

	//設定一覧取得
	$internals = array(
		array(
			'id'   => 'basis',
			'name' => '基本設定'
		),
		array(
			'id'   => 'view',
			'name' => '表示設定'
		),
		array(
			'id'   => 'user',
			'name' => 'ユーザーの設定'
		),
		array(
			'id'   => 'entry',
			'name' => 'エントリーの設定'
		),
		array(
			'id'   => 'page',
			'name' => 'ページの設定'
		),
		array(
			'id'   => 'comment',
			'name' => 'コメントの設定'
		),
		array(
			'id'   => 'trackback',
			'name' => 'トラックバックの設定'
		),
		array(
			'id'   => 'media',
			'name' => 'メディアの設定'
		),
		array(
			'id'   => 'option',
			'name' => 'オプションの設定'
		)
	);

	//プラグイン設定一覧取得
	$plugins = array();

	if ($dir = scandir(FREO_MAIN_DIR . 'freo/plugins/')) {
		foreach ($dir as $data) {
			if (is_file(FREO_MAIN_DIR . 'freo/plugins/' . $data) and preg_match("/^config\.(\w+)\.php$/", $data, $matches)) {
				$id = $matches[1];
			} else {
				continue;
			}

			if (!defined('FREO_PLUGIN_' . strtoupper($id) . '_NAME')) {
				continue;
			}

			if (!is_file(FREO_CONFIG_DIR . 'plugins/' . $id . '.ini')) {
				continue;
			}

			$plugins[] = array(
				'id'   => $id,
				'name' => constant('FREO_PLUGIN_' . strtoupper($id) . '_NAME')
			);
		}
	} else {
		freo_error('プラグイン格納ディレクトリを開けません。');
	}

	//設定内容取得
	$configs     = array();
	$config_name = null;

	if ($_GET['type'] and $_GET['file']) {
		$config_flag = false;
		$buffer      = '';
		$count       = 0;
		$types       = array();
		$sizes       = array();
		$exts        = array();

		if ($_GET['type'] == 'plugin') {
			$dir = FREO_CONFIG_DIR . 'plugins/';
		} else {
			$dir = FREO_CONFIG_DIR;
		}
		if ($_GET['file']) {
			$file = $_GET['file'] . '.ini';
		} else {
			$file = null;
		}

		if ($fp = fopen($dir . $file, 'r')) {
			while ($line = fgets($fp)) {
				$line = trim($line);

				if ($buffer and (preg_match('/^\[.+\]$/', $line) or preg_match('/^;.+$/', $line))) {
					$configs[] = array(
						'type' => 'config',
						'name' => null,
						'data' => '<textarea name="config[data][_buffer' . ++$count . ']" cols="50" rows="5">' . (isset($_POST['config']['data']['_buffer' . $count]) ? $_POST['config']['data']['_buffer' . $count] : $buffer) . '</textarea>'
					);

					$buffer = '';
				}

				if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
					$config_flag = true;

					$configs[] = array(
						'type' => 'section',
						'name' => null,
						'data' => $matches[1]
					);
				} elseif (preg_match('/^;(.+)$/', $line, $matches)) {
					if ($matches[1] == 'exit') {
						break;
					}

					if ($config_flag) {
						$configs[] = array(
							'type' => 'comment',
							'name' => null,
							'data' => $matches[1]
						);
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
							$sizes[$name] = $size;
							$exts[$name]  = $ext;
						}
					}
				} elseif (preg_match('/^([^\s]+)\s*=\s*(.+)$/', $line, $matches)) {
					if (!$config_flag) {
						continue;
					}

					$name  = $matches[1];
					$value = $matches[2];

					$value = str_replace('<', '&lt;', $value);
					$value = str_replace('>', '&gt;', $value);

					if (empty($types[$name])) {
						$buffer .= $line . "\n";
					} else {
						$data = '';

						switch ($types[$name]) {
							case 'set':
								$options = explode('|', $exts[$name]);

								if (preg_match('/^"(.*)"$/', $value, $matches)) {
									$value = $matches[1];
								}
								if (isset($_POST['config']['data'][$name])) {
									$value = implode(',', $_POST['config']['data'][$name]);
								}

								foreach ($options as $option) {
									list($option_key, $option_value) = explode(':', $option, 2);

									if (in_array($option_key, explode(',', $value))) {
										$checked = ' checked="checked"';
									} else {
										$checked = '';
									}

									$data .= '<input type="checkbox" name="config[data][' . $name . '][]" id="label_' . $name . '_' . $option_key . '" value="' . $option_key . '"' . $checked . ' /> <label for="label_' . $name . '_' . $option_key . '"">' . $option_value . '</label><br />';
								}

								break;
							case 'enum':
								$options = explode('|', $exts[$name]);

								if (preg_match('/^"(.*)"$/', $value, $matches)) {
									$value = $matches[1];
								}
								if (isset($_POST['config']['data'][$name])) {
									$value = $_POST['config']['data'][$name];
								}

								foreach ($options as $option) {
									list($option_key, $option_value) = explode(':', $option, 2);

									if ($value == $option_key) {
										$checked = ' checked="checked"';
									} else {
										$checked = '';
									}

									$data .= '<input type="radio" name="config[data][' . $name . ']" id="label_' . $name . '_' . $option_key . '" value="' . $option_key . '"' . $checked . ' /> <label for="label_' . $name . '_' . $option_key . '"">' . $option_value . '</label><br />';
								}

								break;
							case 'bool':
								if (empty($exts[$name])) {
									$options = array('On', 'Off');
								} else {
									$options = explode('|', $exts[$name], 2);
								}

								if (isset($_POST['config']['data'][$name])) {
									$value = $_POST['config']['data'][$name];
								}

								$data .= '<select name="config[data][' . $name . ']">';
								if ($value == 'On') {
									$data .= '<option value="On" selected="selected">' . $options[0] . '</option>';
									$data .= '<option value="Off">' . $options[1] . '</option>';
								} else {
									$data .= '<option value="On">' . $options[0] . '</option>';
									$data .= '<option value="Off" selected="selected">' . $options[1] . '</option>';
								}
								$data .= '</select>';

								break;
							case 'text':
								if (preg_match('/^"(.*)"$/', $value, $matches)) {
									$matches[1] = preg_replace('/" FREO_CONFIG_QUOTE "/', '&quot;', $matches[1]);
									$matches[1] = str_replace('\\\\', "\a", $matches[1]);
									$matches[1] = str_replace('\n', "\n", $matches[1]);
									$matches[1] = str_replace("\a", '\\', $matches[1]);

									$value = $matches[1];
								}
								if (isset($_POST['config']['data'][$name])) {
									$value = $_POST['config']['data'][$name];
								}

								if ($freo->agent['type'] == 'mobile') {
									$data .= '<textarea name="config[data][' . $name . ']" cols="20" rows="5">' . $value . '</textarea>';
								} elseif ($freo->agent['type'] == 'iphone') {
									$data .= '<textarea name="config[data][' . $name . ']" cols="30" rows="5">' . $value . '</textarea>';
								} else {
									$data .= '<textarea name="config[data][' . $name . ']" cols="50" rows="5">' . $value . '</textarea>';
								}

								break;
							case 'char':
								if (preg_match('/^"(.*)"$/', $value, $matches)) {
									$matches[1] = preg_replace('/" FREO_CONFIG_QUOTE "/', '&quot;', $matches[1]);
									$matches[1] = str_replace('\\\\', '\\', $matches[1]);

									$value = $matches[1];
								}
								if (isset($_POST['config']['data'][$name])) {
									$value = $_POST['config']['data'][$name];
								}

								if ($freo->agent['type'] == 'mobile') {
									$data .= '<input type="text" name="config[data][' . $name . ']" size="20" value="' . $value . '" />';
								} elseif ($freo->agent['type'] == 'iphone') {
									$data .= '<input type="text" name="config[data][' . $name . ']" size="30" value="' . $value . '" />';
								} else {
									$data .= '<input type="text" name="config[data][' . $name . ']" size="' . (empty($sizes[$name]) ? 80 : $sizes[$name]) . '" value="' . $value . '" />';
								}

								if (!empty($exts[$name])) {
									$data .= ' ' . $exts[$name];
								}

								break;
							case 'int':
								if (isset($_POST['config']['data'][$name])) {
									$value = $_POST['config']['data'][$name];
								}

								if ($freo->agent['type'] == 'mobile') {
									$data .= '<input type="text" name="config[data][' . $name . ']" size="5" value="' . $value . '"';
									if ($freo->agent['career'] == 'docomo') {
										$data .= ' style="-wap-input-format:&quot;*&lt;ja:n&gt;&quot;;"';
									} else {
										$data .= ' istyle="4" format="*N" mode="numeric"';
									}
									$data .= ' />';
								} elseif ($freo->agent['type'] == 'iphone') {
									$data .= '<input type="text" name="config[data][' . $name . ']" size="5" value="' . $value . '" />';
								} else {
									$data .= '<input type="text" name="config[data][' . $name . ']" size="' . (empty($sizes[$name]) ? 10 : $sizes[$name]) . '" value="' . $value . '" />';
								}

								if (!empty($exts[$name])) {
									$data .= ' ' . $exts[$name];
								}

								break;
						}

						$configs[] = array(
							'type' => 'config',
							'name' => $name,
							'data' => $data
						);
					}
				}
			}
			fclose($fp);
		} else {
			freo_error('設定ファイルを読み込めません。');
		}

		if ($_GET['type'] == 'plugin') {
			$config_name = constant('FREO_PLUGIN_' . strtoupper($_GET['file']) . '_NAME');
		} else {
			foreach ($internals as $internal) {
				if ($internal['id'] == $_GET['file']) {
					$config_name = $internal['name'];
				}
			}
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'internals'   => $internals,
		'plugins'     => $plugins,
		'configs'     => $configs,
		'config_name' => $config_name
	));

	return;
}

?>
