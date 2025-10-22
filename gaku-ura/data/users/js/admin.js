/* 管理機能 */
#!include element.js;
#!include popup.js;
#!include string.js;

class TextEditor{
	#editor;
	#lid;
	#code;
	#pre;
	constructor(){
		if ($ID("fsize") || $ID("input_tab")) return;
		this.#lid = document.createElement("div");
		this.#code = document.createElement("code");
		this.#editor = document.createElement("div");
		this.#pre = {"l":0,"c":""};
		const pre = document.createElement("pre");
		const p = document.createElement("p");
		const tb = document.createElement("button");
		const es = document.createElement("button");
		tb.innerHTML = "タブをコピー(スマホ向け)";
		tb.id = "input_tab";
		es.innerHTML = "エディターが使い物にならない！ゴミ！";
		p.append(tb);
		p.append(es);
		$ID("form").prepend(p);

		//エディター初期化
		const tl = $ID("text").innerHTML.split("\n");
		this.#code.setAttribute("contenteditable", "plaintext-only");
		this.#code.innerHTML = "";
		for (let i = 0; i < tl.length; ++i){
			this.#code.innerHTML += tl[i]+"\n";
		}
		for (let i = 0; i < tl.length; ++i){
			this.#lid.innerHTML += (i+1)+"<br>";
		}
		const tlen = String(tl.length).length;
		this.#editor.style = "display:flex;background:#fff;max-height:500px;color:#000;position:relative;overflow:hidden;";
		this.#lid.style = "height:100%;background:#eee;color:#111;min-width:"+tlen+"em;";
		pre.style = "width:100%;overflow:scroll;";
		this.#code.style = "outline:none;tab-size:4;";
		pre.append(this.#code);
		this.#editor.append(this.#lid);
		this.#editor.append(pre);
		$ID("form").append(this.#editor);
		this.#lid.style.position = "relative";
		this.#lid.style.willChange = "transform";
		pre.addEventListener("scroll", ()=>{this.#lid.style.transform="translateY(-"+pre.scrollTop+"px)"});
		this.#code.addEventListener("keydown", (e)=>{this.key_in(e,"keydown")});
		this.#code.addEventListener("keyup", (e)=>{this.key_in(e,"keyup")});
		$ID("text").style.display = "none";
		//ショートカットキー
		document.addEventListener("keydown", (e)=>{
			if (e.ctrlKey){
				switch (e.key){
					case 's':
					e.preventDefault();
					document.querySelector('button[type="submit"]').click();
					break;
				}
			}
		});
		tb.addEventListener("click", (e)=>{
			e.preventDefault();
			navigator.clipboard.writeText("\t");
		});
		es.addEventListener("click", (e)=>{
			e.preventDefault();
			$ID("text").style.display = "block";
		});
		/*this.hlstring(false);*/
	}

	ins_tab(){
		const s = window.getSelection();
		const r = s.getRangeAt(0);
		const t = document.createTextNode("\t");
		r.insertNode(t);
		r.setStartAfter(t);
		r.setEndAfter(t);
		s.removeAllRanges();
		s.addRange(r);
		this.#code.focus();
	}

	/*
	__hlstring(){
		let t = $ID("text").innerHTML;
		switch ($QS('input[name="new_name"]').value.split('.').slice(-1)[0]){
			case "html":
			t = t.replace(/(&lt;)(.+)(&gt;)/g, '<span style="color:#00c;">&lt;$2&gt;</span>');
			t = t.replace(/(&lt;!--)(.*?)(--&gt;)/g, '<span style="color:#c00;">&lt;!--$2--&gt;</span>');
			this.#code.innerHTML = t;
			break;
			case "css":
			t = t.replace(/(\/\*.*?\*\/)/g, '<span style="color:#c00;">$1</span>');
			this.#code.innerHTML = t;
			break;
			case "js":
			t = t.replace(/(\/\/.*)/g, '<span style="color:#c00;">$1</span>');
			t = t.replace(/(["'`].*?["'`])/g, '<span style="color:#f60;">$1</span>');
			this.#code.innerHTML = t;
			break;
			case "php":
			t = t.replace(/(["'`].*?["'`])/g, '<span style="color:#f60;">$1</span>');
			t = t.replace(/(\/\*.*?\*\/)/g, '<span style="color:#c00;">$1</span>');
			this.#code.innerHTML = t;
			break;
			case "md":
			t = t.replace(/(#{1,6} .*)/g, '<span style="color:#00c;">$1</span>');
			this.#code.innerHTML = t;
			break;
		}
	}
	hlstring(f=true){
		try {
			const sel = window.getSelection();
			const range = sel.getRangeAt(0);
			const preSelectionRange = range.cloneRange();
			preSelectionRange.selectNodeContents(this.#code);
			preSelectionRange.setEnd(range.startContainer, range.startOffset);
			const start = preSelectionRange.toString().length;
			this.__hlstring();
			let charIndex = 0;
			const nodeStack = [this.#code];
			let node;
			let foundStart = false;
			let stop = false;
			while (!stop && (node = nodeStack.pop())){
				if (node.nodeType === 3){
					const nextCharIndex = charIndex + node.length;
					if (!foundStart && (start >= charIndex) && (start <= nextCharIndex)){
						const range = document.createRange();
						const sel = window.getSelection();
						range.setStart(node, start - charIndex);
						range.collapse(true);
						sel.removeAllRanges();
						sel.addRange(range);
						stop = true;
					}
					charIndex = nextCharIndex;
				} else {
					let i = node.childNodes.length;
					while (i--){
						nodeStack.push(node.childNodes[i]);
					}
				}
			}
		} catch {
			this.__hlstring();
		}
	}
	*/

	key_in(e, ty=null){
		if (ty === "keydown" && e.key === "Tab"){
			e.preventDefault();
			this.ins_tab();
		}
		let t = this.#code.textContent;
		if (this.#pre.c === t) return;
		this.#pre.c = t;
		if (t.slice(-1) !== "\n") t += "\n";
		const tl = t.split("\n");
		$ID("text").innerHTML = "";
		for (let i = 0; i < tl.length -1; ++i){
			$ID("text").innerHTML += h(tl[i])+"\n";
		}
		$ID("text").innerHTML += tl[tl.length -1];
		if (this.#pre.l === tl.length) return;
		this.#lid.innerHTML = "";
		for (let i = 0; i < tl.length; ++i){
			this.#lid.innerHTML += (i+1)+"<br>";
		}
		this.#pre.l = tl.length;
		this.#lid.style.minWidth = String(tl.length).length+"em";
		/*this.hlstring();*/
	}
};


//グラフィカルなポップアップ
popup = new POPUP();


/* 編集画面 */
const qs_l = (new URL(document.location)).searchParams;
if (qs_l.get("Menu") === "edit"){

	//submitメソッドを使えるようにする。buttonタグのキーと値を退避する
	$QS("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('button[type="submit"]').value+'">';

	//エディター
	if ($ID("text")) new TextEditor();

	//削除の警告
	$ID("form").addEventListener("submit", async (e)=>{
		if ($QS('[name="remove"]:checked').value === 'yes'){
			e.preventDefault();
			if (await popup.confirm("貴方はこのファイルを<b>削除</b>しよとしています。<br>フォルダの場合は中身も含めて全て消えます。<br>本当に削除しますか？")) $QS("form").submit();
		}
	});
} else if ($ID("files")){
	//複数ファイル
	function ins_flist(files){
		const d = new DataTransfer();
		files.forEach((f)=>{d.items.add(f)});
		return d.files;
	}

	const drpA = $QS("body");
	const iptF = $ID("files");
	let fc = 0;
	drpA.addEventListener("dragover", (e)=>{
		e.preventDefault();
		drpA.style.background = "#eef";
	});
	drpA.addEventListener("dragleave",()=>drpA.style.backgroundColor="");
	drpA.addEventListener("drop", (e)=>{
		e.preventDefault();
		drpA.style.background = "";
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
			lb.addEventListener("click",e=>e.preventDefault());
		}
		if (fc > 20) popup.alert("ファイル数が20を超えています。多分エラーになります。");
	});
}

