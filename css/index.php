<?php
#gaku-ura css
$f = __DIR__ .'/../gaku-ura/main/src_link.php';
if (is_file($f)){
	require $f;
	main('css');
}
