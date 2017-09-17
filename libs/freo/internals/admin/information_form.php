<?php

/*********************************************************************

 freo | 管理画面 | インフォメーション入力 (2011/04/23)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/validate_information.php';

/* メイン処理 */
function freo_main()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//並び順取得
		if ($_POST['information']['entry_id'] != '') {
			$_POST['information']['entry_id'] = mb_convert_kana($_POST['information']['entry_id'], 'n', 'UTF-8');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_information('update', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}
		}

		//インフォメーションテキスト取得
		if ($_POST['information']['text']) {
			list($excerpt, $more) = freo_divide($_POST['information']['text']);

			$_POST['information_text'] = array(
				'excerpt' => $excerpt,
				'more'    => $more
			);
		} else {
			$_POST['information_text'] = array();
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$information = $_POST['information'];
		} else {
			$_SESSION['input'] = $_POST;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('admin/information_preview');
			} else {
				//登録処理へ移動
				freo_redirect('admin/information_post?freo%5Btoken%5D=' . freo_token('create'));
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$information = $_SESSION['input']['information'];
		} else {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'informations WHERE id = :id');
			$stmt->bindValue(':id', 'default');
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$information = $data;
			} else {
				$information = array();
			}
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'information' => $information
		)
	));

	return;
}

?>
