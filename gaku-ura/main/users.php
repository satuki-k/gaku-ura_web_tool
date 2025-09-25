<?php
/*
 * gaku-ura9.5.14
*/
require __DIR__ .'/../conf/conf.php';
require __DIR__ .'/../conf/users.php';

function get_ftype(string $file):string{
	if (is_dir($file)){
		return 'dir';
	}
	$m = mime_content_type($file);
	$f = strtolower(basename($file));
	foreach (['php','css','html','js','py','rb','conf','ini','htaccess','zip','csv','tsv','txt','image','mpeg','pl'] as $t){
		if (str_ends_with($f, '.'.$t) || stripos($m, $t) !== false){
			return $t;
		}
	}
	return 'unknown';
}

function is_editable(string $fname):bool{
	$f = strtolower(basename($fname));
	if (stripos(mime_content_type($fname), 'text/') !== false){
		return true;
	}
	foreach (['txt','htaccess','php','py','rb','conf','ini','log','css','html','js','csv','tsv','c','cpp','cxx','gkrs','pl'] as $k){
		if (str_ends_with($f, '.'.$k)){
			return true;
		}
	}
	return false;
}
function file_sort(array &$file_list, string $current_dir):void{
	$fols = [];
	$fils = [];
	foreach ($file_list as $f){
		if (is_dir($current_dir.'/'.$f)){
			$fols[] = $f;
		} else {
			$fils[] = $f;
		}
	}
	natsort($fols);
	natsort($fils);
	$file_list = array_merge($fols, $fils);
}

function main(string $from):int{
	$conf = new GakuUra();
	$user = new GakuUraUser($conf);
	$user_dir = $user->user_dir;
	$css_file = $user_dir.'/css';
	$html_file = $user_dir.'/html/'.$from.'.html';
	$js_file = $user_dir.'/js/'.$from.'.js';
	if (!file_exists($html_file)){
		$conf->not_found();
	}
	$login_data = $user->login_check();

	switch ($from){
		/* ユーザーホーム */
		case 'home':
		$conf->content_type('text/html');
		if ($login_data['result'] === false){
			header('Location:./login/');
			exit;
		}
		if ($conf->url_param === ''){
			if (list_isset($_POST, ['submit','session_token']) && $_POST['submit']==='logout' && $conf->check_csrf_token('user_home', $_POST['session_token'], true)){
				$_SESSION['gaku-ura_login:id'] = '';
				header('Location:./');
				exit;
			} elseif (list_isset($_POST, ['session_token','name','mail','passwd','new_passwd','profile']) && $conf->check_csrf_token('user_home', $_POST['session_token'], true)){
				$new_user_data = $login_data['user_data'];
				$name = row(h(GakuUraUser::h($_POST['name'])));
				$mail = row(h(GakuUraUser::h($_POST['mail'])));
				$passwd = row(h(GakuUraUser::h($_POST['passwd'])));
				$new_passwd = row(h(GakuUraUser::h($_POST['new_passwd'])));
				$profile = str_replace("\n", '&#10;', h(GakuUraUser::h($_POST['profile'])));
				if (pass($passwd) === $login_data['user_data']['passwd']){
					if (not_empty($name) && strlen($name) < 32){
						$new_user_data['name'] = $name;
					}
					if (not_empty($new_passwd)){
						$new_user_data['passwd'] = pass($new_passwd);
					}
					if ($user->user_exists('', $mail) === 0){
						$new_user_data['mail'] = $mail;
					}
					$new_user_data['profile'] = $profile;
					$user->change_user_data($conf, $new_user_data);
				}
				header('Location:./');
				exit;
			}
			$replace = [];
			$replace['SESSION_TOKEN'] = $conf->set_csrf_token('user_home');
			$replace['NAME'] = $login_data['user_data']['name'];
			$replace['MAIL'] = $login_data['user_data']['mail'];
			$replace['PROFILE'] = $login_data['user_data']['profile'];
			$replace['ADMIN'] = $login_data['user_data']['admin'];
			$html = file_get_contents($html_file);
			if ((int)$login_data['user_data']['admin'] >= $user->admin_revel){
				$html = str_replace('<admin>', '', $html);
				$html = str_replace('</admin>', '', $html);
			} else {
				remove_comment_rows($html, '<admin>', '</admin>');
			}
			foreach ($replace as $k => $v){
				$html = str_replace('{'.$k.'}', GakuUra::h($v), $html);
			}
			$title = subrpos('<h1>', '</h1>', $html);
			$conf->html($title.'-', '', $html, $css_file, $js_file, false);
		} else {
			$uid = $user->user_exists(h(GakuUraUser::h($conf->url_param)), '');
			if ($uid === 0){
				$conf->not_found();
			}
			$user_data = $user->user_data_convert(explode("\t", get($user->user_list_file, $uid +1)));
			$html = '<section><h1>'.$user_data['name'].'</h1>権限:'.$user_data['admin'].'</section><section><div class="profile">'.to_html(str_replace('&#10;', "\n", $user_data['profile'])).'</div></section><p><br></p><p><a href="./">ユーザーホームへ</a></p>';
			$conf->html($user_data['name'].'-', $user_data['profile'], $html, $css_file, $js_file, true);
		}
		break;

		/* 管理機能 */
		case 'admin':
		if ($login_data['result'] === false){
			header('Location:../login/');
			exit;
		}
		if ((int)$login_data['user_data']['admin'] < $user->admin_revel){
			$conf->not_found();
		}
		$is_edit_mode = false;
		$admin_dir = $user->own_dir[(int)$login_data['user_data']['admin']];
		$c_root = realpath($conf->d_root.$admin_dir);
		$current_dir = $c_root;
		$uri_dir = '';
		$replace = ['TITLE'=>'管理機能','EDIT_AREA'=>'','FILE_LIST'=>''];
		//現在位置を特定
		if (isset($_GET['Dir']) && strpos($_GET['Dir'], '..')===false && is_dir($c_root.'/'.h($_GET['Dir']))){
			$uri_dir = h($_GET['Dir']);
			if ($uri_dir !== ''){
				$current_dir = realpath($c_root.'/'.$uri_dir);
			}
		}

		//投稿
		if (isset($_POST['submit_type'])){
			$_POST['submit'] = $_POST['submit_type'];
		}
		if (list_isset($_POST, ['submit','session_token']) && $conf->check_csrf_token('admin__'.$_POST['submit'], $_POST['session_token'], true)){
			$perm_list = ['no'=>0,'DIR'=>0755,'CGI'=>0745,'STATIC'=>0644,'PRIVATE'=>0600];
			switch ($_POST['submit']){
				case 'edit_file':
				if (list_isset($_POST, ['name','new_name','perm']) && is_file($current_dir.'/'.h($_POST['name'])) && isset($perm_list[$_POST['perm']])){
					$path = $current_dir.'/'.h($_POST['name']);
					//削除
					if ($path !== $conf->config_file || (int)$login_data['user_data']['admin'] === 4){
						if (isset($_POST['remove']) && $_POST['remove']==='yes'){
							unlink($path);
							header('Location:./?Dir='.$uri_dir);
							exit;
						} else {
							if ($_POST['perm'] !== 'no'){
								chmod($path, $perm_list[$_POST['perm']]);
							}
							if (not_empty($_POST['new_name']) && !file_exists($current_dir.'/'.h($_POST['new_name']))){
								rename($path, $current_dir.'/'.h($_POST['new_name']));
								$path = $current_dir.'/'.h($_POST['new_name']);
								$_GET['File'] = h($_POST['new_name']);
							}
							if (isset($_POST['content'])){
								file_put_contents($path, $_POST['content'], LOCK_EX);
							}
						}
					}
					header('Location:?Dir='.$uri_dir.'&File='.$_GET['File'].'&Menu=edit');
					exit;
				}
				break;
				case 'edit_dir':
				if (list_isset($_POST, ['new_name','perm']) && isset($perm_list[$_POST['perm']])){
					$path = $current_dir;
					$up_to = '';
					$rflist = explode('/', $path);
					$up_to_dir = implode('/', array_slice($rflist, 0, count($rflist) -1));
					if ($uri_dir !== ''){
						$flist = explode('/', $uri_dir);
						$up_to = implode('/', array_slice($flist, 0, count($flist) -1));
					}
					//削除
					if (isset($_POST['remove']) && $_POST['remove']==='yes'){
						rmdir_all($path);
					} else {
						if ($_POST['perm'] !== 'no'){
							chmod($path, $perm_list[$_POST['perm']]);
						}
						if (not_empty($_POST['new_name']) && !file_exists($up_to_dir.'/'.h($_POST['new_name']))){
							rename($path, $up_to_dir.'/'.h($_POST['new_name']));
						}
					}
					header('Location:?Dir='.$up_to);
					exit;
				}
				break;
				case 'new':
				if (list_isset($_POST, ['new','name'])){
					$name = str_replace('..', '', str_replace('/', '', h($_POST['name'])));
					if ($_POST['new'] === '.htaccess'){
						touch($current_dir.'/.htaccess');
					} elseif ($_POST['new']==='/sitemap.xml' && strpos($conf->d_root, $c_root)!==false){
						$url_list = [''];
						foreach (scandir($conf->data_dir.'/home/html') as $f){
							if (strpos($f, '.html') !== false){
								$url_list[] = '?Page='.str_replace('.html', '', $f);
							}
							if (strpos($f, '.md') !== false){
								$url_list[] = '?Page='.str_replace('.md', '', $f);
							}
						}
						if (isset($conf->config['login.enable']) && (int)$conf->config['login.enable']===1){
							$url_list[] = 'users/';
						}
						$url_list = array_unique($url_list);
						$t = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
						foreach ($url_list as $i){
							$t .= '<url><loc>'.$conf->domain.$i.'</loc></url>';
						}
						$t .= '</urlset>'."\n";
						file_put_contents($conf->d_root.'/sitemap.xml', $t, LOCK_EX);
						header('Location:?File=sitemap.xml&Menu=edit');
						exit;
					} elseif ($_POST['new']==='/robots.txt' && strpos($conf->d_root, $c_root)!==false){
						file_put_contents($conf->d_root.'/robots.txt', "User-agent: *\nSitemap : {$conf->domain}sitemap.xml\n", LOCK_EX);
						header('Location:?File=robots.txt');
						exit;
					} elseif (not_empty($name) && !file_exists($current_dir.'/'.$name)){
						if ($_POST['new'] === 'folder'){
							mkdir($current_dir.'/'.$name);
						} elseif ($_POST['new'] === 'file'){
							touch($current_dir.'/'.$name);
						} else {
							if (strpos($name, '.'.$_POST['new'])===false && in_array($_POST['new'], ['php','html','css','js','pl','py'], true)){
								$name .= '.'.$_POST['new'];
							}
							$new_path = $current_dir.'/'.str_replace('..', '.', $name);
							$content = '';
							switch ($_POST['new']){
								case 'php':
								$content = '<?php';
								if ($current_dir === __DIR__){
									$content .= implode("\n", ['','require __DIR__ .\'/../conf/conf.php\';','function main():int{',"\t".'return 0;','}']);
								}
								break;
								case 'html':
								$content = implode("\n", ['<!DOCTYPE html>','<html lang="ja">','<head>','<meta http-equiv="content-type" content="text/html;charset=UTF-8">','<meta name="viewport" content="width=device-width,initial-scale=1.0">','<title>neu</title>','<style>','</style>','</head>','<body>','<h1>neu</h1>','</body>','</html>']);
								break;
								case 'css':
								$content = implode("\n", ['/* gaku-uraを使用する場合は以下の機能が適用されます */','/* #!include [lib_name.css]; (data/dafault/lib/css の中を使う場合) */','/* @import は自動的に順序を保持してCSSの先頭に移動します。 */']);
								break;
								case 'js':
								$content = implode("\n", ['// gaku-uraを使用する場合は以下の機能が適用されます。','// #!include [lib_name].js; (data/default/lib/js の中を使う場合)']);
								break;
								case 'pl':
								$content = implode("\n", ['#!/usr/bin/env perl','use strict;','use warnings;','print "content-type:text/html;charset=UTF-8\n\n";']);
								break;
								case 'py':
								$content = implode("\n", ['#!/usr/bin/env python3','import cgi','print("content-type:text/html;charset=UTF-8\n")']);
								break;
							}
							file_put_contents($new_path, $content."\n", LOCK_EX);
							if ($_POST['new']==='pl' || $_POST['new']==='py'){
								chmod($new_path, 0745);
							}
						}
					}
					//アップロード
					if (isset($_FILES)){
						foreach ($_FILES as $k=>$v){
							if (isset($_FILES[$k]['tmp_name']) && (int)$_FILES[$k]['error']===0 && is_file($_FILES[$k]['tmp_name']) && not_empty($_FILES[$k]['name'])){
								$ftname = $_FILES[$k]['tmp_name'];
								$fname = $_FILES[$k]['name'];
								$path = $current_dir.'/'.$fname;
								//権限昇華防止
								if ($path===$conf->config_file || $path===$user->user_list_file){
									//最高権限のみ
									if ((int)$login_data['user_data']['admin'] === 4){
										rename($ftname, $path);
									}
								} else {
									rename($ftname, $path);
								}
								//403防止
								chmod($path, $perm_list['STATIC']);
							}
						}
					}
				}
				header('Location:./?Dir='.$uri_dir);
				exit;
				break;
				case 'user_list':
				//入力が可変長なので、ログインデータで毎回チェックする
				foreach (get_rows($user->user_list_file, 2) as $row){
					$d = $user->user_data_convert(explode("\t", $row));
					//自分より権限が下か自分が最高権限か自分自身
					if ($d['admin']<$login_data['user_data']['admin'] || (int)$login_data['user_data']['admin']===4 || (int)$login_data['user_data']['id']===(int)$d['id']){
						foreach ($user->user_list_keys as $k){
							if ($k === 'id'){
								continue;
							}
							if (isset($_POST[$k.$d['id']])){
								if (in_array($k, ['name','enable','admin','passwd'], true) && !not_empty($_POST[$k.$d['id']])){
									continue;
								}
								if ($k==='enable' || $k==='admin'){
									$_POST[$k.$d['id']] = (int)$_POST[$k.$d['id']];
								}
								if ($k === 'profile'){
									$_POST[$k.$d['id']] = str_replace("\n", '&#10;', $_POST[$k.$d['id']]);
								} elseif ($k === 'admin'){
									if ($_POST[$k.$d['id']]<0 || $_POST[$k.$d['id']]>4 || ((int)$login_data['user_data']['admin']!==4 && $_POST[$k.$d['id']]>=$login_data['user_data']['admin'])){
										continue;
									}
								}
								if ($k === 'passwd'){
									$d[$k] = pass(row(h(GakuUra::h($_POST[$k.$d['id']]))));
								} else {
									$d[$k] = row(h(GakuUra::h($_POST[$k.$d['id']])));
								}
							}
						}
						$user->change_user_data($conf, $d);
					}
				}
				break;
				default:
				$conf->form_die();
				break;
			}
			header('Location:'.$conf->here);
			exit;
		}

		//ファイルがある
		if (isset($_GET['File']) && strpos($_GET['File'],'..')===false && strpos($_GET['File'], '/')===false && is_file($current_dir.'/'.h($_GET['File']))){
			$current_file = $current_dir.'/'.h($_GET['File']);
			//ユーザー管理
			if ($current_file===$user->user_list_file && !isset($_GET['download'])){
				$_GET['Menu'] = 'user';
			} elseif ((isset($_GET['Menu'])&&($_GET['Menu']==='edit')) || (is_editable($current_file)&&!isset($_GET['download']))){
				//編集
				$replace['TITLE'] = 'ファイル:'.basename($current_file).' を編集';
				$html = file_get_contents($html_file);
				remove_comment_rows($html, '<file_list>', '</file_list>');
				$replace['EDIT_AREA'] = '<form action="" method="POST" id="form">
<input type="hidden" name="session_token" value="'.$conf->set_csrf_token('admin__edit_file').'">
<input type="hidden" name="name" value="'.basename($current_file).'">
<p><a href="?Dir='.$uri_dir.'" id="exit">戻る</a>
	<label>名前<input type="text" name="new_name" value="'.basename($current_file).'" placeholder="変更なし"></label>
	<label>ﾊﾟｰﾐｯｼｮﾝ<select name="perm"><option value="no">'.substr(sprintf('%o', fileperms($current_file)), -3).' 変更しない</option>
	<option value="DIR">755</option><option value="CGI">745</option><option value="STATIC">644</option><option value="PRIVATE">600</option>
</select></label>
　<label><input type="radio" name="remove" value="no" checked>削除しない</label> <label><input type="radio" name="remove" value="yes">削除する</label>
　<label><button type="submit" name="submit_type" value="edit_file">保 存</button></label></p>';
				if (is_editable($current_file)){
					$content = file_get_contents($current_file);
					$replace['EDIT_AREA'] .= '<p><label><textarea rows="25" name="content" id="text">'.str_replace("\t", '&#9;', preg_replace('/\r\n|\r|\n/', '&#10;', h($content))).'</textarea></label></p>';
				} elseif (stripos(mime_content_type($current_file), 'image/') !== false){
					$im = getimagesize($current_file);
					$replace['EDIT_AREA'] .= '<p><img style="max-width:100px;height:auto;" width="'.$im[0].'px" height="'.$im[1].'px" src="data:'.mime_content_type($current_file).';base64,'.base64_encode(file_get_contents($current_file)).'"></p>';
				}
				$replace['EDIT_AREA'] .= '</form><p><a href="?Dir='.$uri_dir.'&File='.basename($current_file).'&download">ダウンロードする</a></p><p><br></p>';
			} else {
				header('Content-Description:File Transfer');
				$conf->content_type(mime_content_type($current_file));
				header('Content-Disposition:attachment;filename="'.basename($current_file).'"');
				header('Expires:0');
				header('Cache-Control:must-revalidate');
				header('Pragma:public');
				header('Content-Length:'.filesize($current_file));
				readfile($current_file);
				exit;
			}
			$is_edit_mode = true;
		} elseif (not_empty($uri_dir) && isset($_GET['Menu']) && $_GET['Menu']==='edit'){
			//ディレクトリの編集
			$is_edit_mode = true;
			$replace['TITLE'] = 'ディレクトリ:'.basename($current_dir).' を編集';
			$up_to = '';
			if ($uri_dir !== ''){
				$flist = explode('/', $uri_dir);
				$up_to = implode('/', array_slice($flist, 0, count($flist) -1));
			}
			$html = file_get_contents($html_file);
			remove_comment_rows($html, '<file_list>', '</file_list>');
			$replace['EDIT_AREA'] = '<form action="" method="POST" id="form">
<input type="hidden" name="session_token" value="'.$conf->set_csrf_token('admin__edit_dir').'">
<p><a href="?Dir='.$up_to.'" id="exit">戻る</a>
	<label>名前<input type="text" name="new_name" value="'.basename($current_dir).'" placeholder="変更なし"></label>
	<label>ﾊﾟｰﾐｯｼｮﾝ<select name="perm"><option value="no">'.substr(sprintf('%o', fileperms($current_dir)), -3).' 変更しない</option>
<option value="DIR">755</option><option value="CGI">745</option><option value="STATIC">644</option><option value="PRIVATE">600</option>
</select></label>
　<label><input type="radio" name="remove" value="no" checked>削除しない</label> <label><input type="radio" name="remove" value="yes">削除する</label>
　<label><button type="submit" name="submit_type" value="edit_dir">保 存</button></label>
</p></form><p><br></p>';
		} elseif (!isset($_GET['Menu']) || $_GET['Menu']!=='user'){
			$html = file_get_contents($html_file);
			$html = str_replace('<file_list>', '', str_replace('</file_list>', '', $html));
			$replace['SESSION_TOKEN'] = $conf->set_csrf_token('admin__new');
			if ($uri_dir === ''){
				$replace['FILE_LIST'] .= '<tr><td colspan="5"><b>ルート</b></td></tr>';
			} else {
				$flist = explode('/', $uri_dir);
				$up_to = implode('/', array_slice($flist, 0, count($flist) -1));
				$replace['FILE_LIST'] .= '<tr><td colspan="5"><a href="?Dir=">ルート</a>';
				for ($i = 0;$i < count($flist) -1;++$i){
					$replace['FILE_LIST'] .= '/<a href="?Dir='.implode('/', array_slice($flist, 0, $i+1)).'">'.$flist[$i].'</a>';
				}
				$replace['FILE_LIST'] .= '/'.$flist[count($flist) -1].'</td></tr>';
			}
			//先頭の/禁止
			$u_dir = $uri_dir;
			if ($u_dir !== ''){
				$u_dir .= '/';
			}
			$files = scandir($current_dir);
			file_sort($files, $current_dir);
			foreach ($files as $f){
				if ($f === '.' || $f == '..'){
					continue;
				}
				$file = $current_dir.'/'.$f;
				$mt = date('Y-m/d H:i', filemtime($file));
				if (is_dir($file)){
					$replace['FILE_LIST'] .= '<tr><td class="type_dir"><a href="?Dir='.$u_dir.$f.'">'.$f.'</a></td><td><a href="?Dir='.$u_dir.$f.'&Menu=edit">編　集</a></td><td>'.count(scandir($file))-2 .'ファイル</td><td>'.$mt.'</td></tr>';
				} else {
					$replace['FILE_LIST'] .= '<tr><td class="type_'.get_ftype($file).'"><a href="?Dir='.$uri_dir.'&File='.$f.(is_editable($file)?'&Menu=edit':'').'">'.$f.'</a></td><td><a href="?Dir='.$uri_dir.'&File='.$f.'&Menu=edit">編　集</a></td><td>'.filesize($file)/1000 .'kB '.mime_content_type($file).'</td><td>'.$mt.'</td></tr>';
				}
			}
		}
		if (isset($_GET['Menu']) && $_GET['Menu']==='user'){
			$is_edit_mode = true;

			//ユーザー管理
			$replace['TITLE'] = '他のユーザーを管理';
			$html = file_get_contents($html_file);
			remove_comment_rows($html, '<file_list>', '</file_list>');
			$replace['EDIT_AREA'] = '<p>編集可能なユーザーの一覧を表示します。</p>';
			$replace['EDIT_AREA'] .= '<form action="" method="POST" id="form"><input type="hidden" name="session_token" value="'.$conf->set_csrf_token('admin__user_list').'">';
			$replace['EDIT_AREA'] .= '<p><a href="?Dir='.$uri_dir.'" id="exit">戻る</a>　<label><button type="submit" name="submit" value="user_list">保 存</button></label></p><table><thead><tr>';
			foreach ($user->user_list_keys as $k){
				$replace['EDIT_AREA'] .= '<th>'.$k.'</th>';
			}
			$replace['EDIT_AREA'] .= '</tr></thead>';
			foreach (get_rows($user->user_list_file, 2) as $row){
				$d = $user->user_data_convert(explode("\t", $row));
				//自分より下の権限か最高権限か自分自身
				if ($d['admin']<$login_data['user_data']['admin'] || (int)$login_data['user_data']['admin']===4 || (int)$login_data['user_data']['id']===(int)$d['id']){
					if ((int)$login_data['user_data']['id'] === (int)$d['id']){
						$replace['EDIT_AREA'] .= '<tr id="my">';
					} else {
						$replace['EDIT_AREA'] .= '<tr>';
					}
					foreach ($user->user_list_keys as $k){
						if ($k === 'id'){
							$replace['EDIT_AREA'] .= '<td style="min-width:2em;">'.$d[$k].'</td>';
						} elseif ($k === 'passwd'){
							$replace['EDIT_AREA'] .= '<td><input type="text" name="'.$k.$d['id'].'" placeholder="変更なし"></td>';
						} else {
							$replace['EDIT_AREA'] .= '<td><input type="text" name="'.$k.$d['id'].'" value="'.$d[$k].'"';
							if ($k !== 'mail' && $k !== 'profile'){
								$replace['EDIT_AREA'] .= ' placeholder="変更なし"';
							}
							$replace['EDIT_AREA'] .= '></td>';
						}
					}
					$replace['EDIT_AREA'] .= '</tr>';
				}
			}
			$replace['EDIT_AREA'] .= '</table></form>';
			if (strpos($user->user_list_file, $c_root) !== false){
				$replace['EDIT_AREA'] .= '<p><a href="?Dir='.dirname(str_replace($c_root.'/','',$user->user_list_file)).'&File='.basename($user->user_list_file).'&download">ダウンロードする</a></p>';
			}
			$replace['EDIT_AREA'] .= '<p>idは必ず行番号-1です。adminは自分のより小さい0以上の整数です。enableが0でログイン出来なくなります。このファイルは<b>最高権限のユーザー</b>のみ上書きアップロード可能ですが最高権限が存在しない場合はFTP等を使用して最高権限ユーザーのadminを4にしてください。</p>';
		}

		if (!$is_edit_mode && isset($_GET['Menu']) && $_GET['Menu']==='edit'){
			header('Location:?Dir='.$uri_dir);
			exit;
		}

		//最終的な表示
		foreach ($replace as $k => $v){
			$html = str_replace('{'.$k.'}', GakuUra::h($v), $html);
		}
		$title = subrpos('<h1>', '</h1>', $html);
		$conf->html($title.'-', '', $html, $css_file, $js_file, false);
		break;

		/* ログイン */
		case 'login':
		$conf->content_type('text/html');
		if ($login_data['result'] === true){
			header('Location:../');
			exit;
		}
		$replace = ['WARNING'=>'','SESSION_TOKEN'=>''];
		if (list_isset($_POST, ['name','passwd','session_token']) && $conf->check_csrf_token('login', $_POST['session_token'], true)){
			foreach (get_rows($user->user_list_file, 2) as $row){
				$user_data = $user->user_data_convert(explode("\t", $row));
				if ($user_data['name']===h($_POST['name']) && $user_data['passwd']===pass(h($_POST['passwd']))){
					session_regenerate_id(true);
					$_SESSION['gaku-ura_login:id'] = $user_data['id'];
					$_SESSION['gaku-ura_login:passwd'] = $user_data['passwd'];
					header('Location:../');
					exit;
				}
			}
			$replace['WARNING'] = 'ユーザー名またはパスワードが不正です。';
		}
		$replace['SESSION_TOKEN'] = $conf->set_csrf_token('login');
		$html = file_get_contents($html_file);
		foreach ($replace as $k => $v){
			$html = str_replace('{'.$k.'}', $v, $html);
		}
		$title = subrpos('<h1>', '</h1>', $html);
		$conf->html($title.'-', '', $html, $css_file, '', false);
		break;

		/* 新規登録 */
		case 'regist':
		$conf->content_type('text/html');
		if (!isset($conf->config['login.regist']) || (int)$conf->config['login.regist']===0){
			$conf->not_found(false, 'このサイトでは、新規登録は受け付けていません。');
		}
		$replace = ['WARNING'=>'', 'SESSION_TOKEN'=>''];
		if (list_isset($_POST, ['name','mail','passwd','session_token']) && $conf->check_csrf_token('regist', $_POST['session_token'], true)){
			$name = row(h(GakuUraUser::h($_POST['name'])));
			$passwd = row(h(GakuUraUser::h($_POST['passwd'])));
			$mail = row(h(GakuUraUser::h($_POST['mail'])));
			if (not_empty($name) && not_empty($passwd)){
				if (strlen($name) < 32){
					if ($user->user_exists($name, $mail) === 0){
						$user->change_user_data($conf, ['name'=>$name,'mail'=>$mail,'passwd'=>pass($passwd),'admin'=>0,'enable'=>1]);
						header('Location:../../');
						exit;
					} else {
						$replace['WARNING'] = 'その名前かメールアドレスは既に登録済みです。';
					}
				} else {
					$replace['WARNING'] = '名前が長過ぎです。';
				}
			} else {
				$replace['WARNING'] = '名前またはパスワードが未入力です。';
			}
		}
		$replace['SESSION_TOKEN'] = $conf->set_csrf_token('regist');
		$html = file_get_contents($html_file);
		foreach ($replace as $k => $v){
			$html = str_replace('{'.$k.'}', $v, $html);
		}
		$title = subrpos('<h1>', '</h1>', $html);
		$conf->html($title.'-', '', $html, $css_file, $js_file, false);
		break;
	}
	return 0;
}
