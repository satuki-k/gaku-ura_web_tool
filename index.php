<?php
#gaku-ura トップページ
$f = __DIR__ .'/gaku-ura/main/index.php';
if (is_file($f)){
	require $f;
	main();
}
