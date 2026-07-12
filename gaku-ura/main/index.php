<?php
# gaku-ura9 トップページ
require __DIR__ .'/../conf/conf.php';
#実際にURLでアクセスする方のファイル(/index.php)は、この関数を呼び出すだけ
function main():int{
	$conf = new GakuUra();
	$conf->content_type('text/html');
	$robots = true;
	$page = h($_GET['Page']??'index');
	$html = $conf->data_dir.'/home/html/'.$page;
	if ($page!=='index'){
		$robots = $page!==(string)(int)$page;
		foreach (['md','html'] as $i){
			if (is_file($html.'.'.$i)){
				$html .= '.'.$i;
				$conf->ld_json['datePublished'] = date('Y-m/d', filemtime($html));
			}
		}
		if ($page === 'about'){
			$conf->ld_json['@type'] = $conf->ld_json['author']['@type'];
			$conf->ld_json['name'] = $conf->ld_json['author']['name'];
			unset($conf->ld_json['author']);
		} else {
			$conf->ld_json['@type'] = 'Article';
		}
	} else {
		foreach(['md','html']as$i)if(is_file($html.'.'.$i)) $html.='.'.$i;
		$conf->canonical = $conf->domain;
		$conf->ld_json['@type'] = 'WebSite';
	}
	$r = $conf->htmlf('home', $html, ['VERSION'=>GAKU_URA_VERSION,'TIME'=>date('Y年m月d日 H時i分')], $robots);
	if($r) $conf->not_found(false,'存在しない記事です');
	return $r;
}

