/* upgrade */
#!include element.js;
#!include popup.js;
const popup = new POPUP();
const l = $ID("file");
const f = 'input[type="file"]';
const w = $ID("error");
$QS(f).required = false;
$QS(f).style.display = "none";
l.innerHTML += "ファイルを選択";
l.classList.add("file");
$QS(f).addEventListener("change", ()=>{
	const i = $QS(f);
	const n = $QS(f).files[0]?$QS(f).files[0].name:"";
	l.innerHTML = n;
	l.append(i);
});
$ID("form").addEventListener("submit", (e)=>{
	if (!$QS(f).files[0] || $QS(f).files[0].name===""){
		e.preventDefault();
		$QS(f).click();
	}
});

