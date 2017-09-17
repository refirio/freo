<?php

/*********************************************************************

 freo | 管理画面 | フィルター入力 (2013/04/08)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_filter.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//並び順取得
		if ($_POST['filter']['sort'] != '') {
			$_POST['filter']['sort'] = mb_convert_kana($_POST['filter']['sort'], 'n', 'UTF-8');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_filter($_GET['id'] ? 'update' : 'insert', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$filter = $_POST['filter'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('admin/filter_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$filter = $_SESSION['input']['filter'];
		} elseif ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'filters WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$filter = $data;
			} else {
				freo_error('指定されたフィルターが見つかりません。', '404 Not Found');
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'filters');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}

			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$filter = array(
				'sort' => $sort
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'filter' => $filter
		)
	));

	return;
}

?>
