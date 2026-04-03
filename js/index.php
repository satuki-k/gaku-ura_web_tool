<?php
#gaku-ura js
$f = __DIR__ .'/../gaku-ura/main/src_link.php';
if (is_file($f)){
	require $f;
	main('js');
}
