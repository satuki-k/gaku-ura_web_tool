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
		tb.type = "button";
		es.type = "button";
		tb.innerHTML = "タブ文字をコピー";
		es.innerHTML = "従来版の編集モード";
		p.append(tb);
		p.append(es);
		$ID("form").prepend(p);
		//エディター初期化
		const tl = $ID("text").innerHTML.split("\n");
		this.#code.setAttribute("contenteditable", "plaintext-only");
		this.#code.innerHTML = "";
		for (let i = 0; i < tl.length; ++i) this.#code.innerHTML+=tl[i]+"\n";
		for (let i = 0; i < tl.length; ++i) this.#lid.innerHTML += (i+1)+"<br>";
		const tlen = String(tl.length).length;
		this.#editor.style = "display:flex;background:#fff;max-height:500px;color:#000;position:relative;overflow:hidden;";
		this.#lid.style = "height:100%;background:#eee;color:#111;min-width:"+tlen+"em;";
		pr.style = "width:100%;overflow:scroll;";
		this.#code.style = "outline:none;tab-size:4;";
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
		for (let i = 0; i < tl.length; ++i){
			this.#lid.innerHTML += (i+1)+"<br>";
		}
		this.#pre.l = tl.length;
		this.#lid.style.minWidth = String(tl.length).length+"em";
	}
};

/* 編集画面 */
if (qs_l.get("Menu") === "edit"){
	//エディター
	if ($ID("text")) new TextEditor();
	//submitメソッドを使えるようにする
	$ID("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('button[type="submit"]').value+'">';
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
		if (fc > 20) popup.alert("ファイル数が20を超えています。多分エラーになります。");
	});

	const click_menu = document.createElement("div");
	click_menu.id = "CLICK_MENU";
	click_menu.style = "background:#fff;border:#aaa;padding:.2em;position:absolute;display:none;";
	$QS("body").append(click_menu);
	const close_menu = ()=>{
		click_menu.style.display="none";
		$QSA('[class="select_row"]').forEach((i)=>{
			i.classList.remove("select_row");
		});
	};
	const file_list_e = $QS('table tbody').children;
	const file_list_len = file_list_e.length;
	const d_root_e = $ID("d_root");
	let uri_dir = qs_l.get("Dir");
	if (!uri_dir){
		uri_dir = "";
	}
	let under_d_root = false;
	if (d_root_e && d_root_e.href && (d_root_e.href.slice(d_root_e.href.indexOf("=")+1)===""||~uri_dir.indexOf(d_root_e.href.slice(d_root_e.href.indexOf("=")+1)))){
		under_d_root = true;
	}
	for (let i = 0; i < file_list_len; ++i){
		const cels = file_list_e[i];
		const cels_e = cels.querySelector('a');
		const __cels_e2 = cels.querySelectorAll('a');
		if (!cels_e||!__cels_e2[1]||cels.querySelector('[colspan]')) continue;
		const cels_e2 = __cels_e2[1];
		cels.addEventListener("contextmenu", (e)=>{
			e.preventDefault();
			close_menu();
			click_menu.innerHTML = "";
			click_menu.style.left = e.pageX+"px";
			click_menu.style.top = e.pageY+"px";
			//編集
			const m = [['編集する',cels_e2.href,""]];
			//ダウンロード
			if (cels_e.getAttribute("class") !== "dir"){
				m.push(['ダウンロード',cels_e.href+'&download',""]);
			}
			//実際のURL算出(hrefに「./」使用禁止)
			if (under_d_root){
				const d_root = d_root_e.href;
				let u = "/";
				if (d_root.slice(d_root.indexOf("=")+1)==="" || subrpos("=","&",cels_e.href)===d_root.slice(d_root.indexOf("=")+1)){
					u += cels_e.href.replace(d_root,"");
				} else {
					u += cels_e.href.replace(d_root+"/","");
				}
				if (~u.indexOf("&")) u=u.slice(0, u.indexOf("&"));
				if (u!=="/") u+="/";
				if (cels_e.getAttribute("class") !== "dir"){
					u += cels_e.textContent;
				}
				m.push(["WEBページとして開く",u,"",null,1]);
			}
			m.forEach((i)=>{
				const p = document.createElement("p");
				const a = document.createElement("a");
				p.style = "display:block;font-size:1.2em;";
				a.innerHTML = i[0];
				a.href = i[1];
				a.style = i[2];
				a.style.color = "#111";
				a.style.display = "block";
				a.style.width = "100%";
				if (i[4]) a.target = "_blank";
				p.append(a);
				click_menu.append(p);
			});
			click_menu.style.display = "block";
			cels.classList.add("select_row");
		});
	}
	window.addEventListener("click", close_menu);
}

