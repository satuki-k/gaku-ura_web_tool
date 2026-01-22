/* ポップアップ画面の作成 */
class POPUP{
	#w;
	constructor(style=""){
		this.#w = document.createElement("div");
		this.#w.style = "padding:1em;position:fixed;top:25%;left:50%;transform:translateX(-50%);width:70%;background:#fff;color:#000;pointer-events:auto;"+style;
		this.#w.style.display = "none";
		this.#w.id = "POPUP";
		document.body.append(this.#w);
	}
	apear(){
		this.#w.style.display = "block";
		document.body.style.pointerEvents = "none";
	}
	disapear(){
		this.#w.style.display = "none";
		this.#w.innerHTML = "";
		document.body.style.pointerEvents = "auto";
	}
	/* 通常の警告 */
	alert(msg, ok="はーい( ´ ▽ ` )ﾉ"){
		this.apear();
		this.#w.innerHTML = "<p>"+msg+"</p>";
		const p = document.createElement("p");
		const b = document.createElement("button");
		p.style = "text-align:center;";
		b.style = "color:#000;";
		b.id = "POPUP_OK";
		b.innerHTML = ok;
		p.append(b);
		this.#w.append(p);
		b.focus();
		b.addEventListener("keydown", (e)=>{if(!(e.key==="Return"||e.key==="Enter"))e.preventDefault();});
		b.addEventListener("click", ()=>{this.disapear();});
	}
	/* 質問 */
	async confirm(msg, yes="うん分かった", no="い、、、嫌じゃ"){
		this.apear();
		this.#w.innerHTML = "<p>"+msg+"</p>";
		const p = document.createElement("p");
		const y = document.createElement("button");
		const n = document.createElement("button");
		p.style = "text-align:center;display:flex;";
		y.style = "color:#e27;";
		n.style = "color:#0b0;";
		y.id = "POPUP_YES";
		n.id = "POPUP_NO";
		y.innerHTML = yes;
		n.innerHTML = no;
		p.append(n);
		p.append(y);
		this.#w.append(p);
		y.focus();
		y.addEventListener("keydown", (e)=>{
			if (e.key!=="Return"&&e.key!=="Enter"){
				e.preventDefault();
				if(e.key==="Tab") n.focus();
			}
		});
		n.addEventListener("keydown", (e)=>{
			if (e.key!=="Return"&&e.key!=="Enter"){
				e.preventDefault();
				if(e.key==="Tab") y.focus();
			}
		});
		return new Promise((r)=>{
			const e = (f)=>()=>{
				y.removeEventListener("click", Y);
				n.removeEventListener("click", N);
				this.disapear();
				r(f);
			};
			const Y = e(true);
			const N = e(false);
			y.addEventListener("click", Y);
			n.addEventListener("click", N);
		});
	}
}

