/* ポップアップ画面の作成 */

//多重includeは未然にシステムによって防止されます。(一回includeしたファイルは記録されて次回は無視されます)
#!include element.js;

class POPUP{
	#window;
	constructor(style=""){
		this.#window = document.createElement("div");
		this.#window.style = "font-size:1.2em;padding:1em;position:fixed;top:25%;left:50%;transform:translateX(-50%);width:70%;background:#fff;color:#000;box-shadow:.3em .2em #ccc;pointer-events:auto;animation:POPUP_fade .5s 1;"+style;
		this.#window.style.display = "none";
		this.#window.id = "POPUP";
		$QS("body").append(this.#window);
	}
	apear(){
		this.#window.style.display = "block";
		$QS("body").style.pointerEvents = "none";
	}
	disapear(){
		this.#window.style.display = "none";
		this.#window.innerHTML = "";
		$QS("body").style.pointerEvents = "auto";
	}


	/* 通常の警告 */
	alert(msg, ok="はーい( ´ ▽ ` )ﾉ"){
		this.apear();
		this.#window.innerHTML = "<p>"+msg+"</p>";
		const p = document.createElement("p");
		const b = document.createElement("button");
		p.style = "text-align:center;";
		b.style = "color:#000;";
		b.id = "POPUP_OK";
		b.innerHTML = ok;
		p.append(b);
		this.#window.append(p);
		b.focus();
		b.addEventListener("keydown", (e)=>{if(!(e.key==="Return"||e.key==="Enter"))e.preventDefault();});
		b.addEventListener("click", ()=>{this.disapear();});
	}

	/* 質問 */
	async confirm(msg, yes="うん分かった", no="い、、、嫌じゃ"){
		this.apear();
		this.#window.innerHTML = "<p>"+msg+"</p>";
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
		this.#window.append(p);
		y.focus();
		y.addEventListener("keydown", (e)=>{
			if (!(e.key==="Return"||e.key==="Enter")){
				if(e.key==="Tab") n.focus();
				e.preventDefault();
			}
		});
		n.addEventListener("keydown", (e)=>{
			if (!(e.key==="Return"||e.key==="Enter")){
				if(e.key==="Tab") y.focus();
				e.preventDefault();
			}
		});
		return new Promise(resolve=>{
			const e = fl=>()=>{
				this.disapear();
				y.removeEventListener("click", YES);
				n.removeEventListener("click", NO);
				resolve(fl);
			};
			const YES = e(true);
			const NO = e(false);
			y.addEventListener("click", YES);
			n.addEventListener("click", NO);
		});
	}
}

