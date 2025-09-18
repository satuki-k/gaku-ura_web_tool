#!include speak.js;
#!include element.js;
if ($QS('.profile') !== null){
	speak($QS('.profile'), $QS('.profile').textContent, 100, false);
}

