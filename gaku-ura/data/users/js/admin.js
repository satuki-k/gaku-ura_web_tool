/* 管理機能 */
#!include element.js;
#!include popup.js;
#!include string.js;
const key_m = "gaku-ura_editor_mode";
const key_f = "gaku-ura_editor_fontSize";
/*! ace (web)ace.c9.io !*/
const cdn_a = "https:\/\/cdnjs.cloudflare.com/ajax/libs/ace/1.43.3/ace.js";
const q = (new URL(document.location)).searchParams;
//グラフィカルなポップアップ
const popup = new POPUP();
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
			if(e.key=="Enter"||e.key==="Return") e.preventDefault();
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
			if(e.ctrlKey){
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
		const l = {"md":"markdown","py":"python","pl":"perl","rb":"ruby","js":"javascript","conf":"ini","htaccess":"ini"};
		const v = $QS('input[name="new_name"]').value;
		const f = v.slice(v.indexOf(".")+1);
		if(!this.#h){
			this.#h = ace.edit(this.#ae.id,{
				useSoftTabs:false,
				mode:"ace/mode/"+(l[f]??f),
				theme:"ace/theme/Tomorrow"
			});
			this.#ae.addEventListener("wheel",
				(e)=>{e.preventDefault();},
				{passive:false});
			this.zoom();
		}
		this.#h.getSession().setValue($ID("text").value);
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
/* 編集画面 */
if (q.get("Menu") === "edit"){
	//submitメソッドを使えるようにする
	$ID("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('button[type="submit"]').value+'">';
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
		if ($QS('[name="remove"]:checked').value === 'yes'){
			e.preventDefault();
			if(await popup.confirm("一度削除すると復元出来ません。<br>本当に削除しますか?")) $ID("form").submit();
		}
	});
} else if ($ID("files")){
	let move_file = 0;
	//ini_get
	const arg = $ID("gaku-ura_args");
	const maxfc = parseInt(arg.getAttribute("max_file_uploads")??20);
	/* ファイル一覧 */
	const d = document.body;
	const t = $ID("files");
	const b = d.style.background;
	d.addEventListener("dragover", (e)=>{
		e.preventDefault();
		d.style.background = "#eef";
	});
	d.addEventListener("dragleave",()=>{d.style.background=b;});
	d.addEventListener("drop", async (e)=>{
		e.preventDefault();
		d.style.background = b;
		if (move_file){
			popup.alert("別の操作を実行中です。");
			return;
		}
		move_file = 1;
		const l = e.dataTransfer.files;
		for (let c=0,i=0;i < l.length;++i,++c){
			const f = l[i];
			if(f.type===""&&(f.size===0||f.size===4096)) continue;
			if (c === maxfc){
				if(await popup.confirm("ファイル数が多いです。ここまでか、これ以降のどちらを投稿しますか?<br>(ここから:"+f.name+")","ここまで","これ以降")) break;
				t.innerHTML = "";
				c = 0;
			}
			const p = document.createElement("input");
			const d = new DataTransfer();
			p.type = "file";
			p.name = "file"+i;
			p.style.display = "none";
			d.items.add(f);
			p.files = d.files;
			t.appendChild(p);
		}
		if(l.length>0) $QS('[type="submit"]').click();
		move_file = 0;
	});
	/* 操作メニュー */
	const g = document.createElement("pre");//クリックメニュー
	g.style = "background:#fff;position:fixed;display:none;";
	document.body.append(g);
	function mclose(){
		g.style.display="none";
		g.innerHTML = "";
		$QSA('.select_row').forEach((i)=>{i.classList.remove("select_row");});
	}
	const f = $QS('table tbody');
	const r = f.children;
	const dr = arg.getAttribute("d_root");
	const ur = arg.getAttribute("u_root");
	const udr = arg&&dr!==null&&(dr===""||~(q.get("Dir")??"").indexOf(dr));
	f.addEventListener("contextmenu", (e)=>{e.preventDefault();});
	f.addEventListener("click", (e)=>{if(g.style.display==="none")e.stopPropagation();});
	function mopen(e, n){
		e.preventDefault();
		if (move_file){
			popup.alert("別の操作を実行中です。");
			return;
		}
		if(g.style.display!=="none") mclose();
		const c = r[n];
		const a = c.querySelector('a');
		const a2 = c.querySelectorAll('a')[1];
		g.innerHTML = "";
		//編集
		const m = [['編集する',a2.href]];
		//ダウンロード
		a.getAttribute("class")==="dir"?m.unshift(['開く',a.href]):m.push(['ダウンロード',a.href+'&download']);
		//実際のURL算出(hrefに「./」使用禁止)
		if (udr){
			let u = "/";
			const cf = a.href.slice(a.href.indexOf("=")+1);
			dr===""||cf===dr? u+=cf.replace(dr,""):u+=cf.replace(dr+"/","");
			if(~u.indexOf("&")) u=u.slice(0, u.indexOf("&"));
			if(u!=="/") u+="/";
			if(a.getAttribute("class")!=="dir") u+=a.textContent;
			if(ur) u=ur+u;
			m.push(["WEBページとして開く",u,1]);
		}
		const s = "color:#111;padding:.2em;display:block;";
		m.forEach((i)=>{
			const o = document.createElement("a");
			o.innerHTML = i[0];
			o.href = i[1];
			o.style = s;
			if(i[2]) o.target = "_blank";
			g.append(o);
		});
		const o = document.createElement("a");
		o.innerHTML = "削除";
		o.href = "#";
		o.style = s;
		o.addEventListener("click", async (e)=>{
			e.preventDefault();
			move_file = 1;
			const s = f.querySelectorAll(".select_row");
			const n = [];
			s.forEach((i)=>{
				const a = i.querySelector('a');
				n.push(a.innerHTML);
			});
			if (!(await popup.confirm('<div style="overflow-y:scroll;max-height:50vh;">'+n.join("　")+"</div> を削除しますか？ 実行すると<b>停止できません。</b>"))){
				move_file = 0;
				return;
			}
			for (let i = 0; i < s.length; ++i){
				if (s[i].getAttribute("removing")){
					popup.alert("このファイルは無効です。");
					continue;
				}
				s[i].setAttribute("removing", 1);
				try{
					const l = s[i].querySelectorAll("a")[1].href;
					const r = await fetch(l);
					const t = await r.text();
					const h = document.createElement("div");
					h.style.display = "none";
					h.innerHTML = "<form "+subrpos("<form ","</form>",t)+"</form>";
					document.body.append(h);
					const j = {"remove":"yes","submit":"edit_file","new_name":"","perm":"no","submit":h.querySelector('button[type="submit"]').value};
					h.querySelectorAll('input[type="hidden"]').forEach((k)=>{j[k.name]=k.value;});
					const p = await new URLSearchParams(j);
					await fetch(l,{
						referrer:l,
						method:"POST",
						headers:{"content-type":"application/x-www-form-urlencoded"},
						body:p.toString()
					});
					s[i].remove();
				}catch{}
			}
			location.reload();//dom更新しないと次の操作が出来ない
			//move_file = 0;
		});
		g.append(o);
		g.style.display = "block";
		g.style.left = Math.min(e.pageX-scrollX,innerWidth-g.offsetWidth)+"px";
		g.style.top = Math.min(e.pageY-scrollY,innerHeight-g.offsetHeight)+"px";
		c.classList.add("select_row");
	}
	for (let i = 1; i < r.length; ++i){
		r[i].addEventListener("click",(e)=>{r[i].classList.toggle("select_row");});
		r[i].addEventListener("contextmenu",(e)=>{mopen(e,i);});
		r[i].addEventListener("dblclick",(e)=>{
			e.preventDefault();
			r[i].querySelector("a").click();
		});
	}
	window.addEventListener("click", mclose);
	window.addEventListener("keydown", (e)=>{
		if (e.ctrlKey){
			if (e.key === "a"){
				e.preventDefault();
				for(let i=1;i<r.length;++i) r[i].classList.add("select_row");
			}
		}
	});
}

