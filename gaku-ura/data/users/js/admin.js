/* 管理機能 */
#!include element.js; //要素取得メソッドの短縮
#!include popup.js;

const input_special_text = (e, char)=>{
	const pos = e.selectionStart;
	e.setRangeText(char, pos, pos, 'end');
	const event = new InputEvent("input", {bubbles:true, cancelable:true, inputType:"insertText", data:char});
	e.dispatchEvent(event);
};

const text_editor = ()=>{
	//プレビュー
	$ID("form").innerHTML = '<p><label>サイズ変更<select id="fsize" style="width:5em;"></select></label>　<button id="input_tab">タブを入力</button></p>'+$ID("form").innerHTML;
	for (let i = 1;i <= 30;++i){
		$ID("fsize").innerHTML += '<option value="'+i+'">'+i+'px</option>';
	}

	//拡大縮小
	$ID("fsize").addEventListener("change", function (){
		$ID("text").style.fontSize = ($ID("fsize").selectedIndex) +"px";
	});

	//タブ
	$ID("text").addEventListener("keydown", function (e){
		if (e.key !== "Tab"){
			return;
		}
		e.preventDefault();
		input_special_text($ID("text"), "\t");
	});

	$ID("input_tab").addEventListener("click", function (e){
		e.preventDefault();
		if ($QS("#input_tab:hover") !== null){
			$ID("text").focus();
			input_special_text($ID("text"), "\t");
		}
	});

	//入力
	document.addEventListener("keydown", function (e){

		//コントロール改変
		if (e.ctrlKey){
			switch (e.key){
				case 's':
				e.preventDefault();
				document.querySelector('button[type="submit"]').click();
				break;
			}
		}
	});
};



//グラフィカルなポップアップ
popup = new POPUP();


/* 編集画面 */
const qs_l = (new URL(document.location)).searchParams;
if (qs_l.get("Menu") !== null && qs_l.get("Menu") === "edit"){

	//submitメソッドを使えるようにする。buttonタグのキーと値を退避する
	$QS("form").innerHTML += '<input type="hidden" name="submit_type" value="'+$QS('button[type="submit"]').value+'">';

	//エディター
	if ($ID("text") !== null){
		text_editor();
	}

	//削除の警告
	$ID("form").addEventListener("submit", async function(e){
		if ($QS('[name="remove"]:checked').value === 'yes'){
			e.preventDefault();
			if (await popup.confirm("貴方はこのファイルを<b>削除</b>しよとしています。<br>フォルダの場合は中身も含めて全て消えます。<br>本当に削除しますか？")){
				$QS("form").submit();
			}
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

	const drop_area = $QS('body');
	const input_files = $ID('files');
	let fcount = 0;
	drop_area.addEventListener('dragover', (e)=>{
		e.preventDefault();
		drop_area.style.background = "#eef";
	});

	drop_area.addEventListener('dragleave', ()=>{
		drop_area.style.backgroundColor = "";
	});

	drop_area.addEventListener('drop', (e)=>{
		e.preventDefault();

		drop_area.style.background = "";
		const files = e.dataTransfer.files;
		if (!files || (files.length === 0)){
			return;
		}
		for (let i = 0;i < files.length;i++){
			const file = files[i];
			if (!file.type || file.size === 0){
				continue;
			}
			const label = document.createElement('span');
			const input = document.createElement('input');
			input.type = 'file';
			input.name = 'file'+fcount;
			input.id = 'file'+fcount;
			label.classList.add('js_finput');
			label.innerHTML = file.name+'<sup>削除</sup>';
			label.id = 'l_'+input.id;
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
		if (fcount > 20){
			popup.alert("ファイル数が20を超えています。多分エラーになります。");
		}
	});
}

