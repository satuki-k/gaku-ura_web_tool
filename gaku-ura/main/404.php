<?php
# gaku-ura9 404ページ
require __DIR__ .'/../conf/conf.php';
function main():int{
	$conf = new GakuUra();
	$u = $_SERVER['REQUEST_URI']??'';
	$d = $conf->d_root.$u;
	$reason = '';
	if ($u==='' || $u==='/'){
		$reason = 'トップページがありません。';
	} elseif (is_dir($d) || str_ends_with($u,'/')){
		$reason = '存在しない、またはindex.*ファイルが無いディレクトリです。';
	} elseif (file_exists($d) || '/'.basename(__FILE__)===$u){
		$reason = 'このURLは無効です。';
	} elseif (strpos($u,'.') === false){
		$reason = 'もしかして:';
		foreach(['html','php','cgi']as$p) $reason.='<a href="'.$u.'.'.$p.'">'.$u.'.'.$p.'</a>、';
	}
	$conf->content_type('text/html');
	$conf->not_found(true, $reason);
	return 0;
}
