<?php
#mbstringが使えないときの代替関数
if (!extension_loaded('mbstring')){
#CP932をUTF8に変換するのが主な目的で、実際のmb_convert_encodingとは仕様が異なります
function mb_convert_encoding(string $s, string $to_encode):string{
	foreach (['UTF-8','SJIS','CP932','EUC-JP','ISO-2022-JP','UTF-7','UTF-16LE','UTF-16BE','ISO-8859-1','ISO-8859-2','ISO-8859-3','ISO-8859-4','ISO-8859-5','ISO-8859-6','ISO-8859-7','ISO-8859-8','ISO-8859-9','ISO-8859-10','ISO-8859-11','ISO-8859-12','ISO-8859-3','ISO-8859-14','ISO-8859-15','US-ASCII','GB2312','GBK','GB18030','BIG5','KOI8-R','KOI8-U'] as $e){
		$c = iconv($e, $e.'//IGNORE', $s);
		if ($c!==false && $c===$s){
			if($e===$to_encode) return $s;
			$o = iconv($e, $to_encode.'//IGNORE', $s);
			if($o!==false) return $o;
		}
	}
	return $s;
}
}

