/* csrfトークンを再取得する */
#!include string.js;
#!include element.js;
//引数[name]は input name="[name]"

//token再取得
async function reload_csrf(name){
	const e = $QS('input[name='+name+']');
	if(!e) return false;
	try{
		const r = await fetch(location.href);
		const t = await r.text();
		const f = subrpos("<form ", "</form>", t);
		if(!f) return;
		const h = document.createElement("div");
		h.innerHTML = "<form "+f+"</form>";
		h.querySelectorAll('input[type="hidden"]').forEach((k)=>{
			if(k.name===e.name&&k.type===e.type) e.value=k.value;
		});
		return true;
	}catch{}
	return false;
}

