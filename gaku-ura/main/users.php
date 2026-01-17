<?php
#gaku-ura9.6.5
require __DIR__ .'/../conf/conf.php';
require __DIR__ .'/../conf/users.php';
function is_editable(string $fname):bool{
	if(stripos(mime_content_type($fname),'text/')===0) return true;
	$f = strtolower(basename($fname));
	foreach (['txt','htaccess','php','py','rb','conf','ini','log','css','html','js','csv','tsv','c','cpp','cxx','gkrs','pl'] as $k){
		if(str_ends_with($f,'.'.$k)) return true;
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
function file_perm(string $file):string{
	return substr(sprintf('%o',fileperms($file)),-3);
}
function perm_opt(array $perm_list, string $now_p):string{
	$r = '<label>ﾊﾟｰﾐｯｼｮﾝ<select name="perm">';
	foreach ($perm_list as $k=>$v){
		if ($k === 'no'){
			$r.='<option value="no">'.$now_p.'(変更しない)</option>';
		} else {
			$r .= sprintf('<option value="%s">%o</option>', $k, $v);
		}
	}
	return $r.'</select></label>';
}
function form_fmt(array $hidden, string $content):string{
	$f = '<form method="POST" action="" id="form">';
	foreach ($hidden as $k=>$v){
		$f .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
	}
	return $f.$content.'</form>';
}

function main(string $from):int{
	$conf = new GakuUra();
	$user = new GakuUraUser($conf);
	$api_args = [];
	$user_dir = $user->user_dir;
	$css_file = $user_dir.'/css';
	$html_file = $user_dir.'/html/'.$from.'.html';
	$js_file = $user_dir.'/js/'.$from.'.js';
	if(!is_file($html_file)) $conf->not_found();
	if(isset($_POST['submit_type'])) $_POST['submit']=$_POST['submit_type'];
	if(isset($_POST['submit'])) $submit=$_POST['submit'];
	$login_data = $user->login_check();
	$replace = ['GAKU_URA_VERSION'=>GAKU_URA_VERSION, 'WARNING'=>''];
	$is_admin = false;
	$is_file_list = false;
	if ($from === 'home'){
		/* ユーザーホーム */
		$conf->content_type('text/html');
		if ($login_data['result'] === false){
			header('Location:./login/');
			exit;
		}
		if ($conf->url_param === ''){
			if (list_isset($_POST,['submit','session_token']) && $_POST['submit']==='logout' && $conf->check_csrf_token('user_home',$_POST['session_token'],true)){
				unset($_SESSION[GakuUraUser::SKEY_ID]);
				unset($_SESSION[GakuUraUser::SKEY_PASSWD]);
				header('Location:./');
				exit;
			} elseif (list_isset($_POST,['session_token','name','mail','passwd','new_passwd','profile']) && $conf->check_csrf_token('user_home',$_POST['session_token'],true)){
				$new_user_data = $login_data['user_data'];
				$name = row(h(GakuUraUser::h($_POST['name'])));
				$mail = row(h(GakuUraUser::h($_POST['mail'])));
				$passwd = row(h(GakuUraUser::h($_POST['passwd'])));
				$new_passwd = row(h(GakuUraUser::h($_POST['new_passwd'])));
				$profile = str_replace("\n", '&#10;', h(GakuUraUser::h($_POST['profile'])));
				if (pass($passwd) === $login_data['user_data']['passwd']){
					if(not_empty($name)&&strlen($name)<32) $new_user_data['name']=$name;
					if(not_empty($new_passwd)) $new_user_data['passwd']=pass($new_passwd);
					if($user->user_exists('',$mail)===0) $new_user_data['mail']=$mail;
					$new_user_data['profile'] = $profile;
					$user->change_user_data($conf, $new_user_data);
				}
				header('Location:./');
				exit;
			} else {
				$replace['SESSION_TOKEN'] = $conf->set_csrf_token('user_home');
				$replace['NAME'] = $login_data['user_data']['name'];
				$replace['MAIL'] = $login_data['user_data']['mail'];
				$replace['PROFILE'] = $login_data['user_data']['profile'];
				$replace['ADMIN'] = $login_data['user_data']['admin'];
				$html = file_get_contents($html_file);
				if((int)$login_data['user_data']['admin']>=$user->admin_revel) $is_admin=true;
			}
		} else {
			$i = $user->user_exists(h(GakuUraUser::h($conf->url_param)));
			if($i===0) $conf->not_found();
			$u = $user->user_data_convert(explode("\t", get($user->user_list_file, $i+1)));
			$html = '<h1>'.$u['name'].'</h1>権限:'.$u['admin'].'<div class="profile">'.to_html(str_replace('&#10;',"\n",$u['profile'])).'</div><p><br></p><p><a href="./">ユーザーホームへ</a></p>';
			$conf->html($u['name'].'-', '', $html, $css_file, $js_file);
			return 0;
		}
	} elseif ($from === 'admin'){
		/* 管理機能 */
		if ($login_data['result'] === false){
			header('Location:../login/');
			exit;
		}
		if((int)$login_data['user_data']['admin']<$user->admin_revel) $conf->not_found();
		$is_edit_mode = false;
		$menu = isset($_GET['Menu'])?$_GET['Menu']:'';
		$admin_dir = $user->own_dir[(int)$login_data['user_data']['admin']];
		$c_root = realpath($conf->d_root.$admin_dir);
		$current_dir = $c_root;
		$uri_dir = '';
		$perm_list = ['no'=>0,'DIR'=>0755,'CGI'=>0745,'STATIC'=>0644,'PRIVATE'=>0600];
		$rm_option = '<label><input type="radio" name="remove" value="no" checked>削除しない</label> <label><input type="radio" name="remove" value="yes">削除する</label>';
		$replace['TITLE'] = '管理機能';
		$replace['EDIT_AREA'] = '';
		$replace['FILE_LIST'] = '';
		$replace['TOP'] = '';
		$replace['CONFIG'] = '';
		if ((int)$login_data['user_data']['admin']>=4 && str_starts_with($conf->config_file,$c_root)){
			$replace['CONFIG'] = '<a href="?Dir='.str_replace($c_root.'/','',str_replace('/'.basename($conf->config_file),'',$conf->config_file)).'&File='.basename($conf->config_file).'&Menu=edit">設定</a>';
		}
		if (str_starts_with($conf->d_root,$c_root)){
			$api_args['d_root'] = ($c_root===$conf->d_root?'':str_replace($c_root.'/','',$conf->d_root));
			$api_args['u_root'] = substr($conf->u_root,0,-1);
			$replace['TOP'] = '<a href="?Dir='.$api_args['d_root'].'">ドキュメントルート</a>';
		}
		//現在位置を特定
		if (isset($_GET['Dir']) && strpos($_GET['Dir'],'..')===false && is_dir($c_root.'/'.h($_GET['Dir']))){
			$uri_dir = h($_GET['Dir']);
			if($uri_dir!=='') $current_dir=realpath($c_root.'/'.$uri_dir);
		}

		//投稿
		if (isset($submit,$_POST['session_token']) && $conf->check_csrf_token('admin__'.$_POST['submit'],$_POST['session_token'],true)){
			if ($submit==='edit_file' && list_isset($_POST, ['name','new_name','perm']) && is_file($current_dir.'/'.h($_POST['name'])) && isset($perm_list[$_POST['perm']])){
				$path = $current_dir.'/'.h($_POST['name']);
				//削除
				if ($path !== $conf->config_file || (int)$login_data['user_data']['admin'] === 4){
					if (isset($_POST['remove']) && $_POST['remove']==='yes'){
						unlink($path);
						header('Location:./?Dir='.$uri_dir);
						exit;
					} else {
						if($_POST['perm']!=='no') chmod($path,$perm_list[$_POST['perm']]);
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
			} elseif ($submit==='edit_dir' && list_isset($_POST, ['new_name','perm']) && isset($perm_list[$_POST['perm']])){
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
						try{
							chmod($path, $perm_list[$_POST['perm']]);
						}catch(Exception $e){}
					}
					if (not_empty($_POST['new_name']) && !file_exists($up_to_dir.'/'.h($_POST['new_name']))){
						try{
							rename($path, $up_to_dir.'/'.h($_POST['new_name']));
						}catch(Exception $e){}
					}
				}
				header('Location:?Dir='.$up_to);
				exit;
			} elseif ($submit==='new' && list_isset($_POST,['new','name'])){
				$name = str_replace('..','', str_replace('/','',h($_POST['name'])));
				if (in_array($_POST['new'],['.htaccess','/sitemap.xml','/robots.txt']) && !str_starts_with($conf->d_root,$c_root)){
					$conf->not_found(false, '権限がありません');
				}
				if ($_POST['new'] === '.htaccess'){
					touch($current_dir.'/.htaccess');
				} elseif ($_POST['new']==='/sitemap.xml' && strpos($conf->d_root,$c_root)!==false){
					$url_list = [''];
					foreach (scandir($conf->data_dir.'/home/html') as $f){
						if (preg_match('/(\.(html|md))$/',$f) === 1){
							$m = preg_replace('/(\.(html|md))$/', '', $f);
							if ($m!=='index' && preg_match('/^[0-9]+$/',$m)!==1){
								$url_list[] = '?Page='.$m;
							}
						}
					}
					if (isset($conf->config['login.enable']) && (int)$conf->config['login.enable']===1){
						$url_list[] = 'users/';
					}
					$t = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'."\n";
					foreach (array_unique($url_list) as $i){
						$t .= '<url><loc>'.$conf->domain.$i.'</loc></url>'."\n";
					}
					file_put_contents($conf->d_root.'/sitemap.xml', $t.'</urlset>'."\n", LOCK_EX);
					header('Location:?Dir='.str_replace($c_root,'',$conf->d_root).'&File=sitemap.xml&Menu=edit');
					exit;
				} elseif ($_POST['new']==='/robots.txt' && strpos($conf->d_root, $c_root)!==false){
					file_put_contents($conf->d_root.'/robots.txt', "User-agent: *\nSitemap : {$conf->domain}sitemap.xml\n", LOCK_EX);
					header('Location:?Dir='.str_replace($c_root,'',$conf->d_root).'&File=robots.txt&Menu=edit');
					exit;
				} elseif (not_empty($name) && !file_exists($current_dir.'/'.$name)){
					if ($_POST['new'] === 'folder'){
						mkdir($current_dir.'/'.$name);
					} elseif ($_POST['new'] === 'file'){
						touch($current_dir.'/'.$name);
					} else {
						if (strpos($name,'.'.$_POST['new'])===false && in_array($_POST['new'],['php','html','css','js','pl','py'],true)){
							$name .= '.'.$_POST['new'];
						}
						$new_path = $current_dir.'/'.str_replace('..','.',$name);
						$cl = [
							'php'=>'<?php',
							'html'=>'<!DOCTYPE html>'."\n".'<html lang="ja">'."\n".'<head>'."\n".'<meta http-equiv="content-type" content="text/html;charset=UTF-8">'."\n".'<meta name="viewport" content="width=device-width,initial-scale=1.0">'."\n".'<title></title>'."\n".'<style></style>'."\n".'</head>'."\n".'<body>'."\n".'<h1></h1>'."\n".'</body>'."\n".'</html>',
							'css'=>'/*'."\n".'gaku-uraを使用する場合は以下の機能が適用されます'."\n".'#!include [lib_name.css]; (data/dafault/lib/css の中を使う場合)'."\n".'@import は自動的に順序を保持してCSSの先頭に移動します'."\n".'*/',
							'js'=>'/*'."\n".'gaku-uraを使用する場合は以下の機能が適用されます'."\n".'#!include [lib_name].js; (data/default/lib/js の中を使う場合)'."\n".'*/',
							'pl'=>'#!/usr/bin/env perl'."\n".'use strict;'."\n".'use warnings;'."\n".'print "content-type:text/html;charset=UTF-8\n\n";',
							'py'=>'#!/usr/bin/env python3'."\n".'import cgi'."\n".'print("content-type:text/html;charset=UTF-8\n")'];
						if ($_POST['new']==='php' && $current_dir===__DIR__){
							$cl['php'] .= "\n".'require __DIR__ .\'/../conf/conf.php\';'."\n".'function main():int{'."\n\t".'$conf = new GakuUra();'."\n\t".'return 0;'."\n".'}';
						}
						file_put_contents($new_path, $cl[$_POST['new']]."\n", LOCK_EX);
						if(in_array($_POST['new'],['pl','py'],true)) chmod($new_path, 0745);
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
							if (str_ends_with($fname,'.cgi') || str_ends_with($fname,'.pl') || str_ends_with($fname,'.rb')){
								chmod($path, 0745);
							} else {
								chmod($path, $perm_list['STATIC']);
							}
						}
					}
				}
				header('Location:./?Dir='.$uri_dir);
				exit;
			} elseif ($submit === 'user_list'){
				//入力が可変長なので、ログインデータで毎回チェックする
				foreach (get_rows($user->user_list_file, 2) as $row){
					$d = $user->user_data_convert(explode("\t", $row));
					//自分より権限が下か自分が最高権限か自分自身
					if ($d['admin']<$login_data['user_data']['admin'] || (int)$login_data['user_data']['admin']===4 || (int)$login_data['user_data']['id']===(int)$d['id']){
						foreach ($user->user_list_keys as $k){
							if ($k==='id') continue;
							if (isset($_POST[$k.$d['id']])){
								if (in_array($k,['name','enable','admin','passwd'],true) && !not_empty($_POST[$k.$d['id']])){
									continue;
								}
								if ($k==='enable' || $k==='admin'){
									$_POST[$k.$d['id']] = (int)$_POST[$k.$d['id']];
								}
								if ($k === 'profile'){
									$_POST[$k.$d['id']] = str_replace("\n", '&#10;', $_POST[$k.$d['id']]);
								} elseif ($k === 'admin'){
									if ($_POST[$k.$d['id']]<0||$_POST[$k.$d['id']]>4 || ((int)$login_data['user_data']['admin']!==4&&$_POST[$k.$d['id']]>=$login_data['user_data']['admin'])){
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
			} else {
				$conf->form_die();
			}
			header('Location:'.$conf->here);
			exit;
		}
		//ファイルがある
		if (isset($_GET['File']) && strpos($_GET['File'],'..')===false && strpos($_GET['File'], '/')===false && is_file($current_dir.'/'.h($_GET['File']))){
			$current_file = $current_dir.'/'.h($_GET['File']);
			if (!isset($_GET['download'])){
				$is_edit_mode = true;
				if ($current_file===$user->user_list_file){
					//ユーザー管理
					$menu = 'user';
				} elseif ($menu==='edit' || is_editable($current_file)){
					//編集
					$replace['TITLE'] = str_replace($c_root,'',$current_file);
					$is_file_list = true;
					$f = '<p><a href="?Dir='.$uri_dir.'" id="exit">戻る</a><label>名前<input type="text" name="new_name" value="'.basename($current_file).'" placeholder="変更なし"></label> ';
					$f .= perm_opt($perm_list,file_perm($current_file)).$rm_option.'<label><button type="submit" name="submit_type" value="edit_file">保 存</button></label></p>';
					if (is_editable($current_file)){
						$content = file_get_contents($current_file);
						$f .= '<p><label><textarea rows="25" name="content" id="text">'.str_replace("\t",'&#9;',preg_replace('/\r\n|\r|\n/','&#10;',h($content))).'</textarea></label></p>';
					} elseif (stripos(mime_content_type($current_file), 'image/') !== false){
						$im = getimagesize($current_file);
						$f .= '<p><img style="max-width:200px;height:auto;" width="'.$im[0].'px" height="'.$im[1].'px" src="data:'.mime_content_type($current_file).';base64,'.base64_encode(file_get_contents($current_file)).'"></p>';
					}
					$replace['EDIT_AREA'] = form_fmt(['session_token'=>$conf->set_csrf_token('admin__edit_file'),'name'=>basename($current_file)], $f);
					$replace['EDIT_AREA'] .= '<p><a href="?Dir='.$uri_dir.'&File='.basename($current_file).'&download">ダウンロードする</a></p><p><br></p>';
				} else {
					$is_edit_mode = false;
				}
			}
			if (!$is_edit_mode){
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
		} elseif (not_empty($uri_dir) && $menu==='edit'){
			//ディレクトリの編集
			$is_edit_mode = true;
			$replace['TITLE'] = 'ディレクトリ"'.basename($current_dir).'"を編集';
			$up_to = '';
			if ($uri_dir !== ''){
				$flist = explode('/', $uri_dir);
				$up_to = implode('/', array_slice($flist,0,count($flist)-1));
			}
			$is_file_list = true;
			$f = '<p><a href="?Dir='.$up_to.'" id="exit">戻る</a><label>名前<input type="text" name="new_name" value="'.basename($current_dir).'" placeholder="変更なし"></label>';
			$f .= perm_opt($perm_list, file_perm($current_dir)).$rm_option.'<label><button type="submit" name="submit_type" value="edit_dir">保 存</button></label></p>';
			$replace['EDIT_AREA'] = form_fmt(['session_token'=>$conf->set_csrf_token('admin__edit_dir')], $f);
		} elseif (!$is_edit_mode){
			$replace['SESSION_TOKEN'] = $conf->set_csrf_token('admin__new');
			$replace['FILE_LIST'] .= '<tr><td colspan="5">';
			if ($uri_dir === ''){
				$replace['FILE_LIST'] .= '(TOP)';
			} else {
				$flist = explode('/', $uri_dir);
				$l = count($flist)-1;
				$replace['FILE_LIST'] .= '<a href="./">(TOP)</a>';
				for ($i = 0;$i < $l;++$i){
					$replace['FILE_LIST'] .= '/<a href="?Dir='.implode('/',array_slice($flist,0,$i+1)).'">'.$flist[$i].'</a>';
				}
				$replace['FILE_LIST'] .= '/'.$flist[$l];
			}
			$replace['FILE_LIST'] .= '</td></tr>';
			//先頭の/禁止
			$u_dir = $uri_dir;
			if($u_dir!=='') $u_dir.='/';
			$files = array_diff(scandir($current_dir,SCANDIR_SORT_NONE),['.','..']);
			file_sort($files, $current_dir);
			foreach ($files as $f){
				$file = $current_dir.'/'.$f;
				$fmt = '<tr><td><a href="?Dir=%s"%s>'.$f.'</a></td><td><a href="?Dir=%s&Menu=edit">編　集</a></td><td>%s '.file_perm($file).'</td><td>'.date('Y-m/d H:i',filemtime($file)).'</td></tr>';
				if (is_dir($file)){
					$replace['FILE_LIST'] .= sprintf($fmt, $u_dir.$f,' class="dir"',$u_dir.$f,count(scandir($file))-2 .'item');
				} else {
					$replace['FILE_LIST'] .= sprintf($fmt, $uri_dir.'&File='.$f.(is_editable($file)?'&Menu=edit':''),'',$uri_dir.'&File='.$f,filesize($file)/1000 .'kB '.mime_content_type($file));
				}
			}
		}
		if ($menu === 'user'){
			//ユーザー管理
			$is_edit_mode = true;
			$replace['TITLE'] = '他のユーザーを管理';
			$is_file_list = true;
			$replace['EDIT_AREA'] = '<p>編集可能なユーザーの一覧を表示します。</p>';
			$f = '<p><a href="?Dir='.$uri_dir.'" id="exit">戻る</a>　<label><button type="submit" name="submit" value="user_list">保 存</button></label></p><table><thead><tr>';
			foreach ($user->user_list_keys as $k){
				$f .= '<th>'.$k.'</th>';
			}
			$f .= '</tr></thead>';
			foreach (get_rows($user->user_list_file, 2) as $row){
				$d = $user->user_data_convert(explode("\t", $row));
				//自分より下の権限か最高権限か自分自身
				if ($d['admin']<$login_data['user_data']['admin'] || (int)$login_data['user_data']['admin']===4 || (int)$login_data['user_data']['id']===(int)$d['id']){
					if ((int)$login_data['user_data']['id'] === (int)$d['id']){
						$f .= '<tr id="my">';
					} else {
						$f .= '<tr>';
					}
					foreach ($user->user_list_keys as $k){
						if ($k === 'id'){
							$f .= '<td style="min-width:2em;">'.$d[$k].'</td>';
						} elseif ($k === 'passwd'){
							$f .= '<td><input type="text" name="'.$k.$d['id'].'" placeholder="変更なし"></td>';
						} else {
							$f .= '<td><input type="text" name="'.$k.$d['id'].'" value="'.$d[$k].'"';
							if($k!=='mail'&&$k!=='profile') $f.=' placeholder="変更なし"';
							$f .= '></td>';
						}
					}
					$f .= '</tr>';
				}
			}
			$f .= '</table>';
			$replace['EDIT_AREA'] .= form_fmt(['session_token'=>$conf->set_csrf_token('admin__user_list')], $f);
			if (strpos($user->user_list_file, $c_root) !== false){
				$replace['EDIT_AREA'] .= '<p><a href="?Dir='.dirname(str_replace($c_root.'/','',$user->user_list_file)).'&File='.basename($user->user_list_file).'&download">ダウンロードする</a></p>';
			}
			$replace['EDIT_AREA'] .= 'enableを0にするとそのユーザーはログイン出来なくなりますが、削除にはなりません。';
		}
		if (!$is_edit_mode && $menu==='edit'){
			header('Location:?Dir='.$uri_dir);
			exit;
		}
	} elseif ($from === 'login'){
		/* ログイン */
		$conf->content_type('text/html');
		if ($login_data['result'] === true){
			header('Location:../');
			exit;
		}
		if (list_isset($_POST,['name','passwd','session_token']) && $conf->check_csrf_token('login',$_POST['session_token'],true)){
			foreach (get_rows($user->user_list_file, 2) as $row){
				$user_data = $user->user_data_convert(explode("\t", $row));
				if ($user_data['name']===h($_POST['name']) && $user_data['passwd']===pass(h($_POST['passwd']))){
					session_regenerate_id(true);
					$_SESSION[GakuUraUser::SKEY_ID] = $user_data['id'];
					$_SESSION[GakuUraUser::SKEY_PASSWD] = $user_data['passwd'];
					if (isset($_SESSION[GakuUraUser::SKEY_FROM])){
						header('Location:'.$_SESSION[GakuUraUser::SKEY_FROM]);
					} else {
						header('Location:../');
					}
					exit;
				}
			}
			$replace['WARNING'] = 'ユーザー名またはパスワードが不正です。';
		}
		$replace['SESSION_TOKEN'] = $conf->set_csrf_token('login');
	} elseif ($from === 'regist'){
		/* 新規登録 */
		$conf->content_type('text/html');
		if (!isset($conf->config['login.regist']) || (int)$conf->config['login.regist']===0){
			$conf->not_found(false, 'このサイトでは、新規登録は受け付けていません。');
		}
		if (list_isset($_POST,['name','mail','passwd','session_token']) && $conf->check_csrf_token('regist',$_POST['session_token'],true)){
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
	}
	$html = file_get_contents($html_file);
	remove_comment_rows($html, '<!--','-->');
	if ($is_admin){
		$html = str_replace('<admin>','',str_replace('</admin>','',$html));
	} else {
		remove_comment_rows($html, '<admin>','</admin>');
	}
	if ($from === 'admin'){
		if ($is_file_list){
			remove_comment_rows($html, '<file_list>','</file_list>');
		} else {
			$html = str_replace('<file_list>','',str_replace('</file_list>','',$html));
		}
	}
	foreach ($replace as $k=>$v){
		$html = str_replace('{'.$k.'}', GakuUra::h($v), $html);
	}
	$title = subrpos('<h1>', '</h1>', $html);
	if ($api_args){
		$html .= '<p style="display:none;" ';
		foreach ($api_args as $k=>$v){
			$html .= $k.'="'.$v.'" ';
		}
		$html .= 'id="gaku-ura_args">';
	}
	return $conf->html($title.'-', '', $html, $css_file, $js_file);
}

