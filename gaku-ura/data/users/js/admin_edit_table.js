/* 管理機能 データベース */
#!include element.js;
#!include string.js;
#!include reload_csrf.js;
#!include keyboard.js;
const q = (new URL(document.location)).searchParams;
//submitメソッドを使えるようにする
$ID("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('#form [type="submit"]').value+'">';
const dbtype = $QS('input[name="dbtype"]').value;
function mkt(e){
	e.preventDefault();
	const mem_s = document.body.style.overflowY;
	document.body.style.overflowY = "hidden";
	const h = document.createElement("div");
	const b = document.createElement("p");
	const c = document.createElement("button");
	const d = document.createElement("button");
	const m = document.createElement("button");
	const f = document.createElement("div");
	const tn = document.createElement("input");
	const tl = document.createElement("lable");
	const isid = document.createElement("input");
	const isidl = document.createElement("lable");
	const tb = document.createElement("table");
	const tr = document.createElement("tr");
	const thead = document.createElement("thead");
	const tbody = document.createElement("tbody");
	const error_msg = document.createElement("p");
	const col_list = ["text","select","checkbox","text(1)"];
	const bs = "padding:.2em;font-size:1em;color:#fff;background:#00e;";
	const ps = "background:#fff;color:#000;";
	h.style = "position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;color:#000;overflow-y:scroll;";
	b.style = "display:flex;margin-top:1em;";
	f.style = "padding:1em;";
	tn.style = ps;
	error_msg.style = "color:#ae3803;";
	tn.type = "text";
	tn.name = "new_name";
	isid.type = "checkbox";
	isid.name = "isadd_id";
	isid.value = isid.checked = true;
	c.style = d.style = m.style = bs;
	m.style.background = "#0f0";
	m.style.marginLeft = "1em";
	c.textContent = "☓キャンセル";
	d.textContent = "↵作　成";
	m.textContent = "+列を追加";
	b.append(c);
	b.append(d);
	tl.innerHTML = "name:";
	tl.append(tn);
	isidl.innerHTML = " PRIMARY KEYを作成:";
	isidl.append(isid);
	h.innerHTML = "<h2>SQL文を作成</h2>";
	h.append(error_msg);
	["col","type","not null","default value"].forEach((i)=>{
		const th = document.createElement("th");
		th.textContent = i;
		tr.append(th);
	});
	thead.append(tr);
	tb.append(thead);
	tb.append(tbody);
	const mkcol = ()=>{
		const tr = document.createElement("tr");
		col_list.forEach((j)=>{
			const i = remove_comment_rows(j, "(", ")");
			const td = document.createElement("td");
			if (i === "select"){
				const p  = document.createElement(i);
				p.name = j;
				["TEXT","INTEGER","REAL","BOOL","INT","BLOB","DATE","NUMERIC"].forEach((j)=>{
					const o = document.createElement("option");
					o.value = o.textContent = j;
					p.append(o);
				});
				td.append(p);
			} else {
				const p = document.createElement("input");
				p.name = j;
				p.type = i;
				p.style = ps;
				td.append(p);
				if(i==="text") p.addEventListener("keydown", (e)=>{
					if(isEnterKey(e)) mkcol();
					if ((e.keyCode===8||e.keyCode===46)&&p.value===""&&tbody.children.length>1){
						e.preventDefault();
						tr.remove();
						tbody.querySelector('input').focus();
					}
				});
			}
			tr.append(td);
			tr.querySelector('input').focus();
		});
		tbody.append(tr);
	};
	m.addEventListener("click", mkcol);
	f.append(tl);
	f.append(isidl);
	f.append(m);
	f.append(tb);
	h.append(f);
	h.append(b);
	document.body.append(h);
	const msg = (s)=>{
		error_msg.innerHTML = s;
	};
	const back = ()=>{
		h.remove();
		document.body.style.overflowY = mem_s;
	};
	c.addEventListener("click", back);
	d.addEventListener("click", ()=>{
		if (tn.value === ""){
			msg("tableの名前が入力されていません。");
			return tn.focus();
		}
		const tname = tn.value;
		const c = tbody.children;
		const cols = [];
		for (let i = 0;i < c.length;++i){
			let disable = 0;
			const col = [];
			col_list.forEach((k)=>{
				const j = remove_comment_rows(k, "(", ")");
				const v = c[i].querySelector((j==="select"?j:"input")+'[name="'+k+'"]');
				//最初の項目は必須
				if (disable || !v || (k===col_list[0]&&v.value==="")){
					return disable = 1;
				} else {
					const d = j==="checkbox"?v.checked:v.value;
					col.push(d);
				}
			});
			if(!disable) cols.push(col);
		}
		let sql = "CREATE TABLE IF NOT EXISTS "+tn.value+" (";
		if (isid.checked){
			if (dbtype === "sqlite"){
				sql += "id INTEGER PRIMARY KEY AUTOINCREMENT,";
			} else {
				sql += "id INT PRIMARY KEY AUTO_INCREMENT,";
			}
		}
		const d = [];
		cols.forEach((i)=>{
			let c = i[0]+" "+i[1];
			if(i[2]) c+=" NOT NULL";
			if(i[3]!=="") c+=" DEFAULT "+i[3];
			d.push(c);
		});
		sql += d.join(", ")+");";
		const i = $QS('[name="query"]');
		i.value = sql;
		back();
		i.focus();
	});
	mkcol();
	tn.focus();
}
const p = document.createElement("p");
const mt = document.createElement("button");
const mf = document.createElement("select");
const tn = $QS('input[name="table"]').value;
const fn = {
	"SQL文>":"",
	"エクスポート(export)":"",
	"インポート(import)":"",
	"列を削除":"ALTER TABLE "+tn+" DROP COLUMN 列;",
	"列を追加":"ALTER TABLE "+tn+" ADD COLUMN 列 型;",
	"カウント":"SELECT COUNT(列)FROM "+tn+";",
	"合計":"SELECT SUM(列)FROM "+tn+";"};
mf.name = "sql_fn";
for (const [k,v] of Object.entries(fn)){
	const o = document.createElement("option");
	o.value = o.textContent = k;
	mf.append(o);
}
mt.textContent = "+table作成";
mt.style = "background:#0e0;color:#fff;";
p.append(mf);
p.append(mt);
$ID("form").before(p);
mt.addEventListener("click", mkt);
mf.addEventListener("change", ()=>{
	const v = mf.value;
	mf.selectedIndex = 0;
	const i = $QS('[name="query"]');
	const s = subrpos("(",")", v);
	if(tn&&($QS('input[name="export"]').checked=(s==="export"))) return $QS('[type="submit"]').click();
	if(s==="import") return $QS('input[type="file"]').click();
	if (tn){
		i.value = fn[v];
		i.focus();
	}
});
$QS('select[name="ctable"]').addEventListener("change", ()=>{$QS('[type="submit"]').click();});
$ID("form").addEventListener("submit", async (e)=>{
	e.preventDefault();
	if(await reload_csrf("session_token")) $ID("form").submit();
});

