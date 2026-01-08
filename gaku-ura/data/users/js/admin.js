/* 管理機能 */
#!include element.js;
#!include popup.js;
#!include string.js;
const q = (new URL(document.location)).searchParams;
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
				u += cf.replace(d_root+((d_root===""||cf===d_root)?"/":""),"");
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

