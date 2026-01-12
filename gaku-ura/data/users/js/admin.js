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
		this.#m1.addEventListener("change", ()=>{this.editor();});
		this.#f.addEventListener("input", ()=>{this.zoom();});
		this.#w.addEventListener("change", ()=>{this.row();});
		this.#f.addEventListener("keydown", function(e){
			if(e.key=="Enter"||e.key==="Return") e.preventDefault();
		});
		this.editor();
		this.zoom();
		window.addEventListener("keydown", (e)=>{
			if(e.ctrlKey){
				if(~["s","+","-",";","="].indexOf(e.key)) e.preventDefault();
				if (e.key === "s"){
					$QS('button[type="submit"]').click();
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
			if(this.#h) $ID("text").value = this.#h.getValue();
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
				mode:"ace/mode/"+(l[f]?l[f]:f),
				theme:"ace/theme/Tomorrow"
			});
			this.zoom();
		}
		this.#h.getSession().setValue($ID("text").value);
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
			popup.alert("通常モードはこの機能を使えません。","仕方ないね");
		}
	}
}

/* 編集画面 */
if (q.get("Menu") === "edit"){
	//submitメソッドを使えるようにする
	$ID("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('button[type="submit"]').value+'">';
	//エディター
	if($ID("text")) new TextEditor();
	//削除の警告
	$ID("form").addEventListener("submit", async (e)=>{
		if ($QS('[name="remove"]:checked').value === 'yes'){
			e.preventDefault();
			if(await popup.confirm("貴方はこのファイルを<b>削除</b>しよとしています。<br>フォルダの場合は中身も含めて全て消えます。<br>本当に削除しますか？")) $ID("form").submit();
		}
	});
} else if ($ID("files")){
	/* ファイル一覧 */
	const ins_flist = (files)=>{
		const d = new DataTransfer();
		files.forEach((f)=>{d.items.add(f);});
		return d.files;
	};
	const drpA = $QS("body");
	const iptF = $ID("files");
	const drpC = drpA.style.background;
	let fc = 0;
	drpA.addEventListener("dragover", (e)=>{
		e.preventDefault();
		drpA.style.background = "#eef";
	});
	drpA.addEventListener("dragleave",()=>{drpA.style.background=drpC;});
	drpA.addEventListener("drop", (e)=>{
		e.preventDefault();
		drpA.style.background = drpC;
		const fl = e.dataTransfer.files;
		if(!fl || fl.length===0) return;
		for (let i = 0;i < fl.length;i++){
			if(!fl[i].type || fl[i].size===0) continue;
			const lb = document.createElement("span");
			const ipt = document.createElement("input");
			ipt.type = "file";
			ipt.name = "file"+fc;
			ipt.style.display = "none";
			lb.style = "font-size:.8em;display:inline-block;border:solid 1px #eee;background:#f0c;color:#111;margin:.2em;";
			lb.innerHTML = fl[i].name;
			lb.append(ipt);
			ipt.files = ins_flist([fl[i]]);
			iptF.appendChild(lb);
			fc++;
			lb.addEventListener("click",(e)=>{e.preventDefault();});
		}
		if(fc > 20) popup.alert("ファイル数が20を超えています。処理が門前払いされるかもしれません。");
	});

	/* 操作メニュー */
	const cm = document.createElement("pre");
	cm.style = "background:#fff;position:fixed;display:none;";
	$QS("body").append(cm);
	const mclose = ()=>{
		cm.style.display="none";
		cm.innerHTML = "";
		$QSA('.select_row').forEach((i)=>{
			i.classList.remove("select_row");
		});
	};
	const f = $QS('table tbody');
	const fe = f.children;
	const fl = fe.length;
	const arg = $ID("gaku-ura_args");
	const d_root = arg.getAttribute("d_root");
	const u_root = arg.getAttribute("u_root");
	const udr = arg&&d_root!==null&&(d_root===""||~(q.get("Dir")?q.get("Dir"):"").indexOf(d_root));
	f.addEventListener("contextmenu", function(e){e.preventDefault();});
	for (let i = 0; i < fl; ++i){
		const c = fe[i];
		const a = c.querySelector('a');
		const _ = c.querySelectorAll('a');
		if(!a||!_[1]||c.querySelector('[colspan]')) continue;
		const a2 = _[1];
		c.addEventListener("contextmenu", (e)=>{
			e.preventDefault();
			mclose();
			//編集
			const m = [['編集する',a2.href]];
			//ダウンロード
			a.getAttribute("class")==="dir"?m.unshift(['開く',a.href]):m.push(['ダウンロード',a.href+'&download']);
			//実際のURL算出(hrefに「./」使用禁止)
			if (udr){
				let u = "/";
				const cf = a.href.slice(a.href.indexOf("=")+1);
				d_root===""||cf===d_root? u+=cf.replace(d_root,""):u+=cf.replace(d_root+"/","");
				if(~u.indexOf("&")) u=u.slice(0, u.indexOf("&"));
				if(u!=="/") u+="/";
				if(a.getAttribute("class")!=="dir") u+=a.textContent;
				if(u_root) u=u_root+u;
				m.push(["WEBページとして開く",u,1]);
			}
			m.forEach((i)=>{
				const o = document.createElement("a");
				o.innerHTML = i[0];
				o.href = i[1];
				o.style = "color:#111;padding:.2em;display:block;";
				if(i[2]) o.target = "_blank";
				cm.append(o);
			});
			cm.style.display = "block";
			cm.style.left = Math.min(e.pageX-scrollX,innerWidth-cm.offsetWidth)+"px";
			cm.style.top = Math.min(e.pageY-scrollY,innerHeight-cm.offsetHeight)+"px";
			c.classList.add("select_row");
		});
		c.addEventListener("dblclick", (e)=>{
			e.preventDefault();
			a.click();
		});
	}
	window.addEventListener("click", mclose);
}

