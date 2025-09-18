/* ポップアップ画面の作成 */

//多重includeは未然にシステムによって防止されます。(一回includeしたファイルは記録されて次回は無視されます)
#!include element.js;

class POPUP{
	constructor(style=""){
		this.window = document.createElement("div");
		this.window.style = "font-size:1.2em;padding:1em;position:fixed;top:25%;left:50%;transform:translateX(-50%);width:70%;background:#fff;color:#000;box-shadow:2px 2px #ccc;pointer-events:auto;animation:POPUP_fade .5s 1;"+style;
		this.window.style.display = 'none';
		$QS("body").append(this.window);
	}
	apear(){
		this.window.style.display = 'block';
		$QS('body').style.pointerEvents = 'none';
	}
	disapear(){
		this.window.style.display = 'none';
		$QS('body').style.pointerEvents = 'auto';
	}
	alert(msg, ok="はーい( ´ ▽ ` )ﾉ"){
		this.apear();
		this.window.innerHTML = '<p>'+msg+'</p><p style="text-align:center;"><button id="POPUP_OK" style="color:#000;">'+ok+'</button></p>';
		$ID('POPUP_OK').onclick = ()=>{
			this.disapear();
		};
	}
	async confirm(msg, yes="うん分かった", no="い、、、嫌じゃ"){
		this.apear();
		this.window.innerHTML = '<p>'+msg+'</p><p style="text-align:center;display:flex;"><button id="POPUP_NO" style="color:#0b0;">'+no+'</button><button id="POPUP_YES" style="COLOR:#e27;">'+yes+'</button></p>';
		return new Promise(resolve=>{
			const e = fl=>()=>{
				this.disapear();
				$ID("POPUP_YES").removeEventListener("click", YES);
				$ID("POPUP_NO").removeEventListener("click", NO);
				resolve(fl);
			};
			const YES = e(true);
			const NO = e(false);
			$ID("POPUP_YES").addEventListener("click", YES);
			$ID("POPUP_NO").addEventListener("click", NO);
		});
	}
}


