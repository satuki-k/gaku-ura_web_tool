<?php
#gaku-ura9.6.14
#ログイン必須とは限らない機能を考慮し、ログインチェックは初期化では行わない
class GakuUraUser{
	public string $user_dir;
	public string $user_list_file;
	public array $user_list_keys;
	public array $own_dir;
	public int $admin_revel;
	public const SKEY_ID = 'gaku-ura_login:id';
	public const SKEY_PASSWD = 'galu-ura_login:passwd';
	public const SKEY_NAME = 'gaku-ura_login:name';
	public const SKEY_FROM = 'gaku-ura_login:from';
	#GakuUraオブジェクトが引数
	function __construct(object &$conf){
		if((int)($conf->config['login.enable']??0)===0) $conf->not_found(false,'この機能は無効です。');
		$this->own_dir = ['/','/','/','/','/'];
		if (isset($conf->config['login.dir'])){
			$dirs = explode(' ', $conf->config['login.dir']);
			if(count($dirs)===5) $this->own_dir=$dirs;
		}
		#個別に設定出来るように
		for($i=0;$i<5;++$i) if(isset($conf->config['login.dir'.$i])) $this->own_dir[$i]=$conf->config['login.dir'.$i];
		$this->admin_revel = (int)($conf->config['login.admin_revel']??3);
		if($this->admin_revel < 1) $conf->not_found(false,'管理者権限の要件が1未満で危険です');
		$this->user_dir = $conf->data_dir.'/users';
		$this->user_list_file = $this->user_dir.'/user_list.tsv';
		$this->user_list_keys = ['id','name','mail','passwd','admin','profile','enable'];
		if (file_exists($this->user_list_file)){
			$f = explode("\t", get($this->user_list_file, 1));
			foreach($this->user_list_keys as $k) if(!in_array($k,$f,true))$conf->not_found(false,'ユーザー管理ファイルに'.$k.'列がありません');
			$this->user_list_keys = $f;
		} else {
			file_put_contents($this->user_list_file, implode("\t",$this->user_list_keys)."\n", LOCK_EX);
		}
	}
	#区切り文字エスケープ
	public static function h(string $t):string{
		return str_replace("\t", '', $t);
	}
	#ユーザー情報の単純配列を、ユーザー一覧ファイルの1行目をキーにした連想配列に変換
	public function user_data_convert(array $row):array{
		$d = [];
		$l = count($this->user_list_keys);
		for($i=0;$i<$l;++$i) $d[$this->user_list_keys[$i]]=$row[$i]??'';
		return $d;
	}
	public function login_check():array{
		$r = ['result'=>false];
		$m = [self::SKEY_ID, self::SKEY_NAME, self::SKEY_PASSWD];
		if (list_isset($_SESSION,$m) && (int)$_SESSION[$m[0]]>0){
			$s = [];
			foreach($m as $i) $s[]=$_SESSION[$i];
			$l = get($this->user_list_file, (int)$s[0]+1);
			if ($l){
				$u = $this->user_data_convert(explode("\t", $l));
				if ((int)$u['enable']){
					if ((int)$u['id']===(int)$s[0] && $u['name']===$s[1] && $u['passwd']===$s[2]){
						$r['result'] = true;
						$r['user_data'] = $u;
					}
				}
			}
		}
		if (!$r['result'] && strpos($_SERVER['REQUEST_URI']??'','/login/')===false){
			$_SESSION[self::SKEY_FROM] = $_SERVER['REQUEST_URI'];#ログイン後復帰する用
		}
		return $r;
	}
	public function user_exists(string $name, string $mail=''):int{
		$i = 0;
		foreach (get_rows($this->user_list_file,2) as $l){
			++$i;
			$d = $this->user_data_convert(explode("\t", $l));
			if ((int)$d['enable'] === 1){
				if($name!==''&&$d['name']===$name) return $i;
				if($mail!==''&&$d['mail']===$mail) return $i;
			}
		}
		return 0;
	}
	#idが0なら新規登録になる
	public function change_user_data(object &$conf, array $user_data):void{
		$conf->file_lock('user_list');
		$rows = get_rows($this->user_list_file, 1);
		$row = '';
		foreach ($this->user_list_keys as $k){
			if(isset($user_data[$k])) $row.=trim(self::h($user_data[$k]));
			$row .= "\t";
		}
		$row = rtrim(row($row));
		if (!isset($user_data['id']) || ($user_data['id'] < 1)){
			$d = explode("\t", $row);
			$l = explode("\t", $rows[count($rows)-1]);
			for ($i = 0;$i < count($this->user_list_keys);++$i){
				if ($this->user_list_keys[$i] === 'id'){
					if($l[$i]==='id') $l[$i]=0;
					$d[$i] = (int)$l[$i] +1;
					file_put_contents($this->user_list_file, implode("\t",$d)."\n", FILE_APPEND|LOCK_EX);
					$_SESSION[self::SKEY_ID] = $d[$i];
					$_SESSION[self::SKEY_NAME] = $user_data['name'];
					$_SESSION[self::SKEY_PASSWD] = $user_data['passwd'];
					break;
				}
			}
		} else {
			$l = get($this->user_list_file, $user_data['id']+1);
			if ($l !== $row){
				$rows[$user_data['id']] = $row;
				file_put_contents($this->user_list_file, implode("\n",$rows)."\n", LOCK_EX);
			}
		}
		$conf->file_unlock('user_list');
	}
}

