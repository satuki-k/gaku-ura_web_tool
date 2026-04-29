<?php
#gaku-ura9.7.14
/*
 * 計算系
 * このファイルは独立で使用出来ます
 * conf.phpに依存しません
*/

#複数ページを区切り範囲で取り出す(ページネーション,階級生成)
#総数,分割量,n番目のページ(1スタート),重なり無し
#配列インデックス向けの[left,right,個数]配列を返す エラー判定は空配列
function pages(int $n, int $step, int $pgid, bool $uniq=false):array{
	if($pgid<1||$step<1||$n<1) return[];
	$l = ($pgid-1)*$step;
	if($l >= $n) return [];
	$s = (int)($n/$step);
	if($n%$step) $s+=1;
	$r = $pgid*$step;
	if ($r >= $n){
		if(!$uniq) $l-=$r-$n+1;
		$r = $n;
	}
	return [$l,$r,$s];
}

# =0になるxを探索
#関数,解の見当負,解の見当正,精度
#regula falsi法
function RegulaFalsi(callable $f, float $left, float $right, float $ep):float{
	$c = 0;
	for ($i = 0;$i < 100;++$i){
		$fa = $f($left);
		$fb = $f($right);
		$c = ($left*$fb -$right*$fa)/($fb -$fa);
		$fc = $f($c);
		if ($fa*$fc < 0.0){
			$right = $c;
		} else {
			$left = $c;
		}
		if(abs($fc)<$ep) break;
	}
	return $c;
}

