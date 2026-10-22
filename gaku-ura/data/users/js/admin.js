/* 管理機能 */
#!include element.js;
#!include popup.js;
#!include string.js;
#!include keyboard.js;
#!include reload_csrf.js;
const q = (new URL(window.location)).searchParams;
const popup = new POPUP();
let move_file = 0;
let cspan = Date.now();
//ini_get
const arg = $ID("gaku-ura_args");
const maxfc = parseInt(arg.getAttribute("max_file_uploads")??20);
const d = document.body;
const b = d.style.background;
const g = document.createElement("pre");//クリックメニュー
g.classList.add("cmenu");
document.body.append(g);
const f = $ID('file_list');
const r = f.children;
const dr = arg.getAttribute("d_root");
function prs(m){
	const f = $ID("fparea");
	f.innerHTML = m?m:"";
	m?f.classList.add("note"):f.classList.remove("note");
}
function sp(e){
	e.stopPropagation();
}
function pd(e){
	e.preventDefault();
}
function sc(){
	$QS('[type="submit"]').click();
}
async function edit_s(url, key, value){
	try{
		const l = url+"&async";
		const r = await fetch(l);
		const t = await r.json();
		const j = {"csrf_token":t["CSRF_TOKEN"],"name":t["NAME"],"submit":t["SUBMIT_TYPE"],"new_name":"","new_path":"","perm":"no"};
		j[key] = value;
		const p = await new URLSearchParams(j);
		await fetch(l,{
			referrer:l,
			method:"POST",
			headers:{"content-type":"application/x-www-form-urlencoded"},
			body:p.toString()
		});
	}catch{}
}
async function dg(e){
	if(e.type!=="dragleave") pd(e);
	d.style.background = e.type==="dragover"?"#eef":b;
	if(!e.type==="drop") return;
	if (move_file){
		popup.alert("別の操作を実行中です。");
		return;
	}
	const l = e.dataTransfer.files;
	if(l.length) prs("uploading files...");
	let n = 0;
	for (let c=0,i=0;i < l.length;++i,++c){
		move_file = 1;
		const f = l[i];
		if(f.type===""&&(f.size===0||f.size===4096)){
			await popup.alert("フォルダを検出しました。手動入力してください。");
			if (!n || (await popup.confirm("複数のフォルダはアップロード出来ません。すでに添付したフォルダを破棄しますか。"))){
				$ID("folder").click();
				n++;
			} else {
				const j = $ID("name");
				$ID("new").value = "folder";
				if(j.value!=="") j.value+="\\";
				j.value += f.name;
			}
			continue;
		}
		if (c === maxfc){
			if(await popup.confirm("ファイル数が多いです。ここまでか、これ以降のどちらを投稿しますか?<br>(ここから:"+f.name+")","ここまで","これ以降")) break;
			$ID("files").innerHTML = "";
			c = 0;
		}
		const p = document.createElement("input");
		const d = new DataTransfer();
		p.type = "file";
		p.name = "file"+i;
		p.style.display = "none";
		d.items.add(f);
		p.files = d.files;
		$ID("files").appendChild(p);
	}
	if(n) await popup.alert("確認");
	if((await reload_csrf("csrf_token"))&&l.length>0) sc();
}
function mclose(e){
	g.style.display="none";
	$QSA('.select_row').forEach((i)=>{
		i.classList.remove("select_row");
		i.removeAttribute("id");
	});
}
function fmc(e){
	if (!isCtrlKey(e) && this.classList.contains("select_row") && Date.now()-cspan>500){
		this.id = "selected_row";
		return rename(e);
	}
	if(g.style.display==="none") sp(e);
	if(!isCtrlKey(e)) mclose(e);
	this.classList.toggle("select_row");
	cspan = Date.now();
}
function fmo(e){
	if(!(~(this.getAttribute("class")??"").indexOf("select_row"))) mclose(e);
	mopen(e, this);
}
function fmd(e){
	pd(e);
	this.querySelector("a").click();
}
function fm(){
	mclose(0);
	let i = r.length;
	while (i--){
		r[i].style.userSelect = "none";
		r[i].removeEventListener("click", fmc);
		r[i].removeEventListener("contextmenu", fmo);
		r[i].removeEventListener("dblclick", fmd);
		r[i].addEventListener("click", fmc);
		r[i].addEventListener("contextmenu", fmo);
		r[i].addEventListener("dblclick", fmd);
	}
}
function rename(e){
	pd(e);
	const r = $ID("selected_row");
	const a = r.querySelectorAll("a");
	const n = document.createElement("input");
	const l = document.createElement("tr");
	const d = document.createElement("td");
	mclose(e);
	n.type = "text";
	n.style.width = "100%";
	n.value = n.placeholder = a[0].textContent;
	d.colSpan = 4;
	d.append(n);
	l.append(d);
	r.style.display = "none";
	r.after(l);
	n.setSelectionRange(0, n.value.lastIndexOf("."));
	n.focus();
	const rn = async (e)=>{
		n.onblur = null;
		n.onkeydown = null;
		if (e.key!=="Escape" && a[0].textContent !== n.value && /^[^"'&?\/\\|\[\]\*=]+$/.test(n.value)){
			await edit_s(a[1].href, "new_name", n.value);
			a[0].textContent = n.value;
		}
		l.remove();
		r.style.display = "table-row";
	};
	n.onblur = rn;
	n.onkeydown = async (e)=>{
		if(isEnterKey(e)||e.key==="Escape") await rn(e);
	};
}
function mopen(e, c){
	pd(e);
	if (move_file){
		popup.alert("別の操作を実行中です。");
		return;
	}
	if(g.style.display!=="none") mclose(e);
	const a = c.querySelector("a");
	const a2 = c.querySelectorAll("a")[1];
	g.innerHTML = '<p>'+a.textContent+'</p>';
	const m = [];
	const isdir = a.getAttribute("class") === "dir";
	if (isdir){
		m.push(['📂開く',a.href]);
		m.push(['✍編集する',a2.href]);
	} else {
		m.push(['✍開く(編集)',a2.href]);
		if(a.textContent.endsWith(".db")){
			let u = a.href.replace(/(Menu=[^&]*)/, "Menu=edit_db");
			if(!(~u.indexOf("Menu="))) u+="&Menu=edit_db";
			m.push(['📗SQLで編集',u]);
		}
		m.push(["👁ブラウザで見る",a.href+"&download",1]);
	}
	const u = a.getAttribute("url");
	if(u.length) m.push(["🔗WEBページとして開く",u,1]);
	["tar.gz","zip"].forEach((i)=>{
		m.push(["📥ダウンロード("+i+")",a.href+"&type="+i+"&download"]);
	});
	["tar.gz","zip"].forEach((i)=>{
		m.push(["この階層をﾀﾞｳﾝﾛｰﾄﾞ("+i+")","?Dir="+(q.get("Dir")??"")+"&type="+i+"&download"]);
	});
	m.forEach((i)=>{
		const o = document.createElement("a");
		o.innerHTML = i[0];
		o.href = i[1];
		if(~i[1].indexOf("&download")&&!i[2]) o.download=1;
		if(i[2]) o.target="_blank";
		g.append(o);
		o.addEventListener("click", mclose);
	});
	const o = document.createElement("a");
	o.innerHTML = "🗑削除";
	o.href = "#";
	o.addEventListener("click", async (e)=>{
		pd(e);
		g.style.display="none";
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
		prs("removing files...");
		for (let i = 0;i < s.length;++i){
			await edit_s(s[i].querySelectorAll("a")[1].href, "remove", "yes");
			s[i].remove();
			fm();
		}
		move_file = 0;
		await reload_csrf("csrf_token");
		prs(0);
	});
	if (!isdir && {EXTRACT_TYPE}.test(a.textContent)){
		const x = document.createElement("a");
		x.innerHTML = "📂すべて展開";
		x.href = "#";
		x.addEventListener("click", async (e)=>{
			pd(e);
			move_file = 1;
			const s = f.querySelectorAll(".select_row");
			const n = [];
			s.forEach((i)=>{
				const a = i.querySelector('a');
				n.push(a.innerHTML);
			});
			prs("extracting files...");
			for (let i = 0;i < s.length;++i){
				await edit_s(s[i].querySelectorAll("a")[1].href, "extract", "yes");
				fm();
			}
			move_file = 0;
			await reload_csrf("csrf_token");
			prs(0);
			location.reload();
		});
		g.append(x);
	}
	const n = document.createElement("a");
	n.innerHTML = "✍名前を変更";
	n.href = "#";
	c.id = "selected_row";
	n.addEventListener("click", rename);
	g.append(n);
	g.append(o);
	g.style.display = "block";
	g.style.left = Math.min(e.pageX-scrollX,innerWidth-g.offsetWidth)+"px";
	g.style.top = Math.min(e.pageY-scrollY,innerHeight-g.offsetHeight)+"px";
	c.classList.add("select_row");
}
function key_action(e){
	if (isCtrlKey(e)){
		if (e.key === "a"){
			pd(e);
			for(let i=0;i<r.length;++i) r[i].classList.add("select_row");
		}
	}
}
["dragover","dragleave","drop"].forEach((i)=>{d.addEventListener(i,dg);});
$ID("file").addEventListener("change", sc);
$ID("folder").addEventListener("change", sc);
$ID("group").addEventListener("change", sc);
g.addEventListener("click", sp);
$ID("POPUP").addEventListener("click", sp);
window.addEventListener("click", mclose);
window.addEventListener("keydown", key_action);
fm();

