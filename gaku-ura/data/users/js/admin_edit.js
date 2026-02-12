/* 管理機能 編集機能 */
#!include element.js;
#!include popup.js;
#!include string.js;
#!include keyboard.js;
#!include reload_csrf.js;
const key_m = "gaku-ura_editor_mode";
const key_f = "gaku-ura_editor_fontSize";
/*! ace (web)ace.c9.io !*/
const cdn_a = "https:\/\/cdnjs.cloudflare.com/ajax/libs/ace/1.43.3/ace.js";
const q = (new URL(document.location)).searchParams;
const popup = new POPUP();
//submitメソッドを使えるようにする
$ID("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('#form [type="submit"]').value+'">';
class TextEditor{
	#m;
	#mf;
	#mt;
	#m1;
	#w;
	#f;
	#ae;
	#h;
	constructor(){
		this.#ae = document.createElement("pre");
		this.#m1 = document.createElement("select");
		const w = document.createElement("label");
		this.#w = document.createElement("input");
		this.#f = document.createElement("input");
		this.#h = null;
		const p = document.createElement("p");
		this.#mt = ["normal","ace","exit"];
		let m=[];this.#mt.forEach((i)=>{m.push(i);});
		const v = localStorage.getItem(key_m);
		const s = localStorage.getItem(key_f);
		this.#m = this.#mt[(v!==null&&v>-1&&v<m.length)?v:1];
		this.#mf = -1;
		m.splice(m.indexOf(this.#m), 1);
		m.unshift(this.#m);
		m.forEach((i)=>{
			const o = document.createElement("option");
			o.value = i;
			o.textContent = i+(i==="exit"?"":" editor");
			this.#m1.append(o);
		});
		this.#f.value = s>0?s:18;
		this.#ae.id = "editor";
		this.#ae.style = "height:500px;z-index:0;";
		this.#f.type = "number";
		this.#f.style.width = "5em";
		this.#w.type = "checkbox";
		w.innerHTML = "行の折返し";
		w.prepend(this.#w);
		p.append(this.#m1);
		p.append(this.#f);
		p.append(w);
		$ID("form").before(p);
		$ID("form").after(this.#ae);
		$ID("text").value===""?this.reload():this.setup();
	}
	setup(){
		this.#m1.addEventListener("change", ()=>{this.editor();});
		this.#f.addEventListener("input", ()=>{this.zoom();});
		this.#w.addEventListener("change", ()=>{this.row();});
		this.#f.addEventListener("keydown", (e)=>{
			if(isEnterKey(e)) e.preventDefault();
		});
		this.editor();
		this.zoom();
		window.addEventListener("wheel", (e)=>{
			if (e.ctrlKey){
				e.preventDefault();
				(e.deltaY<0)?this.#f.value++:this.#f.value--;
				this.zoom();
			}
		},{passive:false});
		window.addEventListener("keydown", (e)=>{
			if(isCtrlKey(e)){
				if(~["s","+","-",";","="].indexOf(e.key)) e.preventDefault();
				if (e.key === "s"){
					$QS('[type="submit"]').click();
				} else if (~["+",";","-","="].indexOf(e.key)){
					~["+",";"].indexOf(e.key)?this.#f.value++:this.#f.value--;
					this.zoom();
				}
			}
		});
	}
	editor(){
		this.#m = this.#m1.value;
		if(this.#m===this.#mf) return;
		switch (this.#m){
			case "normal":
			if(this.#h) $ID("text").value=this.#h.getValue();
			this.#ae.style.display = "none";
			$ID("text").style.display = "block";
			break;
			case "ace":
			$ID("text").style.display = "none";
			this.#ae.style.display = "block";
			if ($ID("include_ace")){
				this.ace();
			} else {
				const s = document.createElement("script");
				s.src = cdn_a;
				s.id = "include_ace";
				document.body.appendChild(s);
				s.addEventListener("load", ()=>{
					this.ace();
					$ID("form").addEventListener("submit", ()=>{
						if(this.#m==="ace") $ID("text").value=this.#h.getValue();
					});
				});
			}
			break;
			case "exit":
			$ID("exit").click();
			return;
			break;
		}
		this.#mf = this.#m;
		localStorage.setItem(key_m, this.#mt.indexOf(this.#m));
	}
	ace(){
		const t = $ID("text").value;
		const l = {"md":"markdown","py":"python","pl":"perl","rb":"ruby","js":"javascript","conf":"ini","htaccess":"ini"};
		const v = $QS('input[name="new_name"]').value;
		const f = v.slice(v.indexOf(".")+1);
		let m = l[f]??f;
		if (t.slice(0,3)==='#!/'){
			const r = t.split("\n")[0];
			["perl","python","python3","ruby","php","sh","bash"].forEach((i)=>{
				if(r.endsWith(i)) m=i;
			});
		}
		if(!this.#h){
			this.#h = ace.edit(this.#ae.id,{
				useSoftTabs:false,
				mode:"ace/mode/"+m,
				theme:"ace/theme/Tomorrow"
			});
			this.#ae.addEventListener("wheel",
				(e)=>{e.preventDefault();},
				{passive:false});
			this.zoom();
		}
		this.#h.getSession().setValue(t);
	}
	//readfileじゃないと取得出来ないファイルの閲覧
	async reload(){
		try{
			const d = q.get("Dir");
			const f = q.get("File");
			const r = await fetch("?Dir="+d+"&File="+f+"&download");
			const t = await r.text();
			$ID("text").value = t;
			if(this.#m==="ace") this.#h.getSession().setValue(v);
		}catch{}
		this.setup();
	}
	zoom(){
		const v = (this.#f.value<1)?1:this.#f.value;
		this.#ae.style.fontSize = $ID("text").style.fontSize = v+"px";
		localStorage.setItem(key_f, v);
	}
	row(){
		if (this.#m === "ace"){
			this.#h.session.setUseWrapMode(this.#w.checked);
		} else {
			this.#w.checked = !this.#w.checked;
		}
	}
}
//エディター
if($ID("text")){
	new TextEditor();
} else if (q.get("File")&&$QS('input[name="name"]')){
	const p = document.createElement("div");
	const r = document.createElement("a");
	p.style.margin = "1em";
	r.style = "color:#fff;background:#00c;padding:.2em;border:solid 1px #fff;";
	r.innerHTML = "無理やり編集する";
	p.append(r);
	$ID("form").after(p);
	r.addEventListener("click", (e)=>{
		e.preventDefault();
		p.remove();
		const l = document.createElement("p");
		const b = document.createElement("label");
		const t = document.createElement("textarea");
		t.name = "content";
		t.rows = 25;
		t.id = "text";
		b.append(t);
		l.append(b);
		$QS('form').append(l);
		new TextEditor();
	});
}
//削除の警告
$ID("form").addEventListener("submit", async (e)=>{
	e.preventDefault();
	if((await reload_csrf("session_token"))&&($QS('[name="remove"]:checked').value!=='yes'||(await popup.confirm("一度削除すると復元出来ません。<br>本当に削除しますか?")))) $ID("form").submit();
});

