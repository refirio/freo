<?php

/*********************************************************************

 freo | 初期化処理 (2012/09/30)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

ini_set('default_charset', 'UTF-8');
ini_set('mbstring.internal_encoding', 'UTF-8');
ini_set('mbstring.language', 'Japanese');
ini_set('mbstring.input_encoding', 'pass');
ini_set('mbstring.output_encoding', 'pass');
ini_set('mbstring.substitute_character', 'none');

ini_set('session.use_trans_sid', 0);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 0);
ini_set('session.auto_start', 0);
ini_set('session.cache_limiter', 'none');

if (ini_get('register_globals')) {
	exit('Fatal error');
}
if (ini_get('magic_quotes_gpc')) {
	$_GET     = freo_stripslashes($_GET);
	$_POST    = freo_stripslashes($_POST);
	$_REQUEST = freo_stripslashes($_REQUEST);
	$_SERVER  = freo_stripslashes($_SERVER);
	$_COOKIE  = freo_stripslashes($_COOKIE);
}

if (ob_get_level()) {
	ob_end_clean();
}

?>
