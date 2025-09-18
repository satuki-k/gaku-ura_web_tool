<?php
# gaku-ura9 トップページ
$f = __DIR__ .'/gaku-ura/main/index.php'; //主たる動作はこれに記述
if (file_exists($f)){
	require $f;
	main();
}
