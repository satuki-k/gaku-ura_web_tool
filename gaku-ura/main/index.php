<?php
# gaku-ura9 トップページ
require __DIR__ .'/../conf/conf.php';

//実際にURLでアクセスする方のファイル(/index.php)は、この関数を呼び出すだけ
function main():int{
	$conf = new GakuUra();
	$conf->content_type('text/html');
	$html_dir = $conf->data_dir.'/home/html';
	$file = '';
	$robots = true;
	if (isset($_GET['Page']) && strpos($_GET['Page'],'.')===false && strpos($_GET['Page'],'/')===false){
		if (is_file($html_dir.'/'.(string)(int)$_GET['Page'].'.html')){
			$file = (string)(int)$_GET['Page'].'.html';
			$robots = false;
		} elseif (is_file($html_dir.'/'.(string)(int)$_GET['Page'].'.md')){
			$file = (string)(int)$_GET['Page'].'.md';
			$robots = false;
		} elseif (is_file($html_dir.'/'.h($_GET['Page']).'.html')){
			$file = h($_GET['Page']).'.html';
		} elseif (is_file($html_dir.'/'.h($_GET['Page']).'.md')){
			$file = h($_GET['Page']).'.md';
		} else {
			$conf->not_found();
		}
		$conf->ld_json['datePublished'] = date('Y-m/d', filemtime($html_dir.'/'.$file));
		if ($_GET['Page'] === 'about'){
			$conf->ld_json['@type'] = $conf->ld_json['author']['@type'];
			$conf->ld_json['name'] = $conf->ld_json['author']['name'];
			unset($conf->ld_json['author']);
		} else {
			$conf->ld_json['@type'] = 'Article';
		}
	} else {
		if (is_file($html_dir.'/index.html')){
			$file = 'index.html';
		} elseif (is_file($html_dir.'/index.md')){
			$file = 'index.md';
		}
		$conf->ld_json['@type'] = 'WebSite';
	}
	return $conf->htmlf('home', $file, ['VERSION'=>GAKU_URA_VERSION,'TIME'=>date('Y年m月d日 H時i分')], $robots);
}


