<?php
#gaku-ura9.6.5
require __DIR__ .'/../conf/conf.php';
function main(string $from):int{
	$conf = new GakuUra();
	$t = '';
	if ($from === 'css'){
		$conf->content_type('text/css');
		if(!isset($_GET['STANDALONE'])) $t=css_out($conf->data_dir.'/default/default.css');
		if (isset($_GET['CSS']) && not_empty($_GET['CSS']) && strpos($_GET['CSS'],'..')===false){
			$css = h($_GET['CSS']);
			$path = $conf->data_dir.$css;
			if (file_exists($path)) $t.=css_out($path);
		}
	} elseif ($from === 'js'){
		$conf->content_type('text/javascript');
		if (isset($_GET['JS']) && not_empty($_GET['JS']) && strpos($_GET['JS'],'..')===false){
			$path = $conf->data_dir.h($_GET['JS']);
			if(file_exists($path)) $t=js_out($path,isset($_GET['NOTMINIFY']));
		}
	}
	echo $conf->include_lib($t, $from);
	return 0;
}

