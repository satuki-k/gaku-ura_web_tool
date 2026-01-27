/* 管理機能 */
#!include element.js;
#!include popup.js;
#!include string.js;
#!include keyboard.js;
#!include reload_csrf.js;
const q = (new URL(document.location)).searchParams;
const popup = new POPUP();
reactive_reload('session_token');
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
		if(f.type===""&&(f.size===0||f.size===4096)){
			const n = $QS('#form input[name="name"]');
			if (n.value === ""){
				n.value = f.name;
				$QS('#form select[name="new"]').value = "folder";
			}
			continue;
		}
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
const f = $QS('tbody');
const r = f.children;
const dr = arg.getAttribute("d_root");
const ur = arg.getAttribute("u_root");
const udr = arg&&dr!==null&&(dr===""||~(q.get("Dir")??"").indexOf(dr));
function mclose(e){
	g.style.display="none";
	g.innerHTML = "";
	$QSA('.select_row').forEach((i)=>{i.classList.remove("select_row");});
}
function fmc(e){
	if(g.style.display==="none") e.stopPropagation();
	if(!isCtrlKey(e)) mclose();
	this.classList.toggle("select_row");
}
function fmo(e){
	if(!(~(this.getAttribute("class")??"").indexOf("select_row"))) mclose(e);
	mopen(e, this);
}
function fmd(e){
	e.preventDefault();
	this.querySelector("a").click();
}
function fm(){
	for (let i = 1; i < r.length; ++i){
		r[i].removeEventListener("click", fmc);
		r[i].removeEventListener("contextmenu", fmo);
		r[i].removeEventListener("dblclick", fmd);
		r[i].addEventListener("click", fmc);
		r[i].addEventListener("contextmenu", fmo);
		r[i].addEventListener("dblclick", fmd);
	}
}
function mopen(e, c){
	e.preventDefault();
	if (move_file){
		popup.alert("別の操作を実行中です。");
		return;
	}
	if(g.style.display!=="none") mclose();
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
				const l = s[i].querySelectorAll("a")[1].href+"&async";
				const r = await fetch(l);
				const t = await r.text();
				const f = subrpos("<form ", "</form>", t);
				if(!f) continue;
				const h = document.createElement("div");
				h.style.display = "none";
				h.innerHTML = "<form "+f+"</form>";
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
				fm();
			}catch{}
		}
		move_file = 0;
	});
	g.append(o);
	g.style.display = "block";
	g.style.left = Math.min(e.pageX-scrollX,innerWidth-g.offsetWidth)+"px";
	g.style.top = Math.min(e.pageY-scrollY,innerHeight-g.offsetHeight)+"px";
	c.classList.add("select_row");
}
f.addEventListener("contextmenu", (e)=>{e.preventDefault();});
window.addEventListener("click", mclose);
window.addEventListener("keydown", (e)=>{
	if (isCtrlKey(e)){
		if (e.key === "a"){
			e.preventDefault();
			for(let i=1;i<r.length;++i) r[i].classList.add("select_row");
		}
	}
});
fm();


