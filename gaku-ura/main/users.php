<?php
#gaku-ura9.7.8
require __DIR__ .'/../conf/db.php';
require __DIR__ .'/../conf/conf.php';
require __DIR__ .'/../conf/users.php';
function is_editable(string $fname):bool{
	$f = fopen($fname, 'r');
	while (($l=fgets($f)) !== false){
		if (strpos($l,"\0") !== false){
			fclose($f);
			return false;
		}
	}
	fclose($f);
	return true;
}
function file_sort(array &$files, string $dir):void{
	$d = [];
	$f = [];
	foreach($files as $i) is_dir($dir.'/'.$i)?$d[]=$i:$f[]=$i;
	natsort($d);
	natsort($f);
	$files = array_merge($d, $f);
}
function file_perm(string $f):string{return substr(sprintf('%o',fileperms($f)),-3);}
function main(string $from):int{
	$conf = new GakuUra();
	$user = new GakuUraUser($conf);
	$html = $from;
	$api_args = [];
	$submit = $_POST['submit']??$_POST['submit_type']??'';
	$csrf_token = $_POST['csrf_token']??'';
	$is_async = isset($_GET['async']);
	$l = $user->login_check();
	$is_login = $l['result'];
	$user_data = $l['user_data']??[];
	$replace = ['GAKU_URA_VERSION'=>GAKU_URA_VERSION,'CONFIG'=>'','WARNING'=>'','FOR_ADMIN'=>'','API_ARGS'=>''];
	if ($from === 'home'){
		/* ユーザーホーム */
		if (!$is_login){
			header('Location:./login/');
			exit;
		}
		if ($conf->url_param === ''){
			if ($submit && $submit==='logout' && $conf->check_csrf_token('user_home',$csrf_token,true)){
				unset($_SESSION[GakuUraUser::SKEY_ID]);
				unset($_SESSION[GakuUraUser::SKEY_NAME]);
				unset($_SESSION[GakuUraUser::SKEY_PASSWD]);
				header('Location:./');
				exit;
			} elseif (list_isset($_POST,['user_name','mail','new_passwd','profile']) && $conf->check_csrf_token('user_home',$csrf_token,true)){
				$_POST['profile'] = str_replace("\n", '&#10;', $_POST['profile']);
				$p = [];
				foreach(['user_name','mail','new_passwd','profile']as$k) $p[$k]=row(h(GakuUraUser::h($_POST[$k])));
				if(not_empty($p['user_name'])&&strlen($p['user_name'])<32&&$user->user_exists($p['user_name'])===0) $user_data['name']=$p['user_name'];
				if(not_empty($p['new_passwd'])) $user_data['passwd']=password_hash($p['new_passwd'],PASSWORD_BCRYPT);
				if(filter_var($p['mail'],FILTER_VALIDATE_EMAIL)&&$user->user_exists('',$p['mail'])===0) $user_data['mail']=$p['mail'];
				$user_data['profile'] = $p['profile'];
				$_SESSION[GakuUraUser::SKEY_NAME] = $user_data['name'];
				$_SESSION[GakuUraUser::SKEY_PASSWD] = $user_data['passwd'];
				$user->change_user_data($user_data);
				header('Location:./');
				exit;
			} else {
				$replace['CSRF_TOKEN'] = $conf->set_csrf_token('user_home');
				foreach(['name','mail','profile','admin']as$k) $replace[strtoupper($k)]=$user_data[$k];
				$a = $conf->data_dir.'/users/html/home_admin.html';
				if ($user_data['admin']>=$user->admin_revel && is_file($a)){
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
			$replace['PROFILE'] = to_html(str_replace('&#10;',"\n",$u['profile']??''));
		}
	} elseif ($from === 'admin'){
		/* 管理機能 */
		if (!$is_login){
			header('Location:../login/');
			exit;
		}
		if($user_data['admin']<$user->admin_revel) $conf->not_found();
		$is_edit_mode = 0;
		$menu = $_GET['Menu']??'';
		$admin_dir = $user->own_dir[$user_data['admin']];
		$c_root = realpath($conf->d_root.$admin_dir);
		$current_dir = $c_root;
		$uri_dir = h(str_replace('..','',$_GET['Dir']??''));
		$perm_list = ['no'=>0,'DIR'=>0755,'CGI'=>0745,'CGI2'=>0755,'STATIC'=>0644,'STATIC2'=>0666,'MPRIVATE'=>0604,'PRIVATE'=>0600];
		$replace['TITLE'] = '管理機能';
		foreach(['max_file_uploads']as$i) $api_args[$i]=ini_get($i);
		#要点ファイルショートカット
		$scut = [];
		$api_args['u_root'] = rreplace($conf->u_root,'/');
		if (str_starts_with($conf->d_root,$c_root)){
			$api_args['d_root'] = lreplace(lreplace($conf->d_root,$c_root), '/');
			$scut[] = '<a href="?Dir='.$api_args['d_root'].'">TOP</a>';
		}
		#shortcut dir
		foreach ([$conf->data_dir=>'project dirs',__DIR__=>'main files',$conf->data_dir.'/home'=>'homeages'] as $k=>$v){
			if(str_starts_with($k,$c_root)) $scut[$v]='<a href="?Dir='.lreplace($c_root===$k?'':lreplace($k,$c_root),'/').'">'.$v.'</a>';
		}
		#shortcut file admin
		if ($user_data['admin'] >= 4){
			foreach ([$conf->config_file=>'config',$user->user_list_file=>'users'] as $k=>$v){
				$b = basename($k);
				if (str_starts_with($k, $c_root)){
					$scut[$v] = '<a href="?Dir='.lreplace(lreplace(rreplace($k,'/'.$b),$c_root),'/').'&File='.$b.'">'.$v.'</a>';
					$replace[strtoupper($v)] = $scut[$v];
				}
			}
		}
		$replace['SHORTCUT'] = implode('|', $scut);
		#現在位置を特定
		$d = realpath($c_root.'/'.$uri_dir);
		if (is_dir($d)){
			$current_dir = $d;
		} elseif ($is_async){
			return 1;
		} else {
			$uri_dir = '';
		}
		#投稿
		if ($submit && $conf->check_csrf_token('admin__'.$submit,$csrf_token,true)){
			if ($submit==='edit_file' && list_isset($_POST,['name','new_name','perm']) && strpos($_POST['name'],'..')===false && strpos($_POST['name'], '/')===false && is_file($current_dir.'/'.h($_POST['name'])) && isset($perm_list[$_POST['perm']])){
				$path = $current_dir.'/'.h($_POST['name']);
				#削除
				if (($path!==$conf->config_file&&!str_starts_with($path,$user->user_dir.'/'.GakuUraUser::TABLE_NAME)) || $user_data['admin']>=4){
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
				$template_dir = $conf->data_dir.'/default/file';
				$name = str_replace('..','', str_replace('/','',h($_POST['name'])));
				if (in_array($_POST['new'],['.htaccess','/sitemap.xml','/robots.txt'],true) && !str_starts_with($conf->d_root,$c_root)){
					$conf->not_found(false, '権限がありません');
				}
				if ($_POST['new'] === '.htaccess'){
					touch($current_dir.'/.htaccess');
				} elseif ($_POST['new']==='/sitemap.xml' && str_starts_with($conf->d_root,$c_root) && is_file($template_dir.$_POST['new'])){
					$f = $template_dir.$_POST['new'];
					$url_list = [''];
					$d = $conf->data_dir.'/home/html';
					foreach (scandir($d) as $i){
						if (preg_match('/(\.(html|md))$/',$i)===1&&!str_starts_with($i,'index.')){
							if((int)$i===0) $url_list[]='?Page='.rreplace(rreplace($i,'.html'),'.md');
						}
					}
					if((int)($conf->config['login.enable']??0)===1) $url_list[]='users/';
					$t = '';
					foreach(array_unique($url_list)as$i) $t.='<url><loc>'.$conf->domain.$i.'</loc></url>'."\n";
					$s = str_replace('{URL_LIST}', $t, file_get_contents($f));
					file_put_contents($conf->d_root.$_POST['new'], $s, LOCK_EX);
					header('Location:?Dir='.lreplace($c_root,$conf->d_root.'/').'&File='.basename($f).'&Menu=edit');
					exit;
				} elseif ($_POST['new']==='/robots.txt' && str_starts_with($conf->d_root,$c_root) && is_file($template_dir.$_POST['new'])){
					$s = file_get_contents($template_dir.$_POST['new']);
					$s = str_replace('{DOMAIN}', $conf->domain, $s);
					file_put_contents($conf->d_root.'/robots.txt', $s, LOCK_EX);
					header('Location:?Dir='.lreplace($c_root,$conf->d_root.'/').'&File=robots.txt&Menu=edit');
					exit;
				} elseif (not_empty($name) && !file_exists($current_dir.'/'.$name)){
					if ($_POST['new'] === 'folder'){
						foreach(explode('\\',$name)as$n) if(!file_exists($current_dir.'/'.$n))mkdir($current_dir.'/'.$n,0777,true);
					} elseif ($_POST['new'] === 'file'){
						foreach(explode('\\',$name)as$n) if(!file_exists($current_dir.'/'.$n))touch($current_dir.'/'.$n);
					} else {
						if (!str_ends_with($name,'.'.$_POST['new']) && in_array($_POST['new'],['php','html','css','js','pl','py','db'],true)){
							$name .= '.'.$_POST['new'];
						}
						$new_path = $current_dir.'/'.str_replace('..','.',$name);
						$t = $template_dir.'/index.'.$_POST['new'];
						if($_POST['new']==='php' && $current_dir===__DIR__) $t=$template_dir.'/main.php';
						$s = "\n";
						if(is_file($t)) $s=file_get_contents($t);
						$r = ['DOMAIN'=>$conf->domain,'GAKU_URA_VERSION'=>GAKU_URA_VERSION,'SITE_TITLE'=>($conf->config['title']??'無題')];
						foreach($r as $k=>$v) $s=str_replace('{'.$k.'}',$v,$s);
						file_put_contents($new_path, $s, LOCK_EX);
						if(in_array($_POST['new'],['pl','py'],true)) chmod($new_path, 0745);
						if ($_POST['new'] === 'db'){
							header('Location:./?Dir='.$uri_dir.'&File='.basename($new_path).'&Menu=edit_db');
							exit;
						}
					}
				}
				#アップロード
				foreach ($_FILES??[] as $k=>$v){
					if (isset($_FILES[$k]['tmp_name']) && (int)$_FILES[$k]['error']===0 && is_file($_FILES[$k]['tmp_name']) && not_empty($_FILES[$k]['name'])){
						$t = $_FILES[$k]['tmp_name'];
						$n = $_FILES[$k]['name'];
						$p = $current_dir.'/'.$n;
						#権限昇華防止
						if ($p===$conf->config_file || str_starts_with($p,$user->user_dir.'/'.GakuUraUser::TABLE_NAME)){
							#最高権限のみ
							if($user_data['admin']===4) rename($t, $p);
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
				header('Location:./?Dir='.$uri_dir);
				exit;
			} elseif ($submit==='edit_db'&&list_isset($_POST,['dbtype','dbname','query','table','import_name'])){
				#SQLite
				if ($_POST['dbtype']==='sqlite' && strpos($_POST['dbname'],'..')===false && strpos($_POST['dbname'], '/')===false && is_file($current_dir.'/'.$_POST['dbname'])){
					$current_table = h(($_POST['table']??''));
					$dbname = h($_POST['dbname']);
					$table = h($_POST['ctable']);
					$g = new GakuUraSQL($_POST['dbtype'], $current_dir.'/'.$_POST['dbname']);
					#インポート
					if (($tn=$_FILES['file']['tmp_name']??'')!=='' && is_file($tn)){
						$fp = fopen($tn,'r');
						$n = $_FILES['file']['name'];
						$a = [];
						$sep = str_ends_with($n,'.tsv')?"\t":',';
						while(($l=fgetcsv($fp,9999999,$sep,'"',''))!==false) $a[]=$l;
						if(not_empty($_POST['import_name'])) $n=$_POST['import_name'];
						$g->import(str_replace('.','',str_replace('-','',h($n))), $a, $_POST['first_is_col']??''==='true', true);
						fclose($fp);
					}
					#削除とtable切替を同時に行うと切替の優先で意図しない削除を防止
					if ($g->table_exists($table)){
						header('Location:./?Dir='.$uri_dir.'&File='.$dbname.'&Menu='.$submit.'&table='.$table);
						exit;
					}
					if ($current_table === ''){
						$tl = $g->get_tables();
						$current_table = $tl[0]??'';
					}
					#table削除
					if (($_POST['remove_table']??'')==='true' && not_empty($current_table)){
						$g->remove_table($current_table);
						header('Location:./?Dir='.$uri_dir.'&File='.$dbname.'&Menu='.$menu);
						exit;
					}
					#SQL文実行
					$sql = $_POST['query'];
					if (!not_empty($sql) && ($_POST['export']??'')==='true'){
						$conf->content_type('text/plain');
						header('Content-Description:File Transfer');
						header('Content-Disposition:attachment;filename="'.$current_table.'.csv"');
						echo $g->export($current_table);
						exit;
					}
					if (not_empty($sql)){
						$conf->content_type('text/plain');
						$r = $g->exec($sql);
						if ($r && (str_starts_with(strtoupper($sql),'SELECT ')||str_starts_with(strtoupper($sql),'SHOW '))){
							foreach ($r as $l){
								foreach($l as $k=>$v) echo "$k:$v\n";
								echo "\n";
							}
							exit;
						}
						if ($r === false){
							echo $g->error_msg;
							exit;
						}
					}
					#table変更
					if (($_POST['table_name']??'')!=='' && $_POST['table_name']!==$current_table){
						if($g->change_table($current_table, $_POST['table_name'])) $current_table=$_POST['table_name'];
					}
					#列変更
					$c = $g->get_cols($current_table);
					foreach ($c as $i=>$j){
						if ($j!==$g->id_col && ($_POST['col,'.$j]??'')!=='' && $_POST['col,'.$j]!==$j){
							if($g->change_col($current_table, $j, $_POST['col,'.$j])) $c[$i]=$_POST['col,'.$j];
						}
					}
					#id列が無いデータベースは未対応
					if (in_array($g->id_col,$c,true)){
						foreach ($g->get_rows($current_table) as $r){
							if (isset($r[$g->id_col]) && ($_POST['remove_row,'.$r[$g->id_col]]??'') === 'true'){
								#行削除
								$g->remove_row($current_table, $g->id_col.'=?', [$r[$g->id_col]]);
							} else {
								#行編集
								$is_change = false;
								$change_row = [];
								foreach ($c as $k){
									if (isset($_POST[$r[$g->id_col].','.$k]) && $_POST[$r[$g->id_col].','.$k]!=strval($r[$k]??'')){
										$change_row[$k] = $_POST[$r[$g->id_col].','.$k];
										$is_change = true;
									}
								}
								if($is_change) $g->change_row($current_table, $change_row, $g->id_col.'=?', [$r[$g->id_col]]);
							}
						}
					}
					#行追加
					$is_add = false;
					$add = [];
					$nl = 'new,';
					foreach ($c as $i){
						if ($i!==$g->id_col && isset($_POST[$nl.$i]) && not_empty($_POST[$nl.$i])){
							$is_add = true;
							$add[$i] = $_POST[$nl.$i];
						}
					}
					if($is_add) $g->append_row($current_table,$add);
					header('Location:./?Dir='.$uri_dir.'&File='.basename($dbname).'&Menu='.$submit.'&table='.$current_table);
					exit;
				}
			} elseif ($submit==='upgrade' && $user_data['admin']>=4 && str_starts_with($conf->d_root,$c_root) && isset($_POST['reupgrade'])){
				#upgrade
				$replace['CSRF_TOKEN'] = '';
				$replace['GAKU_URA_FILES'] = implode('&#10;', GakuUra::GAKU_URA_FILES);
				$replace['UPGRADE_IGNORE'] = implode('&#10;',GakuUra::UPGRADE_IGNORE).'&#10;'.($conf->config['upgrade.ignore']??'');
				if (isset($_POST['file']) && $_POST['reupgrade']==='true' && is_file($conf->data_dir.'/'.$_POST['file'])){
					$r = $conf->upgrade($conf->data_dir.'/'.$_POST['file']);
					unlink($conf->data_dir.'/'.$_POST['file']);
					$m = '完了(success)';
					if ($r !== 0){
						$m = '失敗 <b>更新が不安定な状態で停止しました。FTP等を用いてバックアップで全てのファイルを上書きアップロードしてください。</b>';
					}
					$m .= ' status:'.$r;
					$replace['ERROR_MSG'] = $m;
				} elseif (($f=$_FILES['file']['tmp_name']??'')!==''){
					$d = [];
					$r = $conf->upgrade($_FILES['file']['tmp_name'], $d);
					$m = '成功(success)';
					if ($r === 1){
						$m = '失敗(invalid file)';
					} elseif ($r === 2){
						$m = '失敗(cannot extract)';
					} elseif ($r === 3){
						$m = '警告(ファイル:'.implode(',',$d).' は廃止されました)';
					}
					$m .= ' status:'.$r;
					if ($r===0 || $r===3){
						copy($f, $conf->data_dir.'/'.$_FILES['file']['name']);
						$m .= '<b>操作はまだ完了していません。</b>アップグレード対象のファイル一覧が更新されたので、以下のボタンを押して完了してください。';
						if($m===3) $m.=implode(',',$d).' は廃止されました。';
						$m .= '<form action="" method="POST"><label><button type="submit" name="submit" value="'.$submit.'">完了</button></label><input type="hidden" name="csrf_token" value="'.$conf->set_csrf_token('admin__upgrade').'"><input type="hidden" name="file" value="'.$_FILES['file']['name'].'"><input type="hidden" name="reupgrade" value="true"></form>';
					} else {
						$m .= '<b>失敗しました。</b>';
					}
					$replace['ERROR_MSG'] = $m;
				} else {
					$conf->form_die();
				}
				return $conf->htmlf('users', 'upgrade', $replace);
			} else {
				$conf->form_die();
			}
			header('Location:'.$conf->here);
			exit;
		}
		#upgrade menu
		if ($menu === 'upgrade'){
			$html = 'upgrade';
			$replace['ERROR_MSG'] = '権限が不足しています。';
			$replace['CSRF_TOKEN'] = '';
			$replace['GAKU_URA_FILES'] = implode('&#10;', GakuUra::GAKU_URA_FILES);
			$replace['UPGRADE_IGNORE'] = implode('&#10;',GakuUra::UPGRADE_IGNORE).'&#10;'.($conf->config['upgrade.ignore']??'');
			if ($user_data['admin']>=4 && str_starts_with($conf->d_root,$c_root)){
				$replace['ERROR_MSG'] = '';
				#TODO: 次のバージョンまで警告続ける
				$u = [];
				foreach (get_rows($user->user_list_file,2) as $row){
					$d = $user->user_data_convert(explode("\t", $row));
					if(!subrpos('$','$',$d['passwd'])) $u[]=$d['name'];
				}
				if($u){
					$replace['ERROR_MSG'] = '【続行不可】従来のsha256でハッシュ化されたパスワードが未更新のユーザーがいます。('.implode(',',$u).')<br>次のバージョンで互換性を廃止するため、該当ユーザーに再ログインを依頼するか、削除してください。';
				} else {
					$replace['CSRF_TOKEN'] = $conf->set_csrf_token('admin__upgrade');
				}
			}
		} elseif (($_GET['File']??'')!=='' && strpos($_GET['File'],'..')===false && strpos($_GET['File'], '/')===false){
			#ファイルがある
			$bname = h($_GET['File']);
			$current_file = $current_dir.'/'.$bname;
			if (!is_file($current_file)){
				if($is_async) return 2;
				header('Location:?Dir='.$uri_dir);
				exit;
			}
			if (str_starts_with($current_file,$user->user_dir.'/'.GakuUraUser::TABLE_NAME) && $user_data['admin']<4){
				$conf->not_found(false, '権限がありません。');
			}
			if (!isset($_GET['download'])){
				$is_edit_mode = 1;
				$editable = is_editable($current_file);
				if ($menu!=='edit_db' && ($menu==='edit'||$editable)){
					#編集
					$replace['TITLE'] = lreplace($current_file, $c_root.'/');
					$replace['EXIT'] = '?Dir='.$uri_dir;
					$d = '?Dir='.$uri_dir.'&File='.$bname.'&download';
					$replace['NAME'] = $bname;
					$replace['PERM'] = file_perm($current_file);
					$replace['SUBMIT_TYPE'] = 'edit_file';
					$m = mime_content_type($current_file);
					$f = '';
					if (str_ends_with($current_file,'.db')){
						$f = '<p><a href="?Dir='.$uri_dir.'&File='.$bname.'&Menu=edit_db">tableを編集</a></p>';
					}
					if ($editable){
						$c = str_replace("\t",'&#9;',str_replace("\n",'&#10;',u8lf(h(file_get_contents($current_file)))));
						$f .= '<p><label><textarea rows="25" name="content" id="text">'.$c.'</textarea></label></p>';
					} elseif (str_starts_with($m,'image/')){
						$f .= '<p><img style="max-width:100%;height:auto;" src="'.$d.'"></p>';
					} elseif (str_starts_with($m,'audio/')){
						$f .= '<p><audio controls src="'.$d.'"></audio></p>';
					} elseif (str_starts_with($m,'video/')){
						$f .= '<p><video controls src="'.$d.'"></video></p>';
					}
					$replace['FORM_AFTER'] = $is_async?'':$f;
					$replace['CSRF_TOKEN'] = $conf->set_csrf_token('admin__edit_file');
					$replace['DOWNLOAD'] = '<p><a href="'.$d.'">ダウンロードする</a></p><p><br></p>';
				} elseif ($menu !== 'edit_db'){
					$is_edit_mode = 1;
				}
			}
			if (!$is_edit_mode){
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
		} elseif (not_empty($uri_dir) && $menu==='edit'){
			#ディレクトリの編集
			foreach(['DOWNLOAD','FORM_AFTER']as$i) $replace[$i]='';
			$is_edit_mode = 1;
			$bname = basename($current_dir);
			$replace['TITLE'] = lreplace($current_dir, $c_root.'/');
			$up_to = '';
			if ($uri_dir !== ''){
				$up_to = explode('/', $uri_dir);
				array_pop($up_to);
				$up_to = implode('/', $up_to);
			}
			$replace['EXIT'] = '?Dir='.$up_to;
			$replace['NAME'] = $bname;
			$replace['PERM'] = file_perm($current_dir);
			$replace['SUBMIT_TYPE'] = 'edit_dir';
			$replace['CSRF_TOKEN'] = $conf->set_csrf_token('admin__edit_dir');
		} elseif (!$is_edit_mode){
			$replace['CSRF_TOKEN'] = $conf->set_csrf_token('admin__new');
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
					$e = str_ends_with($f,'.db')?'&Menu=edit_db':'';
					$p .= sprintf($fmt, $uri_dir.'&File='.$f.$e,'',$uri_dir.'&File='.$f,filesize($file)/1000 .'kB '.mime_content_type($file));
				}
			}
			$replace['FILE_LIST'] = $p;
		}
		if ($menu==='edit_db' && isset($current_file)){
			#データベース編集
			$html = 'admin_edit_table';
			$bname = basename($current_file);
			$replace['EXIT'] = '?Dir='.$uri_dir;
			$replace['TITLE'] = lreplace($current_file, $c_root.'/');
			$replace['SUBMIT_TYPE'] = 'edit_db';
			$replace['DBTYPE'] = 'sqlite';
			$replace['DBNAME'] = $bname;
			$replace['COLS'] = '';
			$replace['ROWS'] = '';
			$replace['TTITLE'] = '';
			$replace['TABLE_LIST'] = '';
			$g = new GakuUraSQL('sqlite', $current_file);
			if ($g->is_connect){
				$tl = $g->get_tables();
				$t = $_GET['table']??'';
				if($t==='') $t=$tl[0]??'';
				if ($tl!==[] && $g->table_exists($t)){
					$replace['TTITLE'] = '<b><input type="text" name="table_name" value="'.h($t).'"></b>';
					$c = $g->get_cols($t);
					$ci = $g->get_cols_type($t);
					$replace['COLS'] = '<th style="width:3em;"></th>';
					foreach ($c as $i){
						if ($i === $g->id_col){
							$replace['COLS'] .= '<th style="width:3em;">'.h($i).'</th>';
						} else {
							$replace['COLS'] .= '<th><input type="text" name="col,'.h($i).'" value="'.h($i).'" style="width:95%;border:0;outline:0;"><i style="font:.8em/1 sans-serif;display:block;text-align:left;">'.$ci[$i].'</i></th>';
						}
					}
					foreach ($g->get_rows($t) as $r){
						$i = array_values($r)[0];
						$replace['ROWS'] .= '<tr><td><label><input type="checkbox" name="remove_row,'.h($i).'" value="true">削除</label></td>';
						foreach ($r as $k=>$v){
							if ($k === $g->id_col){
								$replace['ROWS'] .= '<td>'.h($v).'</td>';
							} elseif (in_array($k,$c,true)){
								$replace['ROWS'] .= '<td><input type="text" name="'.h($i).','.h($k).'" value="'.h($v??'').'"></td>';
							}
						}
						$replace['ROWS'] .= '</tr>';
						++$i;
					}
					$replace['ROWS'] .= '<tr><td>追加</td>';
					foreach ($c as $r){
						if ($r === $g->id_col){
							$replace['ROWS'] .= '<td></td>';
						} else {
							$replace['ROWS'] .= '<td><input type="text" name="new,'.h($r).'"></td>';
						}
					}
					$replace['ROWS'] .= '</tr>';
				} else {
					$t = '';
				}
				$replace['TTITLE'] .= ($t===''||$tl===[])?'tableがありません':' <label><input type="checkbox" name="remove_table" value="true">このtableを削除</label>';
				$replace['TABLE'] = h($t);
				foreach($tl as $i) $replace['TABLE_LIST'].='<option value="'.$i.'">'.$i.'</option>';
				$replace['CSRF_TOKEN'] = $conf->set_csrf_token('admin__edit_db');
			} else {
				$conf->not_found(false, 'DBの接続に失敗しました。<a href="'.$replace['EXIT'].'">戻る</a>');
			}
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
		if (list_isset($_POST,['user_name','passwd']) && $conf->check_csrf_token('login',$csrf_token,true)){
			$name = h($_POST['user_name']);
			$passwd = h($_POST['passwd']);
			$m = ($_POST['mail']??'')==='true';
			$i = $user->user_exists($m?'':$name, $m?$name:'');
			if (not_empty($name) && not_empty($passwd) && $i){
				$d = $user->user_data_convert(explode("\t", get($user->user_list_file,$i+1)));
				$p = 0;
				if (subrpos('$','$',$d['passwd'])){
					if(password_verify($passwd,$d['passwd'])) $p=1;
				} elseif (pass($passwd) === $d['passwd']){
					#TODO: この互換性は廃止予定
					$d['passwd'] = password_hash($passwd, PASSWORD_BCRYPT);
					$user->change_user_data($d);
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
		$replace['CSRF_TOKEN'] = $conf->set_csrf_token('login');
	} elseif ($from === 'regist'){
		/* 新規登録 */
		if((int)($conf->config['login.regist']??0)===0) $conf->not_found(false,'このサイトでは、新規登録は受け付けていません。');
		if (list_isset($_POST,['user_name','mail','passwd']) && $conf->check_csrf_token('regist',$csrf_token,true)){
			$p = ['admin'=>0,'enable'=>1];
			foreach([['user_name','name'],['passwd'],['mail']]as$i) $p[$i[1]??$i[0]]=row(h(GakuUraUser::h($_POST[$i[0]])));
			if (not_empty($p['name']) && not_empty($p['passwd'])){
				if (strlen($p['name']) < 32){
					if ($p['mail']==='' || filter_var($p['mail'],FILTER_VALIDATE_EMAIL)){
						if ($user->user_exists($p['name'],$p['mail']) === 0){
							$p['passwd'] = password_hash($p['passwd'], PASSWORD_BCRYPT);
							$user->change_user_data($p);
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
		$replace['CSRF_TOKEN'] = $conf->set_csrf_token('regist');
	}
	$conf->content_type('text/html');
	foreach($api_args as $k=>$v) $replace['API_ARGS'].=$k.'="'.$v.'" ';
	return $conf->htmlf('users', $html, $replace);
}

