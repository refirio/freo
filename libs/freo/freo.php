<?php

/*********************************************************************

 freo | プログラム本体 (2012/09/30)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//外部ファイル読み込み
if (FREO_HTTP_URL == 'http://www.example.com/freo/') {
	require_once FREO_MAIN_DIR . 'freo/prepare.php';
}

require_once FREO_MAIN_DIR . 'smarty/Smarty.class.php';
require_once FREO_MAIN_DIR . 'freo/version.php';
require_once FREO_MAIN_DIR . 'freo/config.php';
require_once FREO_MAIN_DIR . 'freo/common.php';

if (FREO_INITIALIZE_MODE) {
	require_once FREO_MAIN_DIR . 'freo/initialize.php';
}
if (FREO_ROUTING_MODE) {
	require_once FREO_MAIN_DIR . 'freo/routing.php';
}
if (FREO_TRANSFER_MODE) {
	require_once FREO_MAIN_DIR . 'freo/transfer.php';
}
if (FREO_PICTOGRAM_MODE) {
	require_once FREO_MAIN_DIR . 'HTML/Emoji.php';
	require_once FREO_MAIN_DIR . 'freo/pictogram.php';
}

//セッション開始
freo_session();

//テンプレート設定
freo_smarty();

//データベース接続
freo_pdo();

//基本情報取得
freo_core();

//ブラウザ情報取得
freo_agent();

//受信データ正規化
freo_normalize();

//パラメーター取得
freo_parameter();

//ユーザー情報取得
freo_user();

//フィルター設定取得
freo_filter();

//登録情報取得
freo_refer();

//設定読み込み
freo_config();

//プラグイン設定読み込み
freo_plugin('config');

//プラグイン実行
freo_plugin('init');

//プラグイン実行
freo_plugin('page');

//メインプログラム読み込み
freo_require();

//プラグイン実行
freo_plugin('begin');

//メインプログラム実行
freo_execute();

//プラグイン実行
freo_plugin('end');

?>
