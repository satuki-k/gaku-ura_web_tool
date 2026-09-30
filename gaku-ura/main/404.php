<?php
# gaku-ura9 404ページ
require __DIR__ .'/../conf/conf.php';
const DYM = 'もしかして:';
function main():int{
	$conf = new GakuUra();
	$u = urldecode($_SERVER['REQUEST_URI']??'');
	$d = $conf->d_root.$u;
	$reason = '';
	$q = strpos($u,'?')===false?'':substr($u, strpos($u,'?'));
	$u = rreplace($u, $q);
	if ($u==='' || $u==='/'){
		$reason = 'トップページがありません。';
	} elseif (is_dir($d) || str_ends_with($u,'/')){
		$reason = '存在しない、またはindex.*ファイルが無いディレクトリです。';
	} elseif (file_exists($d) || '/'.basename(__FILE__)===$u){
		$reason = 'このURLは無効です。';
	} elseif (strpos($u,'index.') !== false){
		$e = substr($u, 0, strrpos($u,'index.'));
		$reason = DYM.'<a href="'.$e.'">'.$e.'</a>';
	} elseif (strpos($u,'.') === false){
		$reason = DYM;
		foreach(['html','php','cgi']as$p) $reason.='<a href="'.$u.'.'.$p.$q.'">'.$u.'.'.$p.$q.'</a>、';
	} else {
		$e = strrpos($u,'.')===false?'':substr($u, strrpos($u,'.'));
		$u = rreplace($u, $e, '/');
		$reason = DYM.'<a href="'.$u.$q.'">'.$u.$q.'</a>';
	}
	$conf->content_type('text/html');
	$conf->not_found(true, $reason);
	return 0;
}
