<?php
#gaku-ura mypage
$f = __DIR__ .'/../gaku-ura/main/users.php';
if (is_file($f)){
	require $f;
	main('home');
}
