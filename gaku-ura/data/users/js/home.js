/* 主にメッセージ送信の非同期 */
#!include element.js;
const ac = '<input type="hidden" name="async" value="1">';
$ID("session_form").innerHTML += ac;
$ID("thread_form").innerHTML += ac;
let tid = 0;
/* 非同期送信 */
function clear_warning(){
	$ID("warning").innerHTML = "";
}
function send_msg(e){
	const t = this;
	e.preventDefault();
	$ID("submit_"+t.id).style.display = "none";
	stop_load();
	load_msg();
	const f = new FormData(t);
	const x = new XMLHttpRequest();
	x.addEventListener("load", async (e)=>{
		start_load(1000);
		try{
			const j = await JSON.parse(x.responseText);
			$ID("warning").innerHTML = j["WARNING"];
			t.reset();
		}catch{
			$ID("warning").innerHTML = "error";
		}
		$ID("submit_"+t.id).style.display = "inline";
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
	}catch{}
}
$ID("session_form").addEventListener("submit", send_msg);
$ID("thread_form").addEventListener("submit", send_msg);
start_load(5000);

