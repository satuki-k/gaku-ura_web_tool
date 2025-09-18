<?php
# gaku-ura9 404ページ
require __DIR__ .'/../conf/conf.php';

function main():int{
	$conf = new GakuUra();
	$conf->content_type('text/html');
	$conf->not_found(true);
	return 0;
}
