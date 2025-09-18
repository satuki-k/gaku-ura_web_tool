/* 文字列ツール */


function h(str){
	return str
	.replace(/&/g, '&amp;')
	.replace(/</g, '&lt;')
	.replace(/>/g, '&gt;')
	.replace(/"/g, '&quot;')
	.replace(/'/g, '&#39;');
}

function subrpos(start, end, txt){
	let s = txt.indexOf(start);
	let e = txt.indexOf(end, s);
	if (s !== -1 && e !== -1){
		return txt.slice(s +start.length, e);
	} else {
		return '';
	}
}

function remove_comment_rows(code, s='<!--', e='-->'){
	while (subrpos(s, e, code) !== ''){
		code = code.replace(s+subrpos(s, e, code)+e, '');
	}
	return code;
}

