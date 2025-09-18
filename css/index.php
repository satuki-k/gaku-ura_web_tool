<?php
# gaku-ura9 css
$f = __DIR__ .'/../gaku-ura/main/src_link.php';
if (file_exists($f)){
	require $f;
	main('css');
}
