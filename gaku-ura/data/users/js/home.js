/* 主にメッセージ送信の非同期 */
#!include element.js;
#!include popup.js;
const popup = new POPUP();
let tid = 0;
/* 非同期送信 */
function clear_warning(){
	$ID("warning").innerHTML = "";
}
async function send_msg(e){
	const t = this;
	e.preventDefault();
	$ID("submit_"+t.id).style.display = "none";
	$ID("warning").innerHTML = "sending...";
	stop_load();
	await load_msg();
	const f = new FormData(t);
	const x = new XMLHttpRequest();
	f.append("async", 1);
	x.addEventListener("load", async (e)=>{
		start_load(1000);
		try{
			const j = await JSON.parse(x.responseText);
			$ID("warning").innerHTML = j["WARNING"];
			if(j["WARNING"]==="") t.reset();
		}catch{
			$ID("warning").innerHTML = "error";
		}
		$ID("submit_"+t.id).style.display = "inline";
		setTimeout(clear_warning, 5000);
	});
	x.open("POST", location.href);
	x.send(f);
}
async function edit_msg(e){
	e.preventDefault();
	if(!(await popup.confirm("このメッセージを削除しますか？<br>Do you want to remove this message?"))) return;
	stop_load();
	const d = this.id;
	await load_msg();
	$QS("#"+d+' [type="submit"]').style.display = "none";
	$ID("warning").innerHTML = "removing...";
	const f = new FormData($ID(d));
	const x = new XMLHttpRequest();
	f.append("async", 1);
	x.addEventListener("load", async (e)=>{
		start_load(1000);
		try{
			const j = await JSON.parse(x.responseText);
			$ID("warning").innerHTML = j["WARNING"];
			if(j["WARNING"]==="") $ID(d).reset();
		}catch{
			$ID("warning").innerHTML = "error";
		}
		$QS("#"+d+' [type="submit"]').style.display = "inline";
		setTimeout(clear_warning, 5000);
	});
	x.open("POST", location.href);
	x.send(f);
}

/* 新着監視 */
function start_load(span){
	tid = setInterval(load_msg, span);
}
function stop_load(){
	clearInterval(tid);
}
async function load_msg(){
	let p = await new URLSearchParams({"async":"1"});
	try{
		const res = await fetch(location.href,{
			method:"POST",
			headers:{
				"content-type":"application/x-www-form-urlencoded"
			},
			body:p.toString()
		});
		const r = await res.json();
		$ID("session_msg").innerHTML = r["SESSION_MSG"];
		$ID("thread_msg").innerHTML = r["THREAD_MSG"];
		$QSA('[name="csrf_token"]').forEach((i)=>{
			i.value = r["CSRF_TOKEN"];
		});
		$QSA('#message .edit').forEach((i)=>{
			i.addEventListener("submit", edit_msg);
		});
	}catch{}
}
$ID("session_form").addEventListener("submit", send_msg);
$ID("thread_form").addEventListener("submit", send_msg);
load_msg();
start_load(5000);

