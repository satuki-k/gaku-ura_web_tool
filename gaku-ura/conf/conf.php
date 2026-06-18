<?php
#gaku-ura標準lib
const GAKU_URA_VERSION = '9.8.0';
#mbstringの代替関数を使うときは以下のコメントを外す
//include __DIR__ .'/alt-mbstring.php';
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
#UTF-8/LFにする
function lf(string $s):string{return str_replace(["\r\n","\r"],"\n",$s);}
function u8(string $s):string{return mb_convert_encoding($s,'UTF-8');}
function u8lf(string $s):string{return lf(u8($s));}
#埋め込みとjs禁止エスケープ
function expelliarmus(string $s):string{
	foreach (['script','iframe','frame','embed','object'] as $t){
		$s = str_ireplace('</'.$t.'>', '', $s);
		while(($a=stripos($s,'<'.$t))!==false&&($b=stripos($s,'>',$a))!==false) $s=str_replace(substr($s,$a,$b-$a+1),'',$s);
		while(($a=stripos($s,'</'.$t))!==false&&($b=stripos($s,'>',$a))!==false) $s=str_replace(substr($s,$a,$b-$a+1),'',$s);
	}
	return $s;
}
#replace回数指定(第一引数が全体の文字列)
function nreplace(string $s, string $find, string $to, int $n):string{
	$l = strlen($find);
	for ($p=0,$i=0;$i<$n&&($p=strpos($s,$find,$p))!==false;++$i){
		$b = substr($s, 0, $p);
		$a = substr($s, $p+$l);
		$s = $b.$to.$a;
	}
	return $s;
}
#先頭で見つかったときのみ置換
function lreplace(string $s, string $left, string $to=''):string{
	if($left!==''&&str_starts_with($s,$left)) return $to.substr($s,strlen($left));
	return $s;
}
#末尾で
function rreplace(string $s, string $right, string $to=''):string{
	if($right!==''&&str_ends_with($s,$right)) return substr($s,0,strrpos($s,$right)).$to;
	return $s;
}
#改行除去
function row(string $s):string{return str_replace(["\r","\n"],'',$s);}
#base64_URLencode
function encode_a(string $s):string{
	$b = base64_encode($s);
	return rtrim(strtr($b,'+/','-_'), '=');
}
#base64_URLdecode
function decode_a(string $s):string{
	$b = strtr($s, '-_', '+/');
	$p = strlen($b)%4;
	if($p) $b.=str_repeat('=',4-$p);
	$d = base64_decode($b, true);
	return is_string($d)?$d:'';
}
#乱数文字列
function one_time_pass(int $l,int $r):string{return encode_a(random_bytes(random_int($l,$r)));}
#開始と終了の文字列で囲まれた中身の文字列
function subrpos(string $l, string $r, string $t):string{
	$n = strlen($l);
	if(($s=strpos($t,$l))!==false&&($e=strpos($t,$r,$s+$n))!==false) return substr($t,$s+$n,$e-$s-$n);
	return '';
}
function innerHTML(string $t,string $h):string{return subrpos('<'.$t.'>','</'.$t.'>',$h);}
#任意の一つの行
function get(string $file, int $l):string|false{
	if (is_file($file)){
		$f = fopen($file, 'r');
		for($i=0;$i<$l;++$i) $o=fgets($f);
		fclose($f);
		if(isset($o)) return trim($o);
	}
	return false;
}
#任意の行以降全部
function get_rows(string $f, int $l):array|false{
	if(!is_file($f)) return false;
	$r = file($f, FILE_IGNORE_NEW_LINES);
	return $r?array_slice($r,$l-1):$r;
}
#任意時間以上更新ないファイル削除
function unlink_by_date(string $dir, int $ds):void{
	if(is_dir($dir)) return;
	foreach (scandir($dir)as$i){
		$f = $dir.'/'.$i;
		if(is_file($f)&&time()-filemtime($f)>$ds) unlink($f);
	}
}
#パス一覧再帰取得
function path_list(string $dir):array{
	if(!is_dir($dir)) return [$dir];
	$l = [];
	foreach (scandir($dir) as $d){
		if ($d!=='.' && $d!=='..'){
			$f = $dir.'/'.$d;
			$l[] = $f;
			if(is_dir($f)) $l=array_merge($l,path_list($f));
		}
	}
	return $l;
}
#ディレクトリ再帰コピー (from,to,[上書きしないパス]) 引数は絶対パスで正規化すること
#ファイルでコピー先が無い場合: to全体をディレクトリとして作成
function copy_path(string $dir, string $to, array $skip=[]):bool{
	if(!file_exists($dir)||(is_dir($dir)&&is_file($to))) return true;
	if (is_file($dir)){
		if(in_array($to,$skip,true)) return true;
		if(!file_exists($to)) mkdir($to,0777,true);
		if(is_dir($to)) $to=rreplace($to,'/').'/'.basename($dir);
		return copy($dir,$to);
	}
	if(!is_dir($to)) mkdir($to,0777,true);
	foreach (scandir($dir) as $i){
		if ($i!=='.' && $i!=='..'){
			$f = $dir.'/'.$i;
			$t = $to.'/'.$i;
			foreach($skip as $s)if($t===$s) continue 2;
			if(is_dir($f)){
				if(!copy_path($f,$t,$skip)) return false;
			} elseif (!copy($f,$t)){
				return false;
			}
		}
	}
	return true;
}
#ディレクトリ再帰削除
function rmdir_all(string $dir):void{
	if(!is_dir($dir)) return;
	foreach (scandir($dir) as $d){
		if ($d!=='.' && $d!=='..'){
			$f = $dir.'/'.$d;
			is_dir($f)?rmdir_all($f):unlink($f);
		}
	}
	rmdir($dir);
}
#設定ファイル
function read_conf(string $file):array{
	$c = [];
	if(!is_file($file)) return $c;
	$fp = fopen($file, 'r');
	while (($i=fgets($fp)) !== false){
		$e = strpos($i, '=');
		if(!$e||in_array(trim($i)[0],[';','#','['],true)) continue;
		$k = trim(substr($i, 0, $e));
		$v = trim(substr($i, $e +1));
		if ($v === (string)(int)$v){
			$v = (int)$v;
		} else {
			$a = subrpos("'", "'", $v);
			$b = subrpos('"', '"', $v);
			$v = (strlen($a)>strlen($b))?$a:$b;
		}
		if($k!=='') $c[$k]=$v;
	}
	fclose($fp);
	return $c;
}
#簡易的なパスキー認証以外は非推奨
function pass(string $pw):string{return $pw===''?'':hash('sha256',$pw);}
#複数行コメントアウト破壊削除
function remove_comment_rows(string &$t, string $s='/*', string $g='*/'):string{
	while(($p=subrpos($s,$g,$t))!=='') $t=str_replace($s.$p.$g,'',$t);
	return $t;
}
#css軽量化
function css_out(string $file):string{
	$r = '';
	if($file==='') return $r;
	if (is_dir($file)){
		foreach(scandir($file)as$f)if(str_ends_with(strtolower($f),'.css')) $r.=file_get_contents($file.'/'.$f);
	} elseif (is_file($file)){
		$r = file_get_contents($file);
	}
	remove_comment_rows($r);
	return preg_replace('/(\s*(,|:|;|{|})\s*)/', '$2', row($r));
}
#js軽量化
function js_out(string $file, bool $minify=true):string{
	$r = '';
	if($file==='') return $r;
	$o = '#!option ';
	$l = [];
	if (is_dir($file)){
		foreach(scandir($file)as$f)if(str_ends_with(strtolower($f),'.js')) $l[]=$file.'/'.$f;
	} elseif (is_file($file)){
		$l = [$file];
	}
	foreach ($l as $f){
		$j = file_get_contents($f);
		if(subrpos($o,';',$j)==='notminify') $minify=false;
		remove_comment_rows($j, $o, ';');
		if ($minify){
			remove_comment_rows($j);
			$t = '';
			foreach (explode("\n",$j) as $i){
				for ($k=0,$q='',$n=strlen($i)-1;$k<$n;++$k){
					if ($q){
						if($q===$i[$k]) $q='';
					} elseif (in_array($i[$k],['"',"'"],true)){
						$q = $i[$k];
					} elseif (substr($i,$k,2)==='//' && !$q){
						$i = substr($i, 0, $k);
						break;
					}
				}
				$t .= trim($i);
			}
			$r .= preg_replace('/(\s*(,|=|{|}|\(|\)|[|]|\?|!|\&|-|\+|<|>|:|;|\*|\/)\s*)/', '$2', $t);
		} else {
			$r .= $j;
		}
	}
	return $r;
}
#全ての不可視文字はfalse
function not_empty(string $s):bool{return str_replace(["\t","\v",' ','　'],'',row($s))!=='';}
#連想配列の一括キー存在確認
function list_isset(array $dict, array $keys):bool{
	foreach($keys as $k) if(!isset($dict[$k]))return false;
	return true;
}
#html互換md 内部処理用。ユーザー入力で使用する場合は事前にエスケープしてください
function to_html(string $text):string{
	remove_comment_rows($text,'<!--','-->');
	foreach(['|'=>124,'《'=>12298,'》'=>12299,'*'=>42,'#'=>35,'"'=>34,"'"=>39,'`'=>96,'~'=>126,'\\'=>92]as$k=>$v) $text=str_replace("\\$k","&#$v;",$text);
	$rows = explode("\n", str_replace("\\\n",'',lf($text)));
	$r = '';
	$hid = [];
	for ($ol=0,$ul=0,$len=count($rows),$j=0;$j < $len;++$j){
		$l = trim($rows[$j]);
		if (str_starts_with($l,'*') && substr_count($l,'*')%2){
			if ($ol){
				$r .= '</ol>';
				$ol = 0;
			}
			if(!$ul) $r.='<ul>';
			$r .= '<li>'.trim(substr($l,strpos($l,'*')+1)).'</li>';
			++$ul;
		} elseif (sscanf($l,'%d',$i)===1 && strpos($l,$i.'. ')===0 && $i>=0){
			if ($ul){
				$r .= '</ul>';
				$ul = 0;
			}
			if ($i < $ol){
				$r .= '</ol>';
				$ol = 0;
			}
			if(!$ol) $r.='<ol>';
			$r .= '<li>'.trim(substr($l,strpos($l,$i.'.')+strlen($i.'.'))).'</li>';
			++$ol;
		} elseif ($ul){
			$r .= '</ul>';
			$ul = 0;
		} elseif ($ol){
			$r .= '</ol>';
			$ol = 0;
		} elseif (str_starts_with($l,'#')){
			for ($i=6;$i > 0;--$i){
				$p = str_repeat('#', $i);
				if (substr($l,0,$i) === $p){
					$h = trim(substr($l, $i));
					$d = subrpos('<!id ','>', $h);
					if($d!=='') $d=' id="'.$d.'"';
					$r .= '<h'.$i.$d.'>'.$h.'</h'.$i.'>';
					break;
				}
			}
		} elseif (str_starts_with($l,'<')){
			$i = 0;
			foreach (['a','b','del','i','img','q','s','span','u'] as $t){
				if (substr($l,1,strlen($t)) === $t){
					$i = 1;
					break;
				}
			}
			$r .= $i?"<p>$l</p>":$l;
		} elseif (str_starts_with($l,'`')){
			$r .= $l;
		} else {
			$r .= $l===''?'<p><br></p>':"<p>$l</p>";
		}
	}
	foreach (['~~'=>'del','**'=>'b','```'=>'blockquote','`'=>'code','*'=>'i'] as $w=>$t){
		$l = strlen($w);
		while (($b=strpos($r,$w))!==false && ($a=strpos($r,$w,$b+$l))!==false){
			$s = substr($r, 0, $b);
			$c = substr($r, $b+$l, $a-$b-$l);
			$g = substr($r, $a+$l);
			$r = $s.'<'.$t.'>'.$c.'</'.$t.'>'.$g;
		}
	}
	for ($i=0;$i!==false&&($c=subrpos('|','》',substr($r,$i)))!=='';$i=strpos($r,'|',$i+1)){
		if (count($l=explode('《',$c)) === 2){
			$r = str_replace('|'.$c.'》', '<ruby><rb>'.$l[0].'</rb><rt>'.$l[1].'</rt></ruby>', $r);
		}
	}
	return $r;
}
#まあまあ正確なIP
function get_ip():string{
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$i = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		return trim($i[0]);
	}
	foreach(['HTTP_CLIENT_IP','HTTP_X_REAL_IP']as$k) if(isset($_SERVER[$k]))return $_SERVER[$k];
	return $_SERVER['REMOTE_ADDR'];
}
class GakuUra{
	public string $d_root;
	public string $u_root;
	public string $doc_root;
	public string $data_dir;
	public string $nonce;
	public string $config_file;
	public array $config;
	public string $here;#今のURL
	public string $url_param;#URLパラメーター
	public string $domain;#トップリンク
	public string $referer;
	public string $canonical;#正規URL
	public array $ld_json;#構造化データ辞書
	private string $system_dir;
	public const GAKU_URA_FILES = [
		'index.php','404.php','css','js','users','gaku-ura/conf','gaku-ura/main',
		'gaku-ura/description.txt','gaku-ura/.htaccess','gaku-ura/data/description.txt',
		'gaku-ura/data/404','gaku-ura/data/default','gaku-ura/data/users'];
	public const UPGRADE_IGNORE = [
		'gaku-ura/conf/gaku-ura.conf','gaku-ura/data/default/default.html',
		'gaku-ura/data/default/default.css','gaku-ura/data/users/html/custom'];
	function __construct(?bool $third=null){
		header('Referrer-Policy:same-origin');
		if (!isset($_SESSION)){
			session_set_cookie_params(['lifetime'=>2400*3600,'secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Strict']);
			session_start();
		}
		$this->d_root = realpath(__DIR__ .'/../..');
		$this->system_dir = realpath(__DIR__ .'/..');
		$this->data_dir = $this->system_dir.'/data';
		$this->config_file = __DIR__ .'/gaku-ura.conf';
		$this->config = read_conf($this->config_file);
		$this->domain = (empty($_SERVER['HTTPS'])?'http://':'https://').($_SERVER['HTTP_HOST']??'');
		$this->here = $this->domain.($_SERVER['REQUEST_URI']??'');
		$this->domain .= '/';
		$this->url_param = urldecode($_SERVER['QUERY_STRING']??'');
		$c = $this->here;
		$n = ini_get('session.name');
		if (isset($_GET[$n])){
			$s = $n.'='.$_GET[$n];
			foreach(['?','&']as$i) $c=str_replace($i.$s,'',$c);
		}
		$this->canonical = $c;
		$this->referer = $_SERVER['HTTP_REFERER']??'';
		$u = $this->config['u_root']??'';
		if(!str_ends_with($u,'/')) $u.='/';
		if(!str_starts_with($u,'/')) $u='/'.$u;
		$this->u_root = $u;
		$this->doc_root = rreplace(rreplace($this->d_root.'/',$u),'/');
		$this->ld_json = ['@context'=>'https://schema.org','@type'=>'WebPage','url'=>$this->canonical,'author'=>['@type'=>'Person','name'=>'unknown'],'image'=>'/favicon.ico'];
		if (isset($this->config['seo.author']) && count($d=explode(',',$this->config['seo.author']))>1){
			$this->ld_json['author']['@type'] = trim($d[0]);
			$this->ld_json['author']['name'] = trim($d[1]);
		}
		if($this->here===$this->domain||$this->here.'/'===$this->domain) $this->ld_json['@type']='WebSite';
		if(!empty($_SERVER['HTTPS'])) header('Strict-Transport-Security:max-age=63072000;includeSubDomains;preload');
		if ((int)($this->config['header_scrict']??0)===1){
			header('X-Frame-Options:SAMEORIGIN');
		}
		$third = $third??((int)($this->config['use_nonce']??1)===0);
		$this->nonce = '';
		if (!$third){
			$this->nonce = one_time_pass(20, 30);
			header("Content-Security-Policy:connect-src 'self';object-src 'none';base-uri 'self';script-src 'nonce-{$this->nonce}' 'strict-dynamic' https:;");
		}
		if(isset($_POST))foreach($_POST as $k=>$v) $_POST[$k]=u8lf($v);
	}
	#ヘッダー content-type
	public function content_type(string $type, string $c='UTF-8'):void{
		$a = strpos($type,'text/')===0?'charset='.$c:'';
		header('Content-Type:'.$type.';'.$a);
	}
	#htmlやmdの変数展開をエスケープする用 通常のhtmlエスケープはこれではなくh関数を使用
	public static function h(string $s):string{
		return str_replace('{','&#123;', str_replace('}','&#125;',$s));
	}
	#ファイル同時アクセス防止
	public function file_lock(string $label):void{
		$s = $this->system_dir.'/flock';
		if(!is_dir($s)) mkdir($s);
		for($f=$s.'/.'.$label;is_file($f);unlink_by_date($s,60)) usleep(100);
		touch($f);
	}
	public function file_unlock(string $label):void{
		$f = $this->system_dir.'/flock/.'.$label;
		if(is_file($f)) unlink($f);
	}
	#ライブラリのinclude
	public function include_lib(string $code, string $mode):string{
		remove_comment_rows($code);
		$b = '#!include ';
		$e = ';';
		$a = [];
		while (($p=subrpos($b,$e,$code)) !== ''){
			$i = lreplace(trim($p),'\/','/');
			$m = '';
			$f = $this->data_dir.'/'.$i;
			foreach (['js','css'] as $j){
				if (str_ends_with($i, '.'.$j)){
					$m = $j;
					break;
				}
			}
			if ($m && !str_starts_with($i,'/')){
				$f = $this->data_dir.'/default/lib/'.$m.'/'.$i;
			} elseif (str_starts_with($i,'/')){
				$f = $this->d_root.$i;
			}
			$r = '';
			if ($m && !in_array($f,$a,true)){
				if ($m === 'js'){
					$r = js_out($f);
				} elseif ($m === 'css'){
					$r = css_out($f);
				}
				$a[] = $f;
			} elseif (!$m){
				if (str_ends_with($i, '.json')){
					$r = js_out($f);
				} else {
					$r = file_get_contents($f);
				}
			}
			$code = str_replace($b.$p.$e, $r, $code);
		}
		if($mode!=='css') return $code;
		$b = '@import';
		$l = '';
		while (($p=subrpos($b,$e,$code)) !== ''){
			$l .= $b.$p.$e;
			$code = str_replace($b.$p.$e, '', $code);
		}
		return $l.$code;
	}
	#タイトル,説明,bodyタグの中身,[cssファイル,jsファイル,クロール可否,共通css使うか,jsの軽量化,雛形のhtml]
	public function html(string $title,string $summary,string $content,string $css='',string $js='',bool $robots=false,bool $css_default=true,bool $minify=true,?string $htm=null):int{
		$htm = $htm??'default/default.html';
		$f = $this->data_dir.'/'.$htm;
		if(!is_file($f)) return 1;
		$h = row(file_get_contents($f))."\n";
		remove_comment_rows($h, '<!--','-->');
		remove_comment_rows($title, '<','>');
		remove_comment_rows($summary, '<','>');
		if(!$js) remove_comment_rows($h, '<script ','</script>');
		$r = [
		'CSS_URL'=>$this->u_root.'css/?'.lreplace($css,$this->data_dir).($css_default?'':'&STANDALONE'),
		'JS_URL'=>$this->u_root.'js/?'.lreplace($js,$this->data_dir).($minify?'':'&NOTMINIFY'),
		'NONCE'=>$this->nonce,'DESCRIPTION'=>self::h(($robots&&not_empty($summary))?$summary:'なし'),'TITLE'=>self::h($title),
		'CONTENT'=>self::h($content),'SITE_TITLE'=>self::h($this->config['title']??'無題'),'U_ROOT'=>$this->u_root];
		if ($this->here !== $this->canonical){
			$h = nreplace($h, '</head>', '<link rel="canonical" href="'.$this->canonical.'"></head>', 1);
			$robots = false;
		}
		if(!$robots) $h=str_replace('<ti','<meta name="robots" content="noindex"><ti',$h);
		if(strpos($h,'{CSS}')!==false) $r['CSS']=$this->include_lib(($css_default?css_out($this->data_dir.'/default/default.css'):'').css_out($css),'css');
		if(strpos($h,'{JS}')!==false) $r['JS']=$this->include_lib(js_out($js,$minify),'js');
		foreach($r as $k=>$v) $h=str_replace('{'.$k.'}',$v,$h);
		if ($robots && (int)($this->config['seo.enable_ld_json']??0)===1){
			if ($this->ld_json['@type'] !== 'Person'){
				$this->ld_json['name'] = $r['SITE_TITLE'];
				$this->ld_json['headline'] = $r['TITLE'].$r['SITE_TITLE'];
			}
			$this->ld_json['description'] = $summary;
			$j = json_encode($this->ld_json, JSON_UNESCAPED_UNICODE);
			$h = nreplace($h, '</body>', '<script type="application/ld+json">'.$j.'</script></body>', 1);
		}
		echo $h;
		return 0;
	}
	#data_dir以下フォルダ名,html・mdファイル名,ファイル中の「{変数}」対応表,クロール可否
	public function htmlf(string $project_name,?string $file_name,array $replace,bool $robots=false):int{
		$pr = $this->data_dir.'/'.$project_name;
		$fn = basename($file_name??'index');
		$h = $pr.'/html/'.$fn;
		if(strpos($fn,'.')===false) $h.=is_file($h.'.md')?'.md':'.html';
		if(!is_file($h)) return -1;
		$c = file_get_contents($h);
		if(str_ends_with($h,'.md')) $c=to_html($c);
		remove_comment_rows($c, '<!--','-->');
		$b = '<!include ';
		$e = '>';
		$js = '';
		$css = $pr.'/css/index.css';
		while (($p=subrpos($b,$e,$c)) !== ''){
			$t = $b.$p.$e;
			if (str_ends_with($p, '.js')){
				if(file_exists($pr.'/js/'.$p)) $js=$pr.'/js/'.$p;
			} elseif (str_ends_with($p, '.css')){
				if(file_exists($pr.'/css/'.$p)) $css=$pr.'/css/'.$p;
			} elseif (str_ends_with($p,'.html')){
				if(is_file($pr.'/html/'.$p)) $c=str_replace($t,file_get_contents($pr.'/html/'.$p),$c);
			} elseif (str_ends_with($p,'.md')){
				if(is_file($pr.'/html/'.$p)) $c=str_replace($t,to_html(file_get_contents($pr.'/html/'.$p)),$c);
			} elseif (is_file($pr.'/'.$p)){
				$c = str_replace($t, file_get_contents($pr.'/'.$p), $c);
			}
			$c = str_replace($t, '', $c);
		}
		foreach($replace as $k=>$v) $c=str_replace('{'.$k.'}',self::h($v),$c);
		$b = '<!option ';
		$s = ['top_page'=>false,'title'=>self::h(innerHTML('h1',$c)),'description'=>self::h(innerHTML('p',$c)),'robots'=>$robots,'css_default_only'=>false,'css_standalone'=>false,'js_minify'=>true,'template'=>null];
		while (($p=subrpos($b,$e,$c))!=='' && $r=explode(' ',$p)){
			$v = ltrim(lreplace($p, $r[0]));
			if(in_array($r[0],['robots','css_default_only','css_standalone','js_minify'])) $v=(bool)$v;
			$s[$r[0]] = $v;
			$c = str_replace($b.$p.$e, '', $c);
		}
		remove_comment_rows($c, '<!','>');
		$s['title'] .= '-';
		if($s['css_default_only']) $css='';
		if($s['top_page']) $s['title']='';
		return $this->html($s['title'],$s['description'],str_replace("\t",'',row($c)),$css,$js, $s['robots'],!$s['css_standalone'],$s['js_minify'],$s['template']);
	}
	#エラーページ
	public function not_found(bool $is404=false, string $reason=''):void{
		if($reason==='') $reason='アクセス拒否または無効なURLです。';
		$h = $this->u_root;
		$u = urldecode($_SERVER['REQUEST_URI']??'');
		$f = $this->d_root.$u;
		if(http_response_code()===500&&is_file($f)&&str_starts_with(get($f,'#!/',1))) chmod($f,0745);
		if($is404) http_response_code(404);
		foreach (explode(' ',$this->config['error.moved_list']??'') as $m){
			if (($e=strpos($m,'=>')) && ($k=trim(substr($m,0,$e))) && $u===$k){
				$h = trim(substr($m, $e +2));
				break;
			}
		}
		exit($this->htmlf('404',null,['HERE'=>$this->here,'GAKU_URA_VERSION'=>GAKU_URA_VERSION,'PERHAPS'=>$h,'REASON'=>$reason]));
	}
	#csrfトークン発行
	public function set_csrf_token(string $name, int $l=32, int $r=64):string{
		$t = one_time_pass($l, $r);
		$_SESSION['csrf_token__'.$name] = implode("'",[$t,$this->here]);
		return $t;
	}
	#nameはsetとcheckで同じ文字列にする
	public function check_csrf_token(string $name, string $t, bool $ref):bool{
		$d = explode("'", $_SESSION['csrf_token__'.$name]??'');
		$a = strpos($d[1]??'', '://');
		$b = strpos($this->referer, '://');
		return $t&&$d[0]===$t&&(!$ref||($a&&$b&&substr($d[1],$a+3)===substr($this->referer,$b+3)));
	}
	public function form_die():void{exit($this->htmlf('404','form_die',[]));}
	#学裏ライブラリの上書き展開
	public function upgrade(string $tar_gz, ?array &$reduced=null):int{
		if(!is_file($tar_gz)) return 1;
		$l = 'gaku-ura_upgrade';
		$this->file_lock($l);
		$m = $this->system_dir.'/tmp';
		$t = $m.'/g/';
		$b = $m.'/b/';
		$u = [];
		$ub = [];
		foreach (array_merge(self::UPGRADE_IGNORE,explode(',',$this->config['upgrade.ignore']??'')) as $i){
			$u[] = $this->d_root.'/'.$i;
			$ub[] = $b.$i;
		}
		if(is_dir($m)) rmdir_all($m);
		mkdir($t, 0777, true);
		mkdir($b, 0777, true);
		foreach(self::GAKU_URA_FILES as $f)if(file_exists($this->d_root.'/'.$f)) copy_path($this->d_root.'/'.$f,$b.$f,$ub);
		$a = $m.'/upgrade.tar.gz';
		copy($tar_gz, $a);
		try{
			$p = new PharData($a);
			$p->decompress();
			$p = new PharData(rreplace($a,'.gz'));
			if (!$p->extractTo($t)){
				$this->file_unlock($l);
				return 2;
			}
		}catch(Exception $e){
			$this->file_unlock($l);
			return 2;
		}
		$r = 0;
		foreach (self::GAKU_URA_FILES as $f){
			$s = $t.$f;
			$o = $this->d_root.'/'.$f;
			if (!file_exists($s)){
				$r = 3;
				is_file($o)?unlink($o):rmdir_all($o);
				if($reduced!==null) $reduced[]=$f;
				continue;
			}
			if (!copy_path($s,$o,$u)){
				copy_path($b, $this->d_root, $u);
				$this->file_unlock($l);
				return 4;
			}
		}
		rmdir_all($m);
		$this->file_unlock($l);
		return $r;
	}
}

