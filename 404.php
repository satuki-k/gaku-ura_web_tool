<?php
#gaku-ura 404ページ
$f = __DIR__ .'/gaku-ura/main/404.php';
if (is_file($f)){
	require $f;
	main();
}
