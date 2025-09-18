<?php
# gaku-ura9 トップページ
require __DIR__ .'/../conf/conf.php';

//実際にURLでアクセスする方のファイル(/index.php)は、この関数を呼び出すだけ
function main():int{
	$conf = new GakuUra();
	$conf->content_type('text/html');
	$html_dir = $conf->data_dir.'/home/html';
	$css_dir = $conf->data_dir.'/home/css';
	$css_file = $css_dir.'/index.css';
	$robots = true;
	if (isset($_GET['Page']) && (strpos($_GET['Page'], '.') === false) && (strpos($_GET['Page'], '/') === false)){
		$css_file = $css_dir.'/document.css';
		if (is_file($html_dir.'/'.(string)(int)$_GET['Page'].'.html')){
			$robots = false;
			$f = $html_dir.'/'.(string)(int)$_GET['Page'].'.html';
			$html = file_get_contents($f);
		} elseif (is_file($html_dir.'/'.(string)(int)$_GET['Page'].'.md')){
			$robots = false;
			$f = $html_dir.'/'.(string)(int)$_GET['Page'].'.md';
			$html = to_html(file_get_contents($f));
		} elseif (is_file($html_dir.'/'.h($_GET['Page']).'.html')){
			$f = $html_dir.'/'.h($_GET['Page']).'.html';
			$html = file_get_contents($f);
		} elseif (is_file($html_dir.'/'.h($_GET['Page']).'.md')){
			$f = $html_dir.'/'.h($_GET['Page']).'.md';
			$html = to_html(file_get_contents($f));
		} else {
			$conf->not_found();
		}
		$conf->ld_json['datePublished'] = date('Y-m/d', filemtime($f));
		$title = subrpos('<h1>', '</h1>', $html);
		if ($title === ''){
			$title = '無題';
		}
		$title .= '-';
		if ($_GET['Page'] === 'about'){
			$conf->ld_json['@type'] = $conf->ld_json['author']['@type'];
			$conf->ld_json['name'] = $conf->ld_json['author']['name'];
			unset($conf->ld_json['author']);
			unset($conf->ld_json['headline']);
			unset($conf->ld_json['datePublished']);
		} else {
			$conf->ld_json['@type'] = 'Article';
		}
	} else {
		$title = '';
		if (is_file($html_dir.'/index.html')){
			$html = file_get_contents($html_dir.'/index.html');
		} elseif (is_file($html_dir.'/index.md')){
			$html = to_html(file_get_contents($html_dir.'/index.html'));
		}
		$conf->ld_json['@type'] = 'WebSite';
	}
	remove_comment_rows($html, '<!--', '-->');
	$description = subrpos('<p>', '</p>', $html);
	remove_comment_rows($description, '<', '>');
	$conf->html($title, $description, $html, $css_file, '', $robots);
	return 0;
}


