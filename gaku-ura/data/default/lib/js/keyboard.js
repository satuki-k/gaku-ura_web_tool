/* macだけctrlキーじゃなかったり、エンターキーの名称がEnterとReturnある */

//ctrlキー判定(mac対応)
function isCtrlKey(e){
	return (e.ctrlKey||e.metaKey);
}

//決定キー判定
function isEnterKey(e){
	return (e.key==="Return"||e.key==="Enter");
}



