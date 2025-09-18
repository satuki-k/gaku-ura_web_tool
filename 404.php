<?php
# gaku-ura9 404ページ
$f = __DIR__ .'/gaku-ura/main/404.php';
if (file_exists($f)){
	require $f;
	main();
}
