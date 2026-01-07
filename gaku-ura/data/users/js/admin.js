/* 管理機能 */
#!include element.js;
#!include popup.js;
#!include string.js;
const qs_l = (new URL(document.location)).searchParams;
//グラフィカルなポップアップ
const popup = new POPUP();
class TextEditor{
	#editor;
	#lid;
	#code;
	#pre;
	constructor(){
		$ID("text").style.display = "none";
		this.#lid = document.createElement("div");
		this.#code = document.createElement("code");
		this.#editor = document.createElement("div");
		this.#pre = {"l":0,"c":""};
		const pr = document.createElement("pre");
		const p = document.createElement("p");
		const tb = document.createElement("button");
		const es = document.createElement("button");
		const fsl = document.createElement("label");
		const fs = document.createElement("input");
		const fsd = document.createElement("button");
		tb.type = "button";
		es.type = "button";
		tb.innerHTML = "タブ文字をコピー";
		es.innerHTML = "従来版に切り替え";
		fsl.textContent = "文字(px)";
		fs.type = "number";
		fs.style = "width:3em;background:#fff;color:#000;font-size:1em;";
		fs.value = 18;
		fsl.style = "padding-left:1em;";
		fsd.type = "button";
		fsd.innerHTML = "決定";
		fsl.append(fs);
		fsl.append(fsd);
		p.append(tb);
		p.append(es);
		p.append(fsl);
		$ID("form").prepend(p);
		//エディター初期化
		const tl = $ID("text").innerHTML.split("\n");
		this.#code.setAttribute("contenteditable", "plaintext-only");
		this.#code.innerHTML = "";
		for(let i=0;i<tl.length;++i) this.#code.innerHTML+=tl[i]+"\n";
		for(let i=1;i<=tl.length;++i) this.#lid.innerHTML+=i+"<br>";
		const tlen = String(tl.length).length;
		this.#editor.style = "display:flex;max-height:500px;position:relative;overflow:hidden;font:1em/1.4 serif;";
		this.#lid.style = "min-width:"+tlen+"em;height:100%;background:#eee;color:#111;padding-bottom:1em;";
		pr.style = "width:100%;overflow:scroll;background:#fff;color:000;";
		this.#code.style = "outline:none;tab-size:4;color:#000;";
		pr.style.font = this.#editor.style.font;
		this.#lid.style.font = pr.style.font;
		this.#code.style.font = pr.style.font;
		pr.append(this.#code);
		this.#editor.append(this.#lid);
		this.#editor.append(pr);
		$ID("form").append(this.#editor);
		pr.addEventListener("scroll", ()=>{this.#lid.style.transform="translateY(-"+pr.scrollTop+"px)";});
		this.#code.addEventListener("keydown", (e)=>{this.key_in(e,"keydown");});
		this.#code.addEventListener("keyup", (e)=>{this.key_in(e,"keyup");});
		//ショートカットキー
		document.addEventListener("keydown", (e)=>{
			if (e.ctrlKey && e.key==="s"){
				e.preventDefault();
				$QS('button[type="submit"]').click();
			}
		});
		tb.addEventListener("click", ()=>{navigator.clipboard.writeText("\t");});
		es.addEventListener("click", ()=>{$ID("text").style.display="block";});
		fs.addEventListener("keydown", function(e){
			if (e.key=="Enter" || e.key==="Return") e.preventDefault();
		});
		fsd.addEventListener("click", ()=>{
			if (fs.value > 0 && fs.value < 100){
				pr.style.font = fs.value+"px/1.4 serif";
				this.#editor.style.font = pr.style.font;
				this.#lid.style.font = pr.style.font;
				this.#code.style.font = pr.style.font;
			} else {
				popup.alert("1以上100未満にしてください。");
			}
		});
	}
	key_in(e, ty=null){
		if (ty === "keydown" && e.key === "Tab" && !e.shiftKey){
			e.preventDefault();
			const s = window.getSelection();
			const r = s.getRangeAt(0);
			const b = document.createTextNode("\t");
			r.insertNode(b);
			r.setStartAfter(b);
			r.setEndAfter(b);
			s.removeAllRanges();
			s.addRange(r);
		}
		let t = this.#code.textContent;
		if (this.#pre.c === t) return;
		this.#pre.c = t;
		if (t.slice(-1) !== "\n") t += "\n";
		const tl = t.split("\n");
		$ID("text").innerHTML = h(t.slice(0, -1));
		if (this.#pre.l === tl.length) return;
		this.#lid.innerHTML = "";
		for(let i=1;i<=tl.length;++i) this.#lid.innerHTML+=i+"<br>";
		this.#pre.l = tl.length;
		this.#lid.style.minWidth = String(tl.length).length+"em";
	}
}

/* 編集画面 */
if (qs_l.get("Menu") === "edit"){
	//submitメソッドを使えるようにする
	$ID("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('button[type="submit"]').value+'">';
	//エディター
	if ($ID("text")) new TextEditor();
	//削除の警告
	$ID("form").addEventListener("submit", async (e)=>{
		if ($QS('[name="remove"]:checked').value === 'yes'){
			e.preventDefault();
			if (await popup.confirm("貴方はこのファイルを<b>削除</b>しよとしています。<br>フォルダの場合は中身も含めて全て消えます。<br>本当に削除しますか？")) $ID("form").submit();
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
		if (!fl || (fl.length === 0)) return;
		for (let i = 0;i < fl.length;i++){
			if (!fl[i].type || fl[i].size === 0) continue;
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
		if (fc > 20) popup.alert("ファイル数が20を超えています。処理が門前払いされるかもしれません。");
	});

	const cm = document.createElement("pre");
	cm.id = "CLICK_MENU";
	cm.style = "background:#fff;border:#aaa;position:absolute;display:none;";
	$QS("body").append(cm);
	const close_menu = ()=>{
		cm.style.display="none";
		$QSA('.select_row').forEach((i)=>{
			i.classList.remove("select_row");
		});
	};
	const fl = $QS('table tbody');
	const fl_e = fl.children;
	const fl_l = fl_e.length;
	const args_e = $ID("gaku-ura_args");
	const d_root = args_e.getAttribute("d_root");
	const u_root = args_e.getAttribute("u_root");
	let uri_dir = qs_l.get("Dir");
	if (!uri_dir){
		uri_dir = "";
	}
	let under_d_root = false;
	if (args_e && d_root!==null && (d_root===""||~uri_dir.indexOf(d_root))){
		under_d_root = true;
	}
	fl.addEventListener("contextmenu", function(e){e.preventDefault();});
	for (let i = 0; i < fl_l; ++i){
		const c = fl_e[i];
		const c_e = c.querySelector('a');
		const __c_e2 = c.querySelectorAll('a');
		if (!c_e||!__c_e2[1]||c.querySelector('[colspan]')) continue;
		const c_e2 = __c_e2[1];
		c.addEventListener("contextmenu", (e)=>{
			e.preventDefault();
			close_menu();
			cm.innerHTML = "";
			//編集
			const m = [['編集する',c_e2.href,""]];
			//ダウンロード
			if (c_e.getAttribute("class") === "dir"){
				m.unshift(['開く',c_e.href,""]);
			} else {
				m.push(['ダウンロード',c_e.href+'&download',""]);
			}
			//実際のURL算出(hrefに「./」使用禁止)
			if (under_d_root){
				let u = "/";
				const cf = c_e.href.slice(c_e.href.indexOf("=")+1);
				if (d_root==="" || cf===d_root){
					u += cf.replace(d_root,"");
				} else {
					u += cf.replace(d_root+"/","");
				}
				if (~u.indexOf("&")) u=u.slice(0, u.indexOf("&"));
				if (u!=="/") u+="/";
				if (c_e.getAttribute("class") !== "dir"){
					u += c_e.textContent;
				}
				if (u_root) u=u_root+u;
				m.push(["WEBページとして開く",u,"",null,1]);
			}
			m.forEach((i)=>{
				const p = document.createElement("p");
				const a = document.createElement("a");
				p.style = "display:block;font-size:1.2em;padding:.5em;";
				a.innerHTML = i[0];
				a.href = i[1];
				a.style = i[2];
				a.style.color = "#111";
				a.style.display = "block";
				a.style.width = "100%";
				if (i[4]) a.target = "_blank";
				p.append(a);
				cm.append(p);
			});
			cm.style.display = "block";
			cm.style.left = Math.min(e.pageX,window.innerWidth-cm.offsetWidth-10)+"px";
			cm.style.top = Math.min(e.pageY,window.innerHeight-cm.offsetHeight-10)+"px";
			c.classList.add("select_row");
		});
		c.addEventListener("dblclick", (e)=>{
			e.preventDefault();
			c_e.click();
		});
	}
	window.addEventListener("click", close_menu);
}

