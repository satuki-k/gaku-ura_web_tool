<?php
# gaku-ura9.5.18
/* gaku-ura標準ライブラリが定義 */
//ini_set('display_errors', 'Off');

function h(string $t):string{
	return htmlspecialchars($t, ENT_QUOTES, 'UTF-8');#全てのhtml禁止エスケープ
}
function u8lf(string $t):string{
	return preg_replace('/\r\n|\r/', "\n", mb_convert_encoding($t,'UTF-8')); #UTF-8/LFにする
}
//埋め込みとjs禁止エスケープ
function expelliarmus(string $s):string{
	foreach (['script','iframe','frame','embed','object'] as $t){
		$s = preg_replace('/<'.$t.'.*>.*<\/'.$t.'>/si', '', $s);
	}
	return $s;
}
function row(string $s):string{
	return preg_replace('/\r\n|\r|\n/', '', $s);#改行除去
}
function encode_a(string $s):string{
	return str_replace('+','-',str_replace('/','_',base64_encode($s)));#URLに使用可能なbase64_encode
}
function decode_a(string $s):string{
	return base64_decode(str_replace('_','/',str_replace('-','+',$s)));#URLに使用可能なbase64_decode
}
function one_time_pass(int $min, int $max):string{
	return encode_a(random_bytes(random_int($min,$max)));#乱数文字
}
//開始と終了の文字列で囲まれた中身の文字列
function subrpos(string $start, string $end, string $text):string{
	if (($s=strpos($text,$start))!==false && ($e=strpos($text,$end,$s))!==false){
		$len1 = strlen($start);
		return substr($text, $s+$len1, $e-$s-$len1);
	}
	return '';
}
//任意の一つの行
function get(string $file, int $l):string|false{
	if (is_file($file)){
		$fp = fopen($file, 'r');
		for ($i=0;$i < $l;++$i){
			$out = fgets($fp);
		}
		fclose($fp);
		if (isset($out)){
			return trim($out);
		}
	}
	return false;
}
//任意の行以降全部
function get_rows(string $file, int $start):array|false{
	$rows = file($file, FILE_IGNORE_NEW_LINES);
	if ($rows === false){
		return false;
	}
	return array_slice($rows, $start -1);
}
//任意時間以上更新ないファイル削除
function unlink_by_date(string $dir, int $ds):void{
	if (is_dir($dir)){
		foreach (scandir($dir) as $i){
			$f = $dir.'/'.$i;
			if (is_file($f) && time() -filemtime($f) > $ds){
				unlink($f);
			}
		}
	}
}
//パス一覧再帰取得
function path_list(string $dir):array{
	if (!is_dir($dir)){
		return [];
	}
	$list = [];
	foreach (scandir($dir) as $d){
		if ($d !== '.' && $d !== '..'){
			$f = $dir.'/'.$d;
			$list[] = $f;
			if (is_dir($f)){
				$list = array_merge($list, path_list($f));
			}
		}
	}
	return $list;
}
//空じゃないフォルダも削除可
function rmdir_all(string $dir):void{
	if (is_dir($dir)){
		foreach (scandir($dir) as $d){
			if (($d !== '.') && ($d !== '..')){
				$f = $dir.'/'.$d;
				if (is_dir($f)){
					rmdir_all($f);
				} else {
					unlink($f);
				}
			}
		}
		rmdir($dir);
	}
}
//設定ファイル
function read_conf(string $conf_file):array{
	$c = [];
	if (is_file($conf_file)){
		$fp = fopen($conf_file, 'r');
		while (($i=fgets($fp)) !== false){
			$row = trim($i);
			$fi = substr($row, 0, 1);
			if ($fi===';'||$fi==='#'||$fi==='['||strpos($row,'=')===false){
				continue;
			}
			$peq = strpos($row, '=');
			$key = trim(substr($row, 0, $peq));
			$value = trim(substr($row, $peq +1));
			if (is_numeric($value)){
				$value = (int)$value;
			} else {
				$pos1 = subrpos("'", "'", $value);
				$pos2 = subrpos('"', '"', $value);
				$value = $pos2;
				if ($pos1 !== ''){
					$value = $pos1;
				}
			}
			if ($key !== ''){
				$c[$key] = $value;
			}
		}
		fclose($fp);
	}
	return $c;
}
//sha256ハッシュ
function pass(string $passwd):string{
	if ($passwd === ''){
		return '';
	}
	return hash('sha256', $passwd);
}
//開始と終了で囲まれた文字列ごと削除(参照渡し)
function remove_comment_rows(string &$code, string $s='/*', string $g='*/'):string{
	while (($p=subrpos($s, $g, $code)) !== ''){
		$code = str_replace($s.$p.$g, '', $code);
	}
	return $code;
}
//css軽量化
function css_out(string $css_file):string{
	if ($css_file === ''){
		return '';
	}
	$r = '';
	$css_list = [];
	if (is_dir($css_file)){
		foreach (scandir($css_file) as $f){
			if (str_ends_with(strtolower($f), '.css')){
				$css_list[] = $css_file.'/'.$f;
			}
		}
	} elseif (is_file($css_file)){
		$css_list = [$css_file];
	}
	foreach ($css_list as $css){
		$r .= file_get_contents($css);
	}
	remove_comment_rows($r);
	$r = preg_replace('/\r|\n|\r\n|\t/', '', $r);
	return preg_replace('/( |)(,|:|;|{|})( |)/', '$2', $r);
}
//js軽量化
function js_out(string $js_file, bool $minify=true):string{
	if ($js_file === ''){
		return '';
	}
	$r = '';
	$js_list = [];
	if (is_dir($js_file)){
		foreach (scandir($js_file) as $f){
			if (str_ends_with(strtolower($f), '.js')){
				$js_list[] = $js_file.'/'.$f;
			}
		}
	} elseif (is_file($js_file)){
		$js_list = [$js_file];
	}
	foreach ($js_list as $js){
		$j = file_get_contents($js);
		if (subrpos('#!option ',';', $j) === 'notminify'){
			$minify = false;
		}
		remove_comment_rows($j, '#!option ', ';');
		if ($minify){
			remove_comment_rows($j);
			$t = '';
			foreach (explode("\n", $j) as $row){
				$t .= preg_replace('/\/\/.*/', '', trim($row));
			}
			$r .= preg_replace('/( |)(,|=|{|}|\(|\)|[|]|\?|!|\&|-|\+|<|>|:|;|\*|\/)( |)/', '$2', $t);
		} else {
			$r .= $j;
		}
	}
	return $r;
}
//全ての不可視文字はfalse
function not_empty(string $s):bool{
	$s = trim(preg_replace('/\t|\v|\s| |　/', '', row($s)));
	if ($s !== ''){
		return true;
	}
	return false;
}
//連想配列の一括キー存在確認
function list_isset(array $dict, array $keys):bool{
	foreach ($keys as $k){
		if (!isset($dict[$k])){
			return false;
		}
	}
	return true;
}
//html互換md
function to_html(string $md_text):string{
	$fls = ['ol'=>1, 'ul'=>1];
	$t = '';
	foreach (explode("\n", u8lf($md_text)) as $i){
		$row = trim($i);
		$fstc = substr($row, 0, 1);
		if ($fstc === '*'){
			if ($fls['ul']){
				$t .= '<ul>';
				$fls['ul'] = 0;
			}
			$t .= '<li>'.trim(substr($row,1)).'</li>';
			continue;
		} elseif (!$fls['ul']){
			$t .= '</ul>';
			$fls['ul'] = 1;
		}
		if (preg_match('/^[0-9]+\. .+$/', $row) === 1){
			$row = preg_replace('/^[0-9]+\./', '', $row);
			if ($fls['ol']){
				$t .= '<ol>';
				$fls['ol'] = 0;
			}
			$t .= '<li>'.trim($row).'</li>';
			continue;
		} elseif (!$fls['ol']){
			$t .= '</ol>';
			$fls['ol'] = 1;
		}
		for ($i=6,$cp='######'; $i>0; --$i,$cp=substr($cp,0,$i)){
			if (substr($row,0,$i) === $cp){
				$t .= '<h'.$i.'>'.trim(substr($row,$i)).'</h'.$i.'>';
				continue 2;
			}
		}
		if ($fstc === '`'){
			$t .= $row;
			continue;
		} elseif ($fstc === '<'){
			$nf = true;
			foreach (['a','b','del','i','img','q','s','span','u'] as $pt){
				if (substr($row, 1, strlen($pt)+1) === $pt.' '){
					$nf = false;
					break;
				}
			}
			if ($nf){
				$t .= $row;
				continue;
			}
		}
		if ($row === ''){
			$row = '<br>';
		}
		$t .= '<p>'.$row.'</p>';
	}
	foreach (['~~'=>'del','**'=>'b','```'=>'blockquote','`'=>'code','*'=>'i'] as $wrap=>$tag){
		$len = strlen($t);
		$wlen = strlen($wrap);
		$nt = '';
		$fl = 0;
		for ($i = 0; $i < $len; ++$i){
			if ($i < $len -$wlen && substr($t, $i, $wlen) === $wrap){
				if ($fl === 0){
					$fl = 1;
					$nt .= '<'.$tag.'>';
				} elseif ($fl === 1){
					$fl = 0;
					$nt .= '</'.$tag.'>';
				}
				$i += $wlen -1;
			} else {
				$nt .= substr($t, $i, 1);
			}
		}
		$t = $nt;
	}
	for ($p=strpos($t,'|');($c=subrpos('|','》',substr($t,(int)$p)))!=='';$p=strpos($t,'|')){
		if (count($l=explode('《',$c)) === 2){
			$t = str_replace('|'.$c.'》', '<ruby><rb>'.$l[0].'</rb><rt>'.$l[1].'</rt></ruby>', $t);
		}
	}
	return str_replace('\\', '', str_replace('\\\\', '&#92;', $t));
}
//真のIP入手
function get_ip():string{
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$ipAddresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		return trim($ipAddresses[0]);
	}
	if (isset($_SERVER['HTTP_CLIENT_IP'])){
		return $_SERVER['HTTP_CLIENT_IP'];
	}
	if (isset($_SERVER['HTTP_X_REAL_IP'])){
		return $_SERVER['HTTP_X_REAL_IP'];
	}
	return $_SERVER['REMOTE_ADDR'];
}

class GakuUra{
	public string $d_root;
	public string $u_root;
	public string $data_dir;
	public string $nonce;
	public string $config_file;
	public array $config;
	public string $here; //今のURL
	public string $url_param; //URLパラメーター
	public string $domain; //このサイトのドメイン
	public string $referer; //リファラ
	public string $canonical; //正規URL(URLにセッションIDがあるときに取り除いたURL)
	public array $ld_json; //構造化データ辞書
	private string $system_dir;
	//trueでnonceが無効になる
	function __construct(?bool $third_party=null){
		header('Referrer-Policy:same-origin');
		if (!isset($_SESSION)){
			session_start(['cookie_lifetime'=>time()+3600*24*100]);
		}
		$this->d_root = realpath(__DIR__ .'/../..');
		$this->system_dir = realpath(__DIR__ .'/..');
		$this->data_dir = $this->system_dir.'/data';
		$this->config_file = __DIR__ .'/gaku-ura.conf';
		$this->config = read_conf($this->config_file);
		$this->domain = (empty($_SERVER['HTTPS'])?'http://':'https://').(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
		$this->here = $this->domain.(isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'');
		$this->domain .= '/';
		$this->url_param = (isset($_SERVER['QUERY_STRING'])?urldecode($_SERVER['QUERY_STRING']):'');
		$this->canonical = preg_replace('/((\?|\&)'.ini_get('session.name').'.+)/', '', $this->here);
		$this->referer = (isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'');
		$this->u_root = ((isset($this->config['u_root'])&&not_empty($this->config['u_root']))?$this->config['u_root']:'/');
		$this->ld_json = [
		'@context'=>'https://schema.org','@type'=>'WebPage',
		'name'=>'','description'=>'','url'=>$this->canonical,'headline'=>'',
		'author'=>['@type'=>'Person','name'=>'unknown'],
		'datePublished'=>date('Y年m月d日',filemtime($this->d_root)),'image'=>'/favicon.ico'];
		if (isset($this->config['seo.author']) && count($d=explode(',',$this->config['seo.author'])) > 1){
			$this->ld_json['author']['@type'] = trim($d[0]);
			$this->ld_json['author']['name'] = trim($d[1]);
		}
		if ($this->here===$this->domain || $this->here.'/'===$this->domain){
			$this->ld_json['@type'] = 'WebSite';
		}
		$this->nonce = one_time_pass(20, 30);
		if ($third_party === null){
			if (isset($this->config['use_nonce']) && $this->config['use_nonce']===0){
				$third_party = true;
			} else {
				$third_party = false;
			}
		}
		if ($third_party){
			$this->nonce = '';
		} else {
			header("Content-Security-Policy:connect-src 'self';object-src 'none';base-uri 'self';script-src 'nonce-{$this->nonce}' 'unsafe-inline';");
		}
		if (isset($_COOKIE)){
			foreach ($_COOKIE as $k => $v){
				if ($k !== ini_get('session.name')){
					setcookie($k, $v, time() +3600*24*100, '/');
				}
			}
		}
		if (isset($_POST)){
			foreach ($_POST as $k => $v){
				$_POST[$k] = u8lf($v);
			}
		}
	}
	//ヘッダー content-type
	public function content_type(string $type, string $char='UTF-8'):void{
		if (strpos($type, 'text/') !== false){
			header('Content-Type:'.$type.';charset='.$char);
		} else {
			header('Content-Type:'.$type.';');
		}
	}
	public static function h(string $s):string{
		return str_replace('{', '&#123;', str_replace('}','&#125;',$s));#不意置換防止
	}
	//ファイル同時アクセス防止
	public function file_lock(string $label):void{
		if (!is_dir($this->system_dir.'/flock')){
			mkdir($this->system_dir.'/flock');
		}
		for ($f=$this->system_dir.'/flock/.'.$label;file_exists($f);unlink_by_date($this->system_dir.'/flock', 60));
		touch($f);
	}
	public function file_unlock(string $label):void{
		$f = $this->system_dir.'/flock/.'.$label;
		if (file_exists($f)){
			unlink($f);
		}
	}

	//ライブラリのinclude
	public function include_lib(string $code, string $mode):string{
		$ald = [];
		while (($p=subrpos('#!include ', ';', $code)) !== ''){
			$f = $this->data_dir.'/default/lib/'.$mode.'/'.trim($p);
			$r = '';
			if (!in_array($f, $ald, true) && file_exists($f)){
				if ($mode === 'js'){
					$r = js_out($f);
				} elseif ($mode === 'css'){
					$r = css_out($f);
				}
				$ald[] = $f;
			}
			$code = str_replace('#!include '.$p.';', $r, $code);
		}
		remove_comment_rows($code, '/*', '*/');
		if ($mode !== 'css'){
			return $code;
		}
		$l = '';
		while (($p=subrpos('@import', ';', $code)) !== ''){
			$l .= '@import'.$p.';';
			$code = str_replace('@import'.$p.';', '', $code);
		}
		return $l.$code;
	}

	//タイトル 説明 本文(bodyタグの中身) cssファイル jsファイル 検索に表示させたいか 共通cssを含むか jsの軽量化をするか 雛形のhtmlファイル(data_dir基準)
	public function html(string $title,string $description,string $content,string $css='',string $js='',bool $robots=false,bool $css_default=true,bool $minify=true,string $htm='default/default.html'):void{
		$html_file = $this->data_dir.'/'.$htm;
		if (!is_file($html_file)){
			echo '雛形のhtmlがありません';
			exit;
		}
		$html = row(file_get_contents($html_file))."\n";
		remove_comment_rows($html, '<!--', '-->');
		$replace = [
		'CSS_URL'=>$this->u_root.'css/?CSS='.str_replace($this->data_dir,'',$css).($css_default?'':'&USE_DEFAULT=false'),
		'JS_URL'=>$this->u_root.'js/?JS='.str_replace($this->data_dir,'',$js).($minify?'':'&MINIFY=false'),
		'NONCE'=>$this->nonce,
		'DESCRIPTION'=>(not_empty($description)?self::h($description):'なし'),'TITLE'=>self::h($title),
		'CONTENT'=>self::h($content),
		'SITE_TITLE'=>(isset($this->config['title'])?$this->config['title']:'無題'),
		'U_ROOT'=>$this->u_root];
		if ($this->here !== $this->canonical){
			$html = str_replace('</head>', '<link rel="canonical" href="'.$this->canonical.'"></head>', $html);
			$robots = false;
		}
		if ($robots === false){
			$html = str_replace('<ti', '<meta name="robots" content="noindex"><ti', $html);
		}
		if (strpos($html, '{CSS}') !== false){
			$replace['CSS'] = '';
			if ($css_default){
				$replace['CSS'] .= css_out($this->data_dir.'/default/default.css');
			}
			$replace['CSS'] .= css_out($css);
			$replace['CSS'] = $this->include_lib($replace['CSS'], 'css');
		}
		if (strpos($html, '{JS}') !== false){
			$replace['JS'] = $this->include_lib(js_out($js, $minify), 'js');
		}
		foreach ($replace as $s => $r){
			$html = str_replace('{'.$s.'}', $r, $html);
		}
		$html = str_replace(' nonce=""', '', $html);
		if ($robots && isset($this->config['seo.enable_ld_json']) && (int)$this->config['seo.enable_ld_json']===1){
			if ($this->ld_json['@type'] !== 'Person'){
				$this->ld_json['name'] = $replace['SITE_TITLE'];
				$this->ld_json['headline'] = $replace['TITLE'].$replace['SITE_TITLE'];
			}
			$this->ld_json['description'] = $description;
			$j = json_encode($this->ld_json, JSON_UNESCAPED_UNICODE);
			$html = str_replace('</body>', '<script type="application/ld+json">'.$j.'</script></body>', $html);
		}
		echo $html;
	}
	//エラーページ
	public function not_found(bool $is404=false, string $reason=''):void{
		$perhaps = $this->u_root;
		if ($is404){
			$this_file = $this->d_root.$_SERVER['REQUEST_URI'];
			if (isset($_SERVER['REQUEST_URI']) && file_exists($this_file)){
				if (is_file($this_file) && (preg_match('/(\.(cgi|pl|py|rb))$/si', $this_file) === 1)){
					chmod($this_file, 0745);
				} elseif (is_dir($this_file)){
					foreach (scandir($this_file) as $f){
						if (preg_match('/(\.(cgi|pl|py|rb))$/si', $f) === 1){
							chmod($this_file.'/'.$f, 0745);
						}
					}
				}
			}
			if (isset($this->config['error.moved_list'])){
				foreach (explode(' ', $this->config['error.moved_list']) as $moved){
					if (strpos($moved, '=>') !== false){
						list($euri, $turi) = explode('=>', $moved);
						if ($_SERVER['REQUEST_URI'] === trim($euri)){
							$perhaps = trim($turi);
							break;
						}
					}
				}
			}
		}
		$html_file = $this->data_dir.'/404/html/index.html';
		if (file_exists($html_file)){
			$html = file_get_contents($html_file);
			foreach (['PERHAPS'=>$perhaps,'REASON'=>$reason] as $k=>$v){
				$html = str_replace('{'.$k.'}', $v, $html);
			}
			$this->html(subrpos('<h1>', '</h1>', $html).'-', '', $html, $this->data_dir.'/404/css');
		}
		exit;
	}

	/*
	 * php.iniに以下を書くとクッキーが無効でもcheck_csrf_tokenを通過できます。
	 * session.use_trans_sid = 1
	 * session.trans_sid_tags = "form="
	*/
	//csrfトークンが返り値
	public function set_csrf_token(string $label, int $min=32, int $max=64):string{
		$t = one_time_pass($min, $max);
		$_SESSION['csrf_token__'.$label] = implode("'", [$t,$this->here]);
		return $t;
	}
	//labelはsetとcheckで同じ文字列にする。strictをtrueにするとリファラチェックする
	public function check_csrf_token(string $label, string $token, bool $strict):bool{
		if (isset($_SESSION['csrf_token__'.$label])){
			$d = explode("'", $_SESSION['csrf_token__'.$label]);
			if ($d[0] === $token && ($d[1]===$this->referer||!$strict)){
				return true;
			}
		}
		return false;
	}
	public function form_die():void{
		$this->html('フォーム損傷-', '', '<h1>フォーム損傷</h1><p>フォームに損傷があります。停止しました。</p>');#WEB改ざん等で処理出来ない時に使う
		exit;
	}
}

