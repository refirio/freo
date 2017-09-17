<?php

/*********************************************************************

 freo | 設定ファイル (2017/09/03)

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

 @Link http://freo.jp/
 @Copyright(C) 2009-2017 freo.jp
 @Author refirio <info at refirio dot org>

*********************************************************************/

/********* 基本設定 *************************************************/

//設置URL
define('FREO_HTTP_URL', 'http://www.example.com/freo/');

//設置URL(SSL用)
define('FREO_HTTPS_URL', '');

//mod_rewriteへの対応(true ... 対応する / false ... 対応しない)
define('FREO_REWRITE_MODE', false);

/********* データベースの設定 ***************************************/

//データベースの種類(sqlite3 ... SQLite3 / sqlite2 ... SQLite2 / mysql ... MySQL)
define('FREO_DATABASE_TYPE', 'sqlite3');

//接続先(MySQL用)
define('FREO_DATABASE_HOST', '');

//ポート(MySQL用)
define('FREO_DATABASE_PORT', '');

//ユーザー名(MySQL用)
define('FREO_DATABASE_USER', '');

//パスワード(MySQL用)
define('FREO_DATABASE_PASSWORD', '');

//文字コード(MySQL用)
define('FREO_DATABASE_CHARSET', '');

//データベース格納ディレクトリ(SQLite用)
define('FREO_DATABASE_DIR', 'database/');

//データベース名
define('FREO_DATABASE_NAME', 'freo.db');

//テーブル名のプレフィックス
define('FREO_DATABASE_PREFIX', 'freo_');

/********* Cookieの設定 *********************************************/

//Cookieの有効期限
define('FREO_COOKIE_EXPIRE', 60 * 60 * 24 * 30);

/********* キャッシュの設定 *****************************************/

//キャッシュの作成(true ... 作成する / false ... 作成しない)
define('FREO_CACHE_MODE', false);

//キャッシュの対象
define('FREO_CACHE_TARGET', 'default,feed');

//キャッシュ格納ディレクトリ
define('FREO_CACHE_DIR', 'caches/');

//キャッシュの有効期限
define('FREO_CACHE_LIFETIME', 60);

/********* 画像処理の設定 *******************************************/

//ImageMagickの利用(true ... 利用する / false ... 利用しない)
define('FREO_IMAGEMAGICK_MODE', false);

//ImageMagickのパス
define('FREO_IMAGEMAGICK_PATH', '/usr/bin/convert');

/********* 絵文字変換の設定 *****************************************/

//絵文字の変換(true ... 変換する / false ... 変換しない)
define('FREO_PICTOGRAM_MODE', false);

//画像格納ディレクトリのURL
define('FREO_PICTOGRAM_IMAGE_URL', 'http://www.example.com/images/pictograms/');

/********* パスの設定 **********************************************/

//メインプログラムファイル格納ディレクトリ
define('FREO_MAIN_DIR', 'libs/');

//設定ファイル格納ディレクトリ
define('FREO_CONFIG_DIR', 'configs/');

//テンプレート格納ディレクトリ
define('FREO_TEMPLATE_DIR', 'templates/');

//コンパイル済みテンプレート格納ディレクトリ
define('FREO_TEMPLATE_COMPILE_DIR', 'templates_c/');

//メール本文格納ディレクトリ
define('FREO_MAIL_DIR', 'mails/');

//アップロードファイル格納ディレクトリ
define('FREO_FILE_DIR', 'files/');

//CSSファイル格納ディレクトリ
define('FREO_CSS_DIR', 'css/');

//JSファイル格納ディレクトリ
define('FREO_JS_DIR', 'js/');

/********* セットアップの設定 ***************************************/

//パーミッション自動設定の利用(true ... 利用する / false ... 利用しない)
define('FREO_PERMISSION_MODE', true);

?>
