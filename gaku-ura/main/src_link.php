<?php
/*
 * gaku-ura9.4.67
 * css.phpとjs.phpを統合
 */
require __DIR__ .'/../conf/conf.php';
function main(string $from):int{
	$conf = new GakuUra();
	switch ($from){
		case 'css':
		$conf->content_type('text/css');
		$t = '';
		if (!isset($_GET['USE_DEFAULT']) || ($_GET['USE_DEFAULT']==='true')){
			$t .= css_out($conf->data_dir.'/default/default.css');
		}
		if (isset($_GET['CSS']) && not_empty($_GET['CSS']) && (strpos($_GET['CSS'], '..') === false)){
			$css = h($_GET['CSS']);
			$path = $conf->data_dir.$css;
			if (file_exists($path)){
				$t .= css_out($path);
			}
		}
		echo $conf->include_lib($t, 'css');
		break;
		case 'js':
		$conf->content_type('text/javascript');
		if (isset($_GET['JS']) && not_empty($_GET['JS']) && (strpos($_GET['JS'], '..') === false)){
			$js = h($_GET['JS']);
			$path = $conf->data_dir.$js;
			if (file_exists($path)){
				if (isset($_GET['MINIFY']) && ($_GET['MINIFY'] === 'false')){
					$minify = false;
				} else {
					$minify = true;
				}
				echo $conf->include_lib(js_out($path, $minify), 'js');
			}
		}
		break;
	}
	return 0;
}

