<?php
require __DIR__ .'/../conf/conf.php';#標準
#require __DIR__ .'/../conf/users.php';#ユーザー情報
function main():int{
	$conf = new GakuUra();
	$conf->content_type('text/html');

	return 0;
}
