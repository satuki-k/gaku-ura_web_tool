/* 管理機能 */
#!include element.js;
#!include popup.js;
#!include string.js;

const input_special_text = (e, char)=>{
	const pos = e.selectionStart;
	e.setRangeText(char, pos, pos, 'end');
	const event = new InputEvent("input", {bubbles:true, cancelable:true, inputType:"insertText", data:char});
	e.dispatchEvent(event);
};

class TextEditor{
	#editor;
	#lid;
	#code;
	constructor(){
		if ($ID("fsize") || $ID("input_tab")) return;
		this.#lid = document.createElement("div");
		this.#code = document.createElement("code");
		this.#editor = document.createElement("div");
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
		this.#code.setAttribute("contenteditable", "true");
		this.#code.innerHTML = "";
		for (let i = 0; i < tl.length; ++i){
			this.#code.innerHTML += tl[i]+"\n";
		}
		for (let i = 0; i < tl.length; ++i){
			this.#lid.innerHTML += (i+1)+"<br>";
		}
		this.#editor.style = "display:flex;background:#fff;max-height:500px;color:#000;overflow:scroll;";
		pre.style = "width:100%;";
		this.#lid.style = "height:100%;background:#eee;color:#111;min-width:4em;";
		this.#code.style = "display:block;outline:none;tab-size:4;";
		pre.append(this.#code);
		this.#editor.append(this.#lid);
		this.#editor.append(pre);
		$ID("form").append(this.#editor);
		this.#code.addEventListener("keydown", (e)=>{this.key_in(e, "keydown")});
		this.#code.addEventListener("keyup", (e)=>{this.key_in(e, "keyup")});
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
		this.hlstring(false);
	}

	ins_tab(){
		const sel = window.getSelection();
		const range = sel.getRangeAt(0);
		const tabNode = document.createTextNode("\t");
		range.insertNode(tabNode);
		range.setStartAfter(tabNode);
		range.setEndAfter(tabNode);
		sel.removeAllRanges();
		sel.addRange(range);
		this.#code.focus();
	}

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
		return; //undoが出来なくなるし重い
		/*
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
		*/
	}

	key_in(e, ty=null){
		if (ty === "keydown" && e.key === "Tab"){
			e.preventDefault();
			this.ins_tab();
		}
		let t = this.#code.textContent;
		if (t.slice(-1) !== "\n"){
			t += "\n";
		}
		//const tl = t.slice(0,-1).split("\n");
		const tl = t.split("\n");
		$ID("text").innerHTML = "";
		for (let i = 0; i < tl.length -1; ++i){
			$ID("text").innerHTML += h(tl[i])+"\n";
		}
		$ID("text").innerHTML += tl[tl.length -1];
		this.#lid.innerHTML = "";
		for (let i = 0; i < tl.length; ++i){
			this.#lid.innerHTML += (i+1)+"<br>";
		}
		this.hlstring();
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
} else if ($ID("files") !== null){
	//通常


	//複数ファイル
	function insert_flist(files){
		const dataTransfer = new DataTransfer();
		files.forEach(file => dataTransfer.items.add(file));
		return dataTransfer.files;
	}

	const drop_area = $QS("body");
	const input_files = $ID("files");
	let fcount = 0;
	drop_area.addEventListener("dragover", (e)=>{
		e.preventDefault();
		drop_area.style.background = "#eef";
	});

	drop_area.addEventListener("dragleave", ()=>{
		drop_area.style.backgroundColor = "";
	});

	drop_area.addEventListener("drop", (e)=>{
		e.preventDefault();

		drop_area.style.background = "";
		const files = e.dataTransfer.files;
		if (!files || (files.length === 0)) return;
		for (let i = 0;i < files.length;i++){
			const file = files[i];
			if (!file.type || file.size === 0) continue;
			const label = document.createElement("span");
			const input = document.createElement("input");
			input.type = "file";
			input.name = "file"+fcount;
			input.id = "file"+fcount;
			label.classList.add("js_finput");
			label.innerHTML = file.name+"<sup>削除</sup>";
			label.id = "l_"+input.id;
			label.append(input);
			input.files = insert_flist([file]);
			input_files.appendChild(label);
			fcount++;
			$ID(label.id).onclick = (e)=>{
				e.preventDefault();
				$ID(label.id).remove();
				fcount--;
			};
		}
		if (fcount > 20) popup.alert("ファイル数が20を超えています。多分エラーになります。");
	});
}

