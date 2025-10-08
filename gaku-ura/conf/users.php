<?php
/*
 * gaku-ura9.5.15
*/

//ログイン必須とは限らない機能を考慮し、ログインチェックは初期化では行わない
class GakuUraUser{
	public string $user_dir;
	public string $user_list_file;
	public array $user_list_keys;
	public array $own_dir;
	public int $admin_revel;

	//GakuUraオブジェクトが引数
	function __construct(object &$conf){
		if (!isset($conf->config['login.enable']) || (int)$conf->config['login.enable'] === 0){
			$conf->not_found(false, 'この機能は無効です。');
		}
		$this->own_dir = ['/', '/', '/', '/', '/'];
		if (isset($conf->config['login.dir'])){
			$dirs = explode(' ', $conf->config['login.dir']);
			if (count($dirs) === 5){
				$this->own_dir = $dirs;
			}
		}
		$this->admin_revel = 3;
		if (isset($conf->config['login.admin_revel']) && (int)$conf->config['login.admin_revel'] > 0){
			$this->admin_revel = (int)$conf->config['login.admin_revel'];
		}
		$this->user_dir = $conf->data_dir.'/users';
		$this->user_list_file = $this->user_dir.'/user_list.tsv';
		$this->user_list_keys = ['id','name','mail','passwd','admin','profile','enable'];
		if (file_exists($this->user_list_file)){
			$first = explode("\t", get($this->user_list_file, 1));
			foreach ($this->user_list_keys as $k){
				if (!in_array($k, $first, true)){
					$conf->not_found();
				}
			}
			$this->user_list_keys = $first;
		} else {
			file_put_contents($this->user_list_file, implode("\t",$this->user_list_keys)."\n", LOCK_EX);
		}
	}

	//区切り文字エスケープ
	public static function h(string $t):string{
		return str_replace("\t", '', $t);
	}

	//ユーザー情報の単純配列を、ユーザー一覧ファイルの1行目をキーにした連想配列に変換
	public function user_data_convert(array $user_row):array{
		$d = [];
		$l = count($this->user_list_keys);
		for ($i = 0;$i < $l;++$i){
			if (isset($user_row[$i])){
				$d[$this->user_list_keys[$i]] = $user_row[$i];
			} else {
				$d[$this->user_list_keys[$i]] = '';
			}
		}
		return $d;
	}

	public function login_check():array{
		$result = ['result'=>false];
		if (list_isset($_SESSION, ['gaku-ura_login:id','gaku-ura_login:passwd']) && (int)$_SESSION['gaku-ura_login:id'] > 0 && not_empty($_SESSION['gaku-ura_login:passwd'])){
			$row = get($this->user_list_file, (int)$_SESSION['gaku-ura_login:id'] +1);
			if ($row !== false){
				$user_data = $this->user_data_convert(explode("\t", $row));
				if (isset($user_data['enable']) && ((int)$user_data['enable'] === 1)){
					if (list_isset($user_data, ['id','passwd']) && (int)$user_data['id']===(int)$_SESSION['gaku-ura_login:id'] && $user_data['passwd']===$_SESSION['gaku-ura_login:passwd']){
						$result['result'] = true;
						$result['user_data'] = $user_data;
					}
				}
			}
		}
		return $result;
	}

	public function user_exists(string $name, string $mail=''):int{
		$l = count($this->user_list_keys);
		$uid = 0;
		foreach (get_rows($this->user_list_file, 2) as $row){
			++$uid;
			$d = $this->user_data_convert(explode("\t", $row));
			if ((int)$d['enable'] === 1){
				if (($name !== '') && ($d['name'] === $name)){
					return $uid;
				}
				if ($mail !== '' && $d['mail'] === $mail){
					return $uid;
				}
			}
		}
		return 0;
	}

	//idが0なら新規登録になる
	public function change_user_data(object &$conf, array $user_data):void{
		$conf->file_lock('user_list');
		$user_rows = get_rows($this->user_list_file, 1);
		$user_row = '';
		foreach ($this->user_list_keys as $k){
			if (isset($user_data[$k])){
				$user_row .= trim(self::h($user_data[$k]));
			}
			$user_row .= "\t";
		}
		$user_row = rtrim(row($user_row));
		if (!isset($user_data['id']) || ($user_data['id'] < 1)){
			$user_d = explode("\t", $user_row);
			$last = explode("\t", $user_rows[count($user_rows) -1]);
			for ($i = 0;$i < count($this->user_list_keys);++$i){
				if ($this->user_list_keys[$i] === 'id'){
					if ($last[$i] === 'id'){
						$last[$i] = 0;
					}
					$user_d[$i] = (int)$last[$i] +1;
					file_put_contents($this->user_list_file, implode("\t", $user_d)."\n", FILE_APPEND|LOCK_EX);
					$_SESSION['gaku-ura_login:id'] = $user_d[$i];
					$_SESSION['gaku-ura_login:passwd'] = $user_data['passwd'];
					break;
				}
			}
		} else {
			$row = get($this->user_list_file, $user_data['id'] +1);
			if ($row !== $user_row){
				$user_rows[$user_data['id']] = $user_row;
				file_put_contents($this->user_list_file, implode("\n", $user_rows)."\n", LOCK_EX);
			}
		}
		$conf->file_unlock('user_list');
	}
}

