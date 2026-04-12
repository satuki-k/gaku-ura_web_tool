<?php
#gaku-ura9.6.8
require __DIR__ .'/../conf/conf.php';
function main(string $f):int{
	$conf = new GakuUra();
	$conf->content_type('text/'.($f==='js'?'javascript':$f));
	$q = $_GET[strtoupper($f)]??explode('&',$conf->url_param)[0];
	$t = (!isset($_GET['STANDALONE'])&&$f==='css')?css_out($conf->data_dir.'/default/default.css'):'';
	if (not_empty($q) && strpos($q,'..')===false){
		$p = $conf->data_dir.h($q);
		$t .= $f==='js'?js_out($p,!isset($_GET['NOTMINIFY'])):css_out($p);
	}
	echo $conf->include_lib($t, $f);
	return 0;
}

