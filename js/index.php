<?php
# gaku-ura9 javascript
$f = __DIR__ .'/../gaku-ura/main/src_link.php';
if (file_exists($f)){
	require $f;
	main('js');
}
