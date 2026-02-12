/* 管理機能 データベース */
#!include element.js;
#!include popup.js;
#!include reload_csrf.js;
const popup = new POPUP();
//submitメソッドを使えるようにする
$ID("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('#form [type="submit"]').value+'">';

//削除の警告
const r = $QS('[name="remove"]:checked');
if (r){
	$ID("form").addEventListener("submit", async (e)=>{
		e.preventDefault();
		if((await reload_csrf("session_token"))&&(r.value!=='yes'||(await popup.confirm("一度削除すると復元出来ません。<br>本当に削除しますか?")))) $ID("form").submit();
	});
}

