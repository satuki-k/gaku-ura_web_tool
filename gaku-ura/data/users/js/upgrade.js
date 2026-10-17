/* upgrade */
#!include element.js;
const t = $ID("tgz");
const f = $ID("file");
if (t && f){
	t.required = false;
	t.style.display = "none";
	f.classList.add("file");
	f.innerHTML = "ファイルを選択";
	t.addEventListener("change", ()=>{
		$ID("upgrade").click();
	});
	$ID("form").addEventListener("submit", (e)=>{
		if (!t.files[0] || !t.files[0].name){
			e.preventDefault();
			t.click();
		}
	});
}

