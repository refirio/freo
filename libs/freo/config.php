<?php

/*********************************************************************

 freo | システム設定ファイル (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/********* 基本設定 *************************************************/

//初期化処理の利用(true ... 利用する / false ... 利用しない)
define('FREO_INITIALIZE_MODE', true);

//ルーティングの利用(true ... 利用する / false ... 利用しない)
define('FREO_ROUTING_MODE', false);

//セッション自動付加の利用(true ... 利用する / false ... 利用しない)
define('FREO_TRANSFER_MODE', true);

//対応プログラムとテンプレートが無いときの404エラーの利用(true ... 利用する / false ... 利用しない)
define('FREO_ERROR_MODE', true);

/********* ファイルの設定 ******************************************/

//起動ファイル名
define('FREO_MAIN_FILE', 'index.php');

//アスキーモードで扱うファイル
define('FREO_ASCII_FILE', '\.(txt|html|htm|log|js|css|ini|php|xml|htaccess|htpasswd)$');

//404 File Not Found エラーページ
define('FREO_ERROR_FILE', '');

/********* パーミッションの設定 *************************************/

//ディレクトリのパーミッション
define('FREO_PERMISSION_DIR', 0707);

//ファイルのパーミッション
define('FREO_PERMISSION_FILE', 0606);

//データファイルのパーミッション
define('FREO_PERMISSION_DATA', 0606);

//PHPファイルのパーミッション
define('FREO_PERMISSION_PHP', 0644);

//HTMLファイルのパーミッション
define('FREO_PERMISSION_HTML', 0644);

//CSSファイルのパーミッション
define('FREO_PERMISSION_CSS', 0644);

//JSファイルのパーミッション
define('FREO_PERMISSION_JS', 0644);

/********* セッションの設定 *****************************************/

//セッションCookieの有効期限
define('FREO_SESSION_LIFETIME', 0);

//セッションのキャッシュ制御
define('FREO_SESSION_CACHE', 'none');

/********* ワンタイムトークンの設定 *********************************/

//ワンタイムトークンの更新間隔
define('FREO_TOKEN_SPAN', 60 * 10);

/********* 文字列処理の設定 *****************************************/

//テキスト分割文字列
define('FREO_DIVIDE_MARK', '<!-- pagebreak -->');

//ダブルクォート変換文字列
define('FREO_CONFIG_QUOTE', '"');

/********* 画像処理の設定 *******************************************/

//GDのJpegの画質
define('FREO_GD_QUALITY', 85);

//ImageMagickのJpegの画質
define('FREO_IMAGEMAGICK_QUALITY', 90);

//携帯での画像の最大横幅
define('FREO_MOBILE_FILE_WIDTH', 240);

//携帯での画像の最大縦幅
define('FREO_MOBILE_FILE_HEIGHT', 480);

?>
