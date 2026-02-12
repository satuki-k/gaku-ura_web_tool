<?php
#gaku-ura9.6.15
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
function file_sort(array &$files, string $c_dir):void{
	$d = [];
	$f = [];
	foreach($files as $i) is_dir($c_dir.'/'.$i)?$d[]=$i:$f[]=$i;
	natsort($d);
	natsort($f);
	$files = array_merge($d, $f);
}
function file_perm(string $f):string{
	return substr(sprintf('%o',fileperms($f)),-3);
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

function main(string $from):int{
	$conf = new GakuUra();
	$user = new GakuUraUser($conf);
	$html = $from;
	$user_dir = $user->user_dir;
	$api_args = [];
	if(isset($_POST['submit_type'])) $_POST['submit']=$_POST['submit_type'];
	if(isset($_POST['submit'])) $submit=$_POST['submit'];
	$is_async = isset($_GET['async']);
	$login = $user->login_check();
	$is_login = $login['result'];
	$user_data = $login['user_data']??[];
	$replace = ['GAKU_URA_VERSION'=>GAKU_URA_VERSION,'WARNING'=>'','FOR_ADMIN'=>''];
	if ($from === 'home'){
		/* ユーザーホーム */
		if (!$is_login){
			header('Location:./login/');
			exit;
		}
		if ($conf->url_param === ''){
			if (list_isset($_POST,['submit','session_token']) && $_POST['submit']==='logout' && $conf->check_csrf_token('user_home',$_POST['session_token'],true)){
				unset($_SESSION[GakuUraUser::SKEY_ID]);
				unset($_SESSION[GakuUraUser::SKEY_NAME]);
				unset($_SESSION[GakuUraUser::SKEY_PASSWD]);
				header('Location:./');
				exit;
			} elseif (list_isset($_POST,['session_token','name','mail','passwd','new_passwd','profile']) && $conf->check_csrf_token('user_home',$_POST['session_token'],true)){
				$_POST['profile'] = str_replace("\n", '&#10;', $_POST['profile']);
				$p = [];
				foreach(['name','mail','passwd','new_passwd','profile']as$k) $p[$k]=row(h(GakuUraUser::h($_POST[$k])));
				if (password_verify($p['passwd'],$user_data['passwd'])){
					if(not_empty($p['name'])&&strlen($p['name'])<32&&$user->user_exists($p['name'])===0) $user_data['name']=$p['name'];
					if(not_empty($p['new_passwd'])) $user_data['passwd']=password_hash($p['new_passwd'],PASSWORD_BCRYPT);
					if(filter_var($p['mail'],FILTER_VALIDATE_EMAIL)&&$user->user_exists('',$p['mail'])===0) $user_data['mail']=$p['mail'];
					$user_data['profile'] = $p['profile'];
					$_SESSION[GakuUraUser::SKEY_NAME] = $p['name'];
					$user->change_user_data($conf, $user_data);
				}
				header('Location:./');
				exit;
			} else {
				$replace['SESSION_TOKEN'] = $conf->set_csrf_token('user_home');
				foreach(['name','mail','profile','admin']as$k) $replace[strtoupper($k)]=$user_data[$k];
				$a = $conf->data_dir.'/users/html/home_admin.html';
				if ((int)$user_data['admin']>=$user->admin_revel && is_file($a)){
					$replace['FOR_ADMIN'] = str_replace('{NAME}',$replace['NAME'],file_get_contents($a));
				}
			}
		} else {
			$html = 'user_page';
			$i = $user->user_exists(h(GakuUraUser::h($conf->url_param)));
			if($i===0) $conf->not_found();
			$u = $user->user_data_convert(explode("\t", get($user->user_list_file, $i+1)));
			$replace['NAME'] = $u['name'];
			$replace['ADMIN'] = $u['admin'];
			$replace['PROFILE'] = to_html(str_replace('&#10;',"\n",$u['profile']));
		}
	} elseif ($from === 'admin'){
		/* 管理機能 */
		if (!$is_login){
			header('Location:../login/');
			exit;
		}
		if((int)$user_data['admin']<$user->admin_revel) $conf->not_found();
		$is_edit_mode = false;
		$menu = $_GET['Menu']??'';
		$admin_dir = $user->own_dir[(int)$user_data['admin']];
		$c_root = realpath($conf->d_root.$admin_dir);
		$current_dir = $c_root;
		$uri_dir = '';
		$perm_list = ['no'=>0,'DIR'=>0755,'CGI'=>0745,'CGI2'=>0755,'STATIC'=>0644,'STATIC2'=>0666,'MPRIVATE'=>0604,'PRIVATE'=>0600];
		$rm_option = '<label><input type="radio" name="remove" value="no" checked>削除しない</label> <label><input type="radio" name="remove" value="yes">削除する</label>';
		$replace['TITLE'] = '管理機能';
		$replace['TOP'] = '';
		$replace['CONFIG'] = '';
		foreach(['max_file_uploads']as$i) $api_args[$i]=ini_get($i);
		if ((int)$user_data['admin']>=4 && str_starts_with($conf->config_file,$c_root)){
			$b = basename($conf->config_file);
			$replace['CONFIG'] = '<a href="?Dir='.lreplace(rreplace($conf->config_file,'/'.$b),$c_root.'/').'&File='.$b.'&Menu=edit">設定</a>';
		}
		if (str_starts_with($conf->d_root,$c_root)){
			$api_args['d_root'] = ($c_root===$conf->d_root?'':lreplace($conf->d_root,$c_root.'/'));
			$api_args['u_root'] = substr($conf->u_root,0,-1);
			$replace['TOP'] = '<a href="?Dir='.$api_args['d_root'].'">ドキュメントルート</a>';
		}
		#現在位置を特定
		if (($_GET['Dir']??'')!=='' && strpos($_GET['Dir'],'..')===false){
			$u = h($_GET['Dir']);
			$d = realpath($c_root.'/'.$u);
			if (is_dir($d)){
				$uri_dir = $u;
				$current_dir = $d;
			} elseif ($is_async){
				return 1;
			}
		}

		#投稿
		if (isset($submit,$_POST['session_token']) && $conf->check_csrf_token('admin__'.$_POST['submit'],$_POST['session_token'],true)){
			if ($submit==='edit_file' && list_isset($_POST,['name','new_name','perm']) && is_file($current_dir.'/'.h($_POST['name'])) && isset($perm_list[$_POST['perm']])){
				$path = $current_dir.'/'.h($_POST['name']);
				#削除
				if ($path!==$user->user_list_file||$path!==$conf->config_file || (int)$user_data['admin']>=4){
					if (($_POST['remove']??'')==='yes'){
						unlink($path);
						header('Location:./?Dir='.$uri_dir);
						exit;
					} else {
						if($_POST['perm']!=='no') chmod($path,$perm_list[$_POST['perm']]);
						if (not_empty($_POST['new_name']) && !file_exists($current_dir.'/'.h($_POST['new_name']))){
							$n = h($_POST['new_name']);
							$p = $current_dir.'/'.$n;
							rename($path, $p);
							$path = $p;
							$_GET['File'] = $n;
						}
						if(isset($_POST['content'])) file_put_contents($path,$_POST['content'],LOCK_EX);
					}
				}
				header('Location:?Dir='.$uri_dir.'&File='.$_GET['File'].'&Menu=edit');
				exit;
			} elseif ($submit==='edit_dir' && list_isset($_POST,['new_name','perm']) && isset($perm_list[$_POST['perm']])){
				$path = $current_dir;
				$f = explode('/', $path);
				array_pop($f);
				$up_to_dir = implode('/', $f);
				#削除
				if (($_POST['remove']??'')==='yes'){
					rmdir_all($path);
				} else {
					if ($_POST['perm'] !== 'no'){
						try{
							chmod($path, $perm_list[$_POST['perm']]);
						}catch(Exception $e){}
					}
					$n = h($_POST['new_name']);
					if (not_empty($n) && !file_exists($up_to_dir.'/'.$n)){
						try{
							rename($path, $up_to_dir.'/'.$n);
						}catch(Exception $e){}
					}
				}
				$up_to = '';
				if ($uri_dir !== ''){
					$f = explode('/', $uri_dir);
					array_pop($f);
					$up_to = implode('/', $f);
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
				} elseif ($_POST['new']==='/sitemap.xml' && str_starts_with($conf->d_root,$c_root)){
					$url_list = [''];
					foreach (scandir($conf->data_dir.'/home/html') as $f){
						if (preg_match('/(\.(html|md))$/',$f) === 1){
							$m = preg_replace('/(\.(html|md))$/', '', $f);
							if ($m!=='index' && preg_match('/^[0-9]+$/',$m)!==1){
								$url_list[] = '?Page='.$m;
							}
						}
					}
					if((int)($conf->config['login.enable']??0)===1) $url_list[]='users/';
					$t = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'."\n";
					foreach(array_unique($url_list)as$i) $t.='<url><loc>'.$conf->domain.$i.'</loc></url>'."\n";
					file_put_contents($conf->d_root.'/sitemap.xml', $t.'</urlset>'."\n", LOCK_EX);
					header('Location:?Dir='.lreplace($c_root,$conf->d_root.'/').'&File=sitemap.xml&Menu=edit');
					exit;
				} elseif ($_POST['new']==='/robots.txt' && str_starts_with($conf->d_root,$c_root)){
					file_put_contents($conf->d_root.'/robots.txt', "User-agent:*\nSitemap:{$conf->domain}sitemap.xml\n", LOCK_EX);
					header('Location:?Dir='.lreplace($c_root,$conf->d_root.'/').'&File=robots.txt&Menu=edit');
					exit;
				} elseif (not_empty($name) && !file_exists($current_dir.'/'.$name)){
					if ($_POST['new'] === 'folder'){
						foreach(explode('\\',$name)as$n) if(!file_exists($current_dir.'/'.$n))mkdir($current_dir.'/'.$n);
					} elseif ($_POST['new'] === 'file'){
						foreach(explode('\\',$name)as$n) if(!file_exists($current_dir.'/'.$n))touch($current_dir.'/'.$n);
					} else {
						if (!str_ends_with($name,'.'.$_POST['new']) && in_array($_POST['new'],['php','html','css','js','pl','py'],true)){
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
						file_put_contents($new_path, ($cl[$_POST['new']]??'')."\n", LOCK_EX);
						if(in_array($_POST['new'],['pl','py'],true)) chmod($new_path, 0745);
					}
				}
				#アップロード
				if (isset($_FILES)){
					foreach ($_FILES as $k=>$v){
						if (isset($_FILES[$k]['tmp_name']) && (int)$_FILES[$k]['error']===0 && is_file($_FILES[$k]['tmp_name']) && not_empty($_FILES[$k]['name'])){
							$t = $_FILES[$k]['tmp_name'];
							$n = $_FILES[$k]['name'];
							$p = $current_dir.'/'.$n;
							#権限昇華防止
							if ($p===$conf->config_file || $p===$user->user_list_file){
								#最高権限のみ
								if((int)$user_data['admin']===4) rename($t, $p);
							} else {
								rename($t, $p);
							}
							#403防止
							$l = get($p, 1);
							if ($l && str_starts_with($l,'#!/')){
								chmod($p, 0745);
							} else {
								chmod($p, $perm_list['STATIC']);
							}
						}
					}
				}
				header('Location:./?Dir='.$uri_dir);
				exit;
			} elseif ($submit === 'user_list'){
				#入力が可変長なので、ログインデータで毎回チェックする
				foreach (get_rows($user->user_list_file, 2) as $row){
					$d = $user->user_data_convert(explode("\t", $row));
					#自分より権限が下か自分が最高権限か自分自身
					if ($d['admin']<$user_data['admin'] || (int)$user_data['admin']===4 || (int)$user_data['id']===(int)$d['id']){
						foreach ($user->user_list_keys as $k){
							if($k==='id') continue;
							if (isset($_POST[$k.$d['id']])){
								if(in_array($k,['name','enable','admin','passwd'],true)&&!not_empty($_POST[$k.$d['id']])) continue;
								if($k==='enable'||$k==='admin') $_POST[$k.$d['id']]=(int)$_POST[$k.$d['id']];
								if ($k === 'profile'){
									$_POST[$k.$d['id']] = str_replace("\n", '&#10;', $_POST[$k.$d['id']]);
								} elseif ($k === 'admin'){
									if ($_POST[$k.$d['id']]<0||$_POST[$k.$d['id']]>4 || ((int)$user_data['admin']!==4&&$_POST[$k.$d['id']]>=$user_data['admin'])){
										continue;
									}
								} elseif ($k==='mail'&&!filter_var($_POST[$k.$d['id']],FILTER_VALIDATE_EMAIL)){
									continue;
								}
								if ($k === 'passwd'){
									$d[$k] = password_hash(row(h(GakuUra::h($_POST[$k.$d['id']]))), PASSWORD_BCRYPT);
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
		#ファイルがある
		if (($_GET['File']??'')!=='' && strpos($_GET['File'],'..')===false && strpos($_GET['File'], '/')===false){
			$b = h($_GET['File']);
			$d = $current_dir.'/'.$b;
			if (is_file($d)){
				$bname = $b;
				$current_file = $d;
				if (!isset($_GET['download'])){
					$is_edit_mode = true;
					if ($current_file===$user->user_list_file){
						#ユーザー管理
						$menu = 'user';
					} elseif ($menu==='edit' || is_editable($current_file)){
						#編集
						$replace['TITLE'] = lreplace($current_file, $c_root.'/');
						$replace['EXIT'] = '?Dir='.$uri_dir;
						$d = '?Dir='.$uri_dir.'&File='.$bname.'&download';
						$replace['FORM_ITEMS'] = '<input type="hidden" name="name" value="'.$bname.'"><label>名前<input type="text" name="new_name" value="'.$bname.'" placeholder="変更なし"></label> '.perm_opt($perm_list,file_perm($current_file)).$rm_option;
						$replace['SUBMIT_TYPE'] = 'edit_file';
						$m = mime_content_type($current_file);
						$f = '';
						if (is_editable($current_file)){
							$c = str_replace("\t",'&#9;',str_replace("\n",'&#10;',u8lf(h(file_get_contents($current_file)))));
							$f = '<p><label><textarea rows="25" name="content" id="text">'.$c.'</textarea></label></p>';
						} elseif (str_starts_with($m,'image/')){
							$f = '<p><img style="max-width:100%;height:auto;" src="'.$d.'"></p>';
						} elseif (str_starts_with($m,'audio/')){
							$f = '<p><audio controls src="'.$d.'"></audio></p>';
						} elseif (str_starts_with($m,'video/')){
							$f = '<p><video controls src="'.$d.'"></video></p>';
						}
						$replace['FORM_AFTER'] = $is_async?'':$f;
						$replace['SESSION_TOKEN'] = $conf->set_csrf_token('admin__edit_file');
						$replace['DOWNLOAD'] = '<p><a href="'.$d.'">ダウンロードする</a></p><p><br></p>';
					} else {
						$is_edit_mode = false;
					}
				}
				if (!$is_edit_mode){
					if ($current_file===$user->user_list_file && (int)$user_data['admin']<4){
						return $conf->not_found(false, '権限がありません。');
					}
					header('Content-Description:File Transfer');
					$conf->content_type(mime_content_type($current_file));
					header('Content-Disposition:attachment;filename="'.$bname.'"');
					header('Expires:0');
					header('Cache-Control:must-revalidate');
					header('Pragma:public');
					header('Content-Length:'.filesize($current_file));
					readfile($current_file);
					exit;
				}
			} elseif ($is_async){
				return 2;
			}
		} elseif (not_empty($uri_dir) && $menu==='edit'){
			#ディレクトリの編集
			foreach(['DOWNLOAD','FORM_AFTER']as$i) $replace[$i]='';
			$is_edit_mode = true;
			$bname = basename($current_dir);
			$replace['TITLE'] = lreplace($current_dir, $c_root.'/');
			$up_to = '';
			if ($uri_dir !== ''){
				$up_to = explode('/', $uri_dir);
				array_pop($up_to);
				$up_to = implode('/', $up_to);
			}
			$replace['EXIT'] = '?Dir='.$up_to;
			$replace['FORM_ITEMS'] = '<input type="hidden" name="name" value="'.$bname.'"><label>名前<input type="text" name="new_name" value="'.$bname.'" placeholder="変更なし"></label>'.perm_opt($perm_list,file_perm($current_dir)).$rm_option;
			$replace['SUBMIT_TYPE'] = 'edit_dir';
			$replace['SESSION_TOKEN'] = $conf->set_csrf_token('admin__edit_dir');
		} elseif (!$is_edit_mode){
			$replace['SESSION_TOKEN'] = $conf->set_csrf_token('admin__new');
			$p = '<tr><td colspan="5">';
			if ($uri_dir === ''){
				$p .= '(TOP)';
			} else {
				$fl = explode('/', $uri_dir);
				$l = count($fl)-1;
				$p .= '<a href="./">(TOP)</a>';
				if($l>0)for($f=$fl[0],$i=0;$i<$l;++$i,$f.='/'.$fl[$i]) $p.='/<a href="?Dir='.$f.'">'.$fl[$i].'</a>';
				$p .= '/'.$fl[$l];
			}
			$p .= '</td></tr>';
			#先頭の/禁止
			$u_dir = $uri_dir;
			if($u_dir!=='') $u_dir.='/';
			$files = scandir($current_dir,SCANDIR_SORT_NONE);
			file_sort($files, $current_dir);
			foreach ($files as $f){
				if($f==='.'||$f==='..') continue;
				$file = $current_dir.'/'.$f;
				$fmt = '<tr><td><a href="?Dir=%s"%s>'.$f.'</a></td><td><a href="?Dir=%s&Menu=edit">編　集</a></td><td>%s '.file_perm($file).'</td><td>'.date('Y-m/d H:i',filemtime($file)).'</td></tr>';
				if (is_dir($file)){
					$p .= sprintf($fmt, $u_dir.$f,' class="dir"',$u_dir.$f,count(scandir($file))-2 .'item');
				} else {
					$p .= sprintf($fmt, $uri_dir.'&File='.$f.(is_editable($file)?'&Menu=edit':''),'',$uri_dir.'&File='.$f,filesize($file)/1000 .'kB '.mime_content_type($file));
				}
			}
			$replace['FILE_LIST'] = $p;
		}
		if ($menu === 'user'){
			#ユーザー管理
			$html = 'admin_edit_table';
			$replace['TITLE'] = '他のユーザーを管理';
			$replace['EXIT'] = '?Dir='.$uri_dir;
			$replace['FORM_ITEMS'] = '';
			$replace['SUBMIT_TYPE'] = 'user_list';
			$replace['TTITLE'] = '編集可能なユーザーのみを表示します';
			$replace['COLS'] = '<th>'.implode('</th><th>',$user->user_list_keys).'</th>';
			$replace['ROWS'] = '';
			foreach (get_rows($user->user_list_file, 2) as $row){
				$d = $user->user_data_convert(explode("\t", $row));
				#自分より下の権限か最高権限か自分自身
				if ($d['admin']<$user_data['admin'] || (int)$user_data['admin']===4 || (int)$user_data['id']===(int)$d['id']){
					$replace['ROWS'] .= '<tr'.((int)$user_data['id']===(int)$d['id']?' id="my"':'').'>';
					foreach ($user->user_list_keys as $k){
						if ($k === 'id'){
							$replace['ROWS'] .= '<td style="min-width:2em;">'.$d[$k].'</td>';
						} elseif ($k === 'passwd'){
							$replace['ROWS'] .= '<td><input type="text" name="'.$k.$d['id'].'" placeholder="変更なし"></td>';
						} else {
							$replace['ROWS'] .= '<td><input type="text" name="'.$k.$d['id'].'" value="'.$d[$k].'"';
							if($k!=='mail'&&$k!=='profile') $replace['ROWS'].=' placeholder="変更なし"';
							$replace['ROWS'] .= '></td>';
						}
					}
					$replace['ROWS'] .= '</tr>';
				}
			}
			$replace['SESSION_TOKEN'] = $conf->set_csrf_token('admin__user_list');
			$replace['DOWNLOAD'] = '';
			if (str_starts_with($user->user_list_file, $c_root) && (int)$user_data['admin']>=4){
				$b = basename($user->user_list_file);
				$replace['DOWNLOAD'] = '<a href="?Dir='.lreplace(rreplace($user->user_list_file,'/'.$b),$c_root.'/').'&File='.$b.'&download">ダウンロードする</a>';
			}
			$replace['FORM_AFTER'] = 'enableを0にするとそのユーザーはログイン出来なくなりますが、削除にはなりません。';
		}
		if ($is_edit_mode){
			if($html===$from) $html='admin_edit';
		} elseif ($menu === 'edit'){
			if($is_async) return 3;
			header('Location:?Dir='.$uri_dir);
			exit;
		}
	} elseif ($from === 'login'){
		/* ログイン */
		if ($is_login){
			header('Location:../');
			exit;
		}
		if (list_isset($_POST,['name','passwd','session_token']) && $conf->check_csrf_token('login',$_POST['session_token'],true)){
			$name = h($_POST['name']);
			$passwd = h($_POST['passwd']);
			$m = ($_POST['mail']??'')==='true';
			$i = $user->user_exists(($m?'':$name), ($m?$name:''));
			if (not_empty($name) && not_empty($passwd) && $i){
				$d = $user->user_data_convert(explode("\t", get($user->user_list_file,$i+1)));
				$p = 0;
				if (substr($d['passwd'],0,1)==='$' && subrpos('$','$',$d['passwd'])!==''){
					if(password_verify($passwd,$d['passwd'])) $p=1;
				} elseif (pass($passwd) === $d['passwd']){
					$d['passwd'] = password_hash($passwd, PASSWORD_BCRYPT);
					$user->change_user_data($conf, $d);
					$p = 1;
				}
				if ($p){
					$_SESSION[GakuUraUser::SKEY_ID] = $d['id'];
					$_SESSION[GakuUraUser::SKEY_NAME] = $d['name'];
					$_SESSION[GakuUraUser::SKEY_PASSWD] = $d['passwd'];
					header('Location:'.($_SESSION[GakuUraUser::SKEY_FROM]??'../'));
					exit;
				}
			}
			$replace['WARNING'] = 'ユーザー名またはパスワードが不正です。';
		}
		$replace['SESSION_TOKEN'] = $conf->set_csrf_token('login');
	} elseif ($from === 'regist'){
		/* 新規登録 */
		if((int)($conf->config['login.regist']??0)===0) $conf->not_found(false,'このサイトでは、新規登録は受け付けていません。');
		if (list_isset($_POST,['name','mail','passwd','session_token']) && $conf->check_csrf_token('regist',$_POST['session_token'],true)){
			$p = ['admin'=>0,'enable'=>1];
			foreach(['name','passwd','mail']as$k) $p[$k]=row(h(GakuUraUser::h($_POST[$k])));
			if (not_empty($p['name']) && not_empty($p['passwd'])){
				if (strlen($p['name']) < 32){
					if ($p['mail']==='' || filter_var($p['mail'],FILTER_VALIDATE_EMAIL)){
						if ($user->user_exists($p['name'],$p['mail']) === 0){
							$p['passwd'] = password_hash($p['passwd'], PASSWORD_BCRYPT);
							$user->change_user_data($conf, $p);
							header('Location:../../');
							exit;
						} else {
							$replace['WARNING'] = 'その名前かメールアドレスは既に登録済みです。';
						}
					} else {
						$replace['WARNING'] = 'メールアドレスの形式が不正です。';
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
	$conf->content_type('text/html');
	if ($api_args){
		$replace['API_ARGS'] = '';
		foreach($api_args as $k=>$v) $replace['API_ARGS'].=$k.'="'.$v.'" ';
	}
	return $conf->htmlf('users', $html, $replace);
}

