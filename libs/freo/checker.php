<?php

/*********************************************************************

 freo | PHP仕様確認 (2012/09/30)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//アクセス可否確認
if (!defined('FREO_HTTP_URL') or FREO_HTTP_URL != 'http://www.example.com/freo/') {
	exit('checker.php');
}

//外部ファイル読み込み
if (file_exists(FREO_MAIN_DIR . 'freo/common.php')) {
	require_once FREO_MAIN_DIR . 'freo/common.php';
}
if (defined('FREO_INITIALIZE_MODE') and FREO_INITIALIZE_MODE and file_exists(FREO_MAIN_DIR . 'freo/initialize.php')) {
	require_once FREO_MAIN_DIR . 'freo/initialize.php';
}

//chmod使用確認
if (defined('FREO_PERMISSION_MODE') and FREO_PERMISSION_MODE and @chmod(FREO_TEMPLATE_COMPILE_DIR, 0707)) {
	$chmod = true;
} else {
	$chmod = false;
}

//データ出力
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ja\" lang=\"ja\" dir=\"ltr\">\n";
echo "<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
echo "<title>PHP Checker</title>\n";

if (file_exists(FREO_CSS_DIR . 'common.css')) {
	echo "<link rel=\"stylesheet\" href=\"" . FREO_CSS_DIR . "common.css\" type=\"text/css\" media=\"all\" />\n";
}

echo "<style tyle=\"text/css\">\n";
echo "em {\n";
echo "color: #FF0000;\n";
echo "}\n";
echo "table tr th {\n";
echo "text-align: left;\n";
echo "}\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<h1>PHP Checker</h1>\n";
echo "<h2>PHP</h2>\n";
echo "<table summary=\"PHP\">\n";
echo "<tr>\n";
echo "<th>PHP Version</th>\n";
echo "<td>" . (phpversion() >= 5 ? phpversion() : '<em>' . phpversion() . '</em>') . "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "<h2>PDO</h2>\n";
echo "<table summary=\"PDO\">\n";
echo "<tr>\n";
echo "<th>PDO</th>\n";
echo "<td>" . (class_exists('pdo') ? 'OK' : '<em>NG</em>') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>PDO Version</th>\n";
echo "<td>" . phpversion('pdo') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>PDO_MYSQL Version</th>\n";
echo "<td>" . (phpversion('pdo_mysql') ? phpversion('pdo_mysql') : '<em>NG</em>') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>PDO_SQLITE Version</th>\n";
echo "<td>" . (phpversion('pdo_sqlite') ? phpversion('pdo_sqlite') : '<em>NG</em>') . "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "<h2>GD</h2>\n";
echo "<table summary=\"GD\">\n";
echo "<tr>\n";
echo "<th>GD</th>\n";
echo "<td>" . (function_exists('gd_info') ? 'OK' : '<em>NG</em>') . "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "<h2>MultiByte</h2>\n";
echo "<table summary=\"MultiByte\">\n";
echo "<tr>\n";
echo "<th>MultiByte</th>\n";
echo "<td>" . (function_exists('mb_language') ? 'OK' : '<em>NG</em>') . "</td>\n";
echo "</tr>\n";
echo "</table>\n";

if (defined('FREO_PERMISSION_MODE') and FREO_PERMISSION_MODE) {
	echo "<h2>Chmod</h2>\n";
	echo "<table summary=\"Chmod\">\n";
	echo "<tr>\n";
	echo "<th>Chmod</th>\n";
	echo "<td>" . ($chmod ? 'OK' : '<em>NG</em>') . "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

echo "<h2>Charset</h2>\n";
echo "<table summary=\"Charset\">\n";
echo "<tr>\n";
echo "<th>default_charset</th>\n";
echo "<td>" . (ini_get('default_charset') ? ini_get('default_charset') : 'NONE') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>mbstring.input_encoding</th>\n";
echo "<td>" . (ini_get('mbstring.input_encoding') ? ini_get('mbstring.input_encoding') : 'NONE') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>mbstring.internal_encoding</th>\n";
echo "<td>" . (ini_get('mbstring.internal_encoding') ? ini_get('mbstring.internal_encoding') : 'NONE') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>mbstring.output_encoding</th>\n";
echo "<td>" . (ini_get('mbstring.output_encoding') ? ini_get('mbstring.output_encoding') : 'NONE') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>mbstring.language</th>\n";
echo "<td>" . (ini_get('mbstring.language') ? ini_get('mbstring.language') : 'NONE') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>mbstring.substitute_character</th>\n";
echo "<td>" . (ini_get('mbstring.substitute_character') ? ini_get('mbstring.substitute_character') : 'NONE') . "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "<h2>Session</h2>\n";
echo "<table summary=\"Session\">\n";
echo "<tr>\n";
echo "<th>session.use_trans_sid</th>\n";
echo "<td>" . (ini_get('session.use_trans_sid') ? 'ON' : 'OFF') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>session.use_cookies</th>\n";
echo "<td>" . (ini_get('session.use_cookies') ? 'ON' : 'OFF') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>session.use_only_cookies</th>\n";
echo "<td>" . (ini_get('session.use_only_cookies') ? 'ON' : 'OFF') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>session.auto_start</th>\n";
echo "<td>" . (ini_get('session.auto_start') ? 'ON' : 'OFF') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>session.cache_limiter</th>\n";
echo "<td>" . (ini_get('session.cache_limiter') ? ini_get('session.cache_limiter') : 'NONE') . "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "<h2>Other</h2>\n";
echo "<table summary=\"Other\">\n";
echo "<tr>\n";
echo "<th>register_globals</th>\n";
echo "<td>" . (ini_get('register_globals') ? 'ON' : 'OFF') . "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<th>magic_quotes_gpc</th>\n";
echo "<td>" . (ini_get('magic_quotes_gpc') ? 'ON' : 'OFF') . "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "<h2>Details</h2>\n";
echo "<table summary=\"Details\">\n";
echo "<tr>\n";
echo "<th>Directive</th>\n";
echo "<th>global_value</th>\n";
echo "<th>local_value</th>\n";
echo "<th>access</th>\n";
echo "</tr>\n";

foreach (ini_get_all() as $key => $value) {
	echo "<tr>\n";
	echo "<td>" . htmlspecialchars($key, ENT_QUOTES) . "</td>\n";
	echo "<td>" . htmlspecialchars($value['global_value'], ENT_QUOTES) . "</td>\n";
	echo "<td>" . htmlspecialchars($value['local_value'], ENT_QUOTES) . "</td>\n";
	echo "<td>" . htmlspecialchars($value['access'], ENT_QUOTES) . "</td>\n";
	echo "</tr>\n";
}

echo "</table>\n";
echo "</body>\n";
echo "</html>\n";

?>