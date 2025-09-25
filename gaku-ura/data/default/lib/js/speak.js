/* gaku-ura jsLIB speak */

function speak(elm, txt, speed, add=false){
	if (!add){
		elm.innerHTML = '';
	}
	let i = 0;
	let l = txt.length;
	let e = elm;
	let tid = setInterval(()=>{
		if (i > l){
			clearInterval(tid);
			return;
		}
		e.innerHTML += txt.substr(i, 1);
		i++;
	}, speed);
}

