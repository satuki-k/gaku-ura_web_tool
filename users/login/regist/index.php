<?php
//gaku-ura 新規
$f = __DIR__ .'/../../../gaku-ura/main/users.php';
if (file_exists($f)){
	require $f;
	main('regist');
}
