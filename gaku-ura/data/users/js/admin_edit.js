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
function table_editor(f){
	const tprefix = "gaku-ura_table_";
	const tw = document.createElement("div");
	const table = document.createElement("table");
	const tbody = document.createElement("tbody");
	const add_row = document.createElement("button");
	const add_col = document.createElement("button");
	const d = f.endsWith(".csv")?",":"\t";
	tw.classList.add("table");
	$ID("text").style.display = "none";
	add_row.innerHTML = "+行を追加";
	add_col.innerHTML = "+列を追加";
	add_row.type = "button";
	add_row.id = "add_row";
	add_col.type = "button";
	add_col.id = "add_col";
	let col = ($ID("text").value.split("\n")[0]??"").split(d).length;
	let c = 0;
	$ID("text").value.split("\n").forEach((row)=>{
		const tr = document.createElement("tr");
		const l = row.split(d);
		tr.id = tprefix+c;
		for (let i = 0;i < col;++i){
			const td = document.createElement("td");
			const ip = document.createElement("input");
			ip.value = l[i]??"";
			ip.type = "text";
			ip.id = tprefix+c+","+i;
			td.append(ip);
			tr.append(td);
		}
		tbody.append(tr);
		++c;
	});
	table.append(tbody);
	tw.append(table);
	$ID("form").append(tw);
	table.before(add_row);
	table.before(add_col);
	add_row.addEventListener("click", (e)=>{
		const tr = document.createElement("tr");
		tr.id = tprefix+c;
		for (let i = 0;i < col;++i){
			const td = document.createElement("td");
			const ip = document.createElement("input");
			ip.type = "text";
			ip.id = tprefix+c+","+i;
			td.append(ip);
			tr.append(td);
		}
		tbody.append(tr);
		++c;
	});
	add_col.addEventListener("click", (e)=>{
		for (let i = 0;i < c;++i){
			const td = document.createElement("td");
			const ip = document.createElement("input");
			ip.type = "text";
			ip.id = tprefix+i+","+col;
			td.append(ip);
			$ID(tprefix+i).append(td);
		}
		++col;
	});
	const table2text = ()=>{
		$ID("text").value = "";
		for (let i = 0;i < c;++i){
			let l = [];
			let m = 0;
			for (let j = 0;j < col;++j){
				const k = tprefix+i+","+j;
				const v = $ID(k)?$ID(k).value:"";
				l.push(v);
				if(v.length) m=1;
			}
			if(m)$ID("text").value += l.join(d).trimEnd()+"\n";
		}
		if(!$ID("text").value.endsWith("\n")) $ID("text").value+="\n";
	};
	$ID("form").addEventListener("submit", table2text);
	const r = document.createElement("a");
	r.href = "#";
	r.innerHTML = "テキストエディターに戻す";
	$ID("form").prepend(r);
	r.addEventListener("click", (e)=>{
		e.preventDefault();
		r.remove();
		$ID("form").removeEventListener("submit", table2text);
		table2text();
		table.remove();
		add_col.remove();
		add_row.remove();
		new TextEditor();
	});
}
class TextEditor{
	#m;
	#mf;
	#mt;
	#m1;
	#w;
	#f;
	#ae;
	#h;
	#p;
	constructor(){
		this.#ae = document.createElement("pre");
		this.#m1 = document.createElement("select");
		const w = document.createElement("label");
		this.#w = document.createElement("input");
		this.#f = document.createElement("input");
		this.#h = null;
		this.#p = document.createElement("p");
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
		this.#p.append(this.#m1);
		this.#p.append(this.#f);
		this.#p.append(w);
		$ID("form").before(this.#p);
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
				e.deltaY<0?this.#f.value++:this.#f.value--;
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
			const f = $QS('input[name="new_name"]').value;
			if(f.endsWith(".csv") || f.endsWith(".tsv")){
				this.#p.remove();
				this.#ae.remove();
				if(this.#mf==="ace") $ID("text").value=this.#h.getValue();
				table_editor(f);
			} else {
				$ID("exit").click();
			}
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
		const v = this.#f.value<1?1:this.#f.value;
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
	const f = $QS('input[name="new_name"]').value;
	if (f.endsWith(".csv") || f.endsWith(".tsv")){
		table_editor(f);
	} else {
		new TextEditor();
	}
} else if (q.get("File")&&$QS('input[name="name"]')){
	const p = document.createElement("div");
	const r = document.createElement("a");
	p.style.margin = "1em";
	r.style = "color:#fff;background:#00c;padding:.2em;border:solid 1px #fff;";
	r.href = "#";
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
	if (await reload_csrf("csrf_token")){
		const c = $QS('[name="remove"]:checked');
		if (c && c.value==="yes"){
			if(await popup.confirm("一度削除すると復元出来ません。<br>本当に削除しますか?")) $ID("form").submit();
		} else if ($QS('[name="name"]').value!==$QS('[name="new_name"]').value || $QS('[name="perm"]').value!=="no"){
			$ID("form").submit();
		} else {
			const h = $QS("h1").innerHTML;
			$QS("h1").innerHTML = "[saving...]"+h;
			const f = new FormData($ID("form"));
			const x = new XMLHttpRequest();
			f.append("submit", $QS('[type="submit"]').value);
			x.addEventListener("load", (e)=>{
				$QS("h1").innerHTML = "[saved]"+h;
				setTimeout(()=>{$QS("h1").innerHTML=h;},1000);
			});
			x.open("POST", location.href);
			x.send(f);
		}
		$QS('[name="remove"]').checked = false;
	}
});

