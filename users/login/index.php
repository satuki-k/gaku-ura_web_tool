<?php
//gaku-ura login
$f = __DIR__ .'/../../gaku-ura/main/users.php';
if (file_exists($f)){
	require $f;
	main('login');
}
