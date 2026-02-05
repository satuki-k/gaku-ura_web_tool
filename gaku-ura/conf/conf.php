<?php
#gaku-ura標準ライブラリが定義
const GAKU_URA_VERSION = '9.6.14';
#mbstringの代替関数を使うときは以下のコメントを外す
//include __DIR__ .'/alt-mbstring.php';
function h(string $t):string{return htmlspecialchars($t,ENT_QUOTES,'UTF-8');}
#UTF-8/LFにする
function u8lf(string $t):string{
	$t = str_replace("\r\n", "\n", $t);
	$t = str_replace("\r", "\n", $t);
	return mb_convert_encoding($t,'UTF-8');
}
#埋め込みとjs禁止エスケープ
function expelliarmus(string $s):string{
	foreach (['script','iframe','frame','embed','object'] as $t){
		$s = str_ireplace('</'.$t.'>', '', $s);
		while (($a=stripos($s,'<'.$t))!==false&&($b=stripos($s,'>',$a))!==false){
			$s = str_replace(substr($s,$a,$b-$a+1),'',$s);
		}
	}
	return $s;
}
#replace回数指定
function nreplace(string $subject, string $search, string $replace, int $n):string{
	$l = strlen($search);
	for ($p=0,$i=0;$i<$n&&($p=strpos($subject,$search,$p))!==false;++$i){
		$b = substr($subject, 0, $p);
		$a = substr($subject, $p+$l);
		$subject = $b.$replace.$a;
	}
	return $subject;
}
#先頭で見つかったときのみ置換(第一引数が全体の文字列)
function lreplace(string $s, string $left, string $replace=''):string{
	if(str_starts_with($s,$left)) return $replace.substr($s,strlen($left));
	return $s;
}
#末尾で
function rreplace(string $s, string $right, string $replace=''):string{
	if(str_ends_with($s,$right)) return substr($s,0,strpos($s,$right)).$replace;
	return $s;
}
#改行除去
function row(string $s):string{return str_replace("\r",'',str_replace("\n",'',$s));}
#base64_URLencode
function encode_a(string $s):string{return str_replace('+','-',str_replace('/','_',base64_encode($s)));}
#base64_URLdecode
function decode_a(string $s):string{return base64_decode(str_replace('_','/',str_replace('-','+',$s)));}
#乱数文字列
function one_time_pass(int $l,int $r):string{return encode_a(random_bytes(random_int($l,$r)));}
#開始と終了の文字列で囲まれた中身の文字列
function subrpos(string $l, string $r, string $t):string{
	$n = strlen($l);
	if (($s=strpos($t,$l))!==false && ($e=strpos($t,$r,$s+$n))!==false){
		return substr($t, $s+$n, $e-$s-$n);
	}
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
function get_rows(string $file, int $start):array|false{
	$r = file($file, FILE_IGNORE_NEW_LINES);
	return ($r===false)?$r:array_slice($r,$start-1);
}
#任意時間以上更新ないファイル削除
function unlink_by_date(string $dir, int $ds):void{
	if (is_dir($dir)){
		foreach (scandir($dir)as$i){
			$f = $dir.'/'.$i;
			if(is_file($f)&&time()-filemtime($f)>$ds) unlink($f);
		}
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
#空じゃないフォルダも削除可
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
		$r = trim($i);
		$e = strpos($r, '=');
		$f = substr($r, 0, 1);
		if($e===false||in_array($f,[';','#','['],true)) continue;
		$k = trim(substr($r, 0, $e));
		$v = trim(substr($r, $e +1));
		if (is_numeric($v)){
			$v = (int)$v;
		} else {
			$a = subrpos("'", "'", $v);
			$b = subrpos('"', '"', $v);
			$v = ((strlen($a)>strlen($b))?$a:$b);
		}
		if($k!=='') $c[$k]=$v;
	}
	fclose($fp);
	return $c;
}
#簡易的なパスキー認証以外は非推奨
function pass(string $pw):string{return ($pw===''?'':hash('sha256',$pw));}
#開始と終了で囲まれた文字列ごと削除(参照渡し)
function remove_comment_rows(string &$t, string $s='/*', string $g='*/'):string{
	while(($p=subrpos($s,$g,$t))!=='') $t=str_replace($s.$p.$g,'',$t);
	return $t;
}
#css軽量化
function css_out(string $css_file):string{
	if($css_file==='') return '';
	$r = '';
	$l = [];
	if (is_dir($css_file)){
		foreach (scandir($css_file) as $f){
			if(str_ends_with(strtolower($f),'.css')) $l[]=$css_file.'/'.$f;
		}
	} elseif (is_file($css_file)){
		$l = [$css_file];
	}
	foreach($l as $c) $r.=file_get_contents($c);
	remove_comment_rows($r);
	$r = str_replace("\t", '', row($r));
	return preg_replace('/( |)(,|:|;|{|})( |)/', '$2', $r);
}
#js軽量化
function js_out(string $js_file, bool $minify=true):string{
	if($js_file==='') return '';
	$r = '';
	$l = [];
	if (is_dir($js_file)){
		foreach (scandir($js_file) as $f){
			if(str_ends_with(strtolower($f),'.js')) $l[]=$js_file.'/'.$f;
		}
	} elseif (is_file($js_file)){
		$l = [$js_file];
	}
	foreach ($l as $f){
		$j = file_get_contents($f);
		if(subrpos('#!option ',';',$j)==='notminify') $minify=false;
		remove_comment_rows($j, '#!option ', ';');
		if ($minify){
			remove_comment_rows($j);
			$t = '';
			foreach (explode("\n",$j) as $i){
				$t .= preg_replace('/\/\/.*/', '', trim($i));
			}
			$r .= preg_replace('/( |)(,|=|{|}|\(|\)|[|]|\?|!|\&|-|\+|<|>|:|;|\*|\/)( |)/', '$2', $t);
		} else {
			$r .= $j;
		}
	}
	return $r;
}
#全ての不可視文字はfalse
function not_empty(string $s):bool{
	foreach(["\t","\v",' ','　']as$i) $s=str_replace($i,'',$s);
	return (row($s)!=='');
}
#連想配列の一括キー存在確認
function list_isset(array $dict, array $keys):bool{
	foreach($keys as $k) if(!isset($dict[$k]))return false;
	return true;
}
#html互換md
function to_html(string $text):string{
	remove_comment_rows($text,'<!--','-->');
	foreach(['|'=>124,'《'=>12298,'》'=>12299,'*'=>42,'#'=>35,'"'=>34,"'"=>39,'`'=>96,'~'=>126,'\\'=>92]as$k=>$v) $text=str_replace('\\'.$k,'&#'.$v.';',$text);
	$rows = explode("\n", u8lf($text));
	$r = '';
	for ($ol=0,$ul=0,$len=count($rows),$j=0; $j < $len; ++$j){
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
			for ($i=6; $i > 0; --$i){
				$p = str_repeat('#', $i);
				if (substr($l,0,$i) === $p){
					$r .= '<h'.$i.'>'.trim(substr($l,$i)).'</h'.$i.'>';
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
			if ($i){
				$r .= '<p>'.$l.'</p>';
			} else {
				$r .= $l;
			}
		} elseif (str_starts_with($l,'`')){
			$r .= $l;
		} else {
			if($l==='') $l='<br>';
			$r .= '<p>'.$l.'</p>';
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
	return str_replace('\\', '', $r);
}
#真のIP入手
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
	public string $data_dir;
	public string $nonce;
	public string $config_file;
	public array $config;
	public string $here;#今のURL
	public string $url_param;#URLパラメーター
	public string $domain;#このサイトのドメイン
	public string $referer;#リファラ
	public string $canonical;#正規URL(URLにセッションIDがあるときに取り除いたURL)
	public array $ld_json;#構造化データ辞書
	private string $system_dir;
	function __construct(?bool $third=null){
		header('Referrer-Policy:same-origin');
		if(!isset($_SESSION)) session_start(['cookie_lifetime'=>time()+3600*2400]);
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
		$this->u_root = not_empty($u)?$u:'/';
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
		if($third===null) $third=(int)($this->config['use_nonce']??1)===0;
		$this->nonce = '';
		if (!$third){
			$this->nonce = one_time_pass(20, 30);
			header("Content-Security-Policy:connect-src 'self';object-src 'none';base-uri 'self';script-src 'nonce-{$this->nonce}' 'strict-dynamic' https:;");
		}
		if(isset($_COOKIE)) foreach($_COOKIE as $k=>$v)if($k!==$n)setcookie($k,$v,time()+3600*2400,'/');
		if(isset($_POST)) foreach($_POST as $k=>$v)$_POST[$k]=u8lf($v);
	}
	#ヘッダー content-type
	public function content_type(string $type, string $c='UTF-8'):void{
		$a = (strpos($type,'text/')===0)?'charset='.$c:'';
		header('Content-Type:'.$type.';'.$a);
	}
	public static function h(string $s):string{
		return str_replace('{','&#123;', str_replace('}','&#125;',$s));#不意置換防止
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
		remove_comment_rows($code, '/*', '*/');
		$b = '#!include ';
		$e = ';';
		$ald = [];
		while (($p=subrpos($b,$e,$code)) !== ''){
			$f = $this->data_dir.'/default/lib/'.$mode.'/'.trim($p);
			$r = '';
			if (!in_array($f,$ald,true) && file_exists($f)){
				if ($mode === 'js'){
					$r = js_out($f);
				} elseif ($mode === 'css'){
					$r = css_out($f);
				}
				$ald[] = $f;
			}
			$code = str_replace($b.$p.$e, $r, $code);
		}
		if($mode!=='css') return $code;
		$l = '';
		while (($p=subrpos('@import',$e,$code)) !== ''){
			$l .= '@import'.$p.$e;
			$code = str_replace('@import'.$p.$e, '', $code);
		}
		return $l.$code;
	}
	#タイトル 説明 本文(bodyタグの中身) cssファイル jsファイル 検索に表示させたいか 共通cssを含むか jsの軽量化をするか 雛形のhtmlファイル(data_dir基準)
	public function html(string $title,string $description,string $content,string $css='',string $js='',bool $robots=false,bool $css_default=true,bool $minify=true,?string $htm=null):int{
		if($htm===null) $htm='default/default.html';
		$f = $this->data_dir.'/'.$htm;
		if(!is_file($f)) return 1;
		$h = row(file_get_contents($f))."\n";
		remove_comment_rows($h, '<!--','-->');
		remove_comment_rows($title, '<','>');
		remove_comment_rows($description, '<','>');
		$r = [
		'CSS_URL'=>$this->u_root.'css/?'.lreplace($css,$this->data_dir).($css_default?'':'&STANDALONE'),
		'JS_URL'=>$this->u_root.'js/?'.lreplace($js,$this->data_dir).($minify?'':'&NOTMINIFY'),
		'NONCE'=>$this->nonce,'DESCRIPTION'=>self::h(($robots&&not_empty($description))?$description:'なし'),'TITLE'=>self::h($title),
		'CONTENT'=>self::h($content),'SITE_TITLE'=>self::h($this->config['title']??'無題'),'U_ROOT'=>$this->u_root];
		if ($this->here !== $this->canonical){
			$h = str_replace('</head>', '<link rel="canonical" href="'.$this->canonical.'"></head>', $h);
			$robots = false;
		}
		if(!$robots) $h=str_replace('<ti','<meta name="robots" content="noindex"><ti',$h);
		if(strpos($h,'{CSS}')!==false) $r['CSS']=$this->include_lib(($css_default?css_out($this->data_dir.'/default/default.css'):'').css_out($css),'css');
		if(strpos($h,'{JS}')!==false) $r['JS']=$this->include_lib(js_out($js,$minify),'js');
		foreach($r as $k=>$v) $h=str_replace('{'.$k.'}',$v,$h);
		$h = str_replace(' nonce=""', '', $h);
		if ($robots && (int)($this->config['seo.enable_ld_json']??0)===1){
			if ($this->ld_json['@type'] !== 'Person'){
				$this->ld_json['name'] = $r['SITE_TITLE'];
				$this->ld_json['headline'] = $r['TITLE'].$r['SITE_TITLE'];
			}
			$this->ld_json['description'] = $description;
			$j = json_encode($this->ld_json, JSON_UNESCAPED_UNICODE);
			$h = str_replace('</body>', '<script type="application/ld+json">'.$j.'</script></body>', $h);
		}
		echo $h;
		return 0;
	}
	#dataプロジェクトパス, htmlまたはmdファイル名前だけ, 「{}」展開変数の連想配列, クロール可否
	public function htmlf(string $project_name,string $file_name,array $replace,bool $robots=false):int{
		$pr = $this->data_dir.'/'.$project_name;
		$fn = basename($file_name);
		if (strpos($fn,'.') === false){
			if (is_file('html/'.$fn.'.md')){
				$fn .= '.md';
			} else {
				$fn .= '.html';
			}
		}
		$h = 'html/'.$fn;
		if(!is_file($pr.'/'.$h)) return -1;
		$c = file_get_contents($pr.'/'.$h);
		if(str_ends_with($h,'.md')) $c=to_html($c);
		remove_comment_rows($c, '<!--','-->');
		$js = '';
		$css = $pr.'/css/index.css';
		while (($p=subrpos('<!include ','>',$c)) !== ''){
			$t = '<!include '.$p.'>';
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
		$s = ['top_page'=>false,'title'=>self::h(innerHTML('h1',$c)),'description'=>self::h(innerHTML('p',$c)),'robots'=>$robots,'css_default_only'=>false,'css_standalone'=>false,'js_minify'=>true,'template'=>null];
		while (($p=subrpos('<!option ','>',$c))!=='' && count($r=explode(' ',$p))>1 && isset($s[$r[0]])){
			$v = implode(' ', array_slice($r,1));
			if(in_array($r[0],['robots','css_default_only','css_standalone','js_minify'])) $v=(bool)$v;
			$s[$r[0]] = $v;
			$c = str_replace('<!option '.$p.'>', '', $c);
		}
		remove_comment_rows($c, '<!','>');
		$s['title'] .= '-';
		if($s['css_default_only']) $css='';
		if($s['top_page']) $s['title']='';
		return $this->html($s['title'], $s['description'], str_replace("\t",'',row($c)), $css, $js, $s['robots'], !$s['css_standalone'], $s['js_minify'], $s['template']);
	}
	#エラーページ
	public function not_found(bool $is404=false, string $reason=''):void{
		$h = $this->u_root;
		if (http_response_code()===500 && isset($_SERVER['REQUEST_URI'])){
			$f = $this->d_root.$_SERVER['REQUEST_URI'];
			if(is_file($f) && str_starts_with(get($f,'#!/',1))) chmod($f, 0745);
		}
		if ($is404){
			$l = $this->config['error.moved_list']??'';
			foreach (explode(' ',$l) as $m){
				if (strpos($m,'=>') !== false){
					list($e, $t) = explode('=>', $m);
					if ($_SERVER['REQUEST_URI'] === trim($e)){
						$h = trim($t);
						break;
					}
				}
			}
		}
		$this->htmlf('404', 'index', ['PERHAPS'=>$h,'REASON'=>$reason]);
		exit;
	}
	/*
	 * php.iniに以下を書くとクッキーが無効でもcheck_csrf_tokenを通過できます。
	 * session.use_trans_sid = 1
	 * session.trans_sid_tags = "form="
	*/
	#csrfトークンが返り値
	public function set_csrf_token(string $name, int $l=32, int $r=64):string{
		$t = one_time_pass($l, $r);
		$_SESSION['csrf_token__'.$name] = implode("'",[$t,$this->here]);
		return $t;
	}
	#labelはsetとcheckで同じ文字列にする。strictをtrueにするとリファラチェックする
	public function check_csrf_token(string $name, string $t, bool $strict):bool{
		if (isset($_SESSION['csrf_token__'.$name])){
			$d = explode("'", $_SESSION['csrf_token__'.$name]);
			if($d[0]===$t&&($d[1]===$this->referer||!$strict)) return true;
		}
		return false;
	}
	public function form_die():void{
		$this->html('フォーム損傷-', '', '<h1>フォーム損傷</h1><p>フォームに損傷があります。停止しました。</p>');#WEB改ざん等で処理出来ない時に使う
		exit;
	}
}

