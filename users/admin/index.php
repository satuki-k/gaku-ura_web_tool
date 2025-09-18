<?php
//gaku-ura 管理機能
$f = __DIR__ .'/../../gaku-ura/main/users.php';
if (file_exists($f)){
	require $f;
	main('admin');
}
