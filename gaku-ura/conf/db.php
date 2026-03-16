<?php
#gaku-ura9.6.16
/*
 * このファイルはスタンドアロンで動作します。conf.phpに依存しません。
 * データベースの操作
 * 例外処理やデータベースの種類による差異を自動で切り替え
 * sqlite,mysql,mariadbを想定
*/

#SQLインジェクション対策をするが型チェックなどの入力は確認しない
#行番号は「id」をテーブル作成時に自動作成します。where節で「id=n」のように指定できます
class GakuUraSQL{
	public string $is_connect;#接続失敗記録を保持
	public string $error_msg;#例外潰してもエラー文を見たい
	private string $dbtype;
	private string $dbname;
	private object $h;#PDOハンドル
	#データベースの種類, データベースの名前, [ホスト, ユーザー名, パスワード, 追加パラメーター]
	function __construct(string $dbtype, string $dbname, string $host='', string $user='', string $passwd='', string $opt=''){
		$this->error_msg = '';
		$d = strtolower($dbtype);
		$this->dbtype = $d;
		$this->dbname = $dbname;
		if (in_array($d,['sqlite','mysql','mariadb'])){
			if($opt!=='') $opt=';'.$opt;
			$dsn = $dbtype.':';
			try{
				$this->is_connect = true;
				if ($d === 'sqlite'){
					if(!is_file($this->dbname)&&!str_ends_with($this->dbname,'.db')) $this->dbname.='.db';
					if (is_file($this->dbname)){
						$fp = fopen($this->dbname, 'r');
						$l = fgets($fp);
						fclose($fp);
						if ($l==='' && !str_starts_with($l, 'SQLite format ')){
							$this->error_msg = 'is not DB file';
							$this->is_connect = false;
						}
					}
					if($this->is_connect) $this->h = new PDO($dsn.$this->dbname.$opt);
				} elseif ($host !== ''){
					$this->h = new PDO($dsn.'dbname='.$this->dbname.';host='.$host.$opt, $user, $passwd);
				} else {
					$this->is_connect = false;
				}
				if($this->is_connect) $this->h->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}catch(Exception $e){
				$this->error_msg = $e->getMessage();
				$this->is_connect = false;
			}
		} else {
			$this->error_msg = 'unsupported database type';
			$this->is_connect = false;
		}
	}
	#テーブル一覧取得
	public function get_tables():array{
		$t = [];
		if(!$this->is_connect) return [];
		try{
			if ($this->dbtype === 'sqlite'){
				$r = $this->h->query('SELECT name FROM sqlite_master WHERE type=\'table\';');
				foreach($r as $i)if($i[0]!=='sqlite_sequence') $t[]=$i[0];
			} else {
				$r = $this->h->query('SHOW TABLES;');
				foreach($r as $i) $t[]=$i[0];
			}
			return $t;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return [];
	}
	#テーブル存在確認
	public function table_exists(string $table):bool{
		return ($table!==''&&$this->is_connect&&in_array($table,$this->get_tables(),true));
	}
	/*
	テーブル作成 (テーブル名, 列[列=>型やオプション]連想配列指定)	連番idは行番号の代わりとして自動指定
	例: (table_name, ['name'=>'TEXT NOT NULL', 'email'=>'VARCHAR(100)', 'old'=>'INTEGER DEFAULT 0'])
	*/
	public function make_table(string $table, array $cols):bool{
		if(!$this->is_connect||$table==='') return false;
		try{
			$q = 'id INT PRIMARY KEY AUTO_INCREMENT';
			if($this->dbtype==='sqlite') $q='id INTEGER PRIMARY KEY AUTOINCREMENT';
			foreach($cols as $k=>$v) $q.=",{$k} {$v}";
			$this->h->exec("CREATE TABLE IF NOT EXISTS {$table} ({$q});");
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#テーブル変更
	public function change_table(string $table, string $new_name):bool{
		if(!$this->table_exists($table)||$this->table_exists($new_name)) return false;
		try{
			$this->h->exec("ALTER TABLE {$table} RENAME TO {$new_name};");
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#テーブル削除
	public function remove_table(string $table):bool{
		try{
			$this->h->exec("DROP TABLE IF EXISTS {$table};");
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#行数を取得
	public function count_rows(string $table):int{
		if(!$this->table_exists($table)) return 0;
		try{
			$r = $this->h->query("SELECT COUNT(*) FROM {$table};");
			foreach($r as $i) return $i[0];
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return 0;
	}
	#行を追加 (テーブル名, [列名=>値]連想配列)
	public function append_row(string $table, array $row):bool{
		if(!$this->table_exists($table)) return false;
		try{
			$c = $this->get_cols($table);
			$k = [];
			$b = [];
			$bl = [];
			foreach ($row as $i=>$v){
				if (in_array($i, $c, true)){
					$k[] = $i;
					$b[] = '?';
					$bl[] = $v;
				}
			}
			$p = $this->h->prepare("INSERT INTO {$table}(".implode(',',$k).") VALUES (".implode(',',$b).");");
			$p->execute($bl);
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
			return false;
		}
	}
	#列を取得
	public function get_cols(string $table):array{
		$a = [];
		if(!$this->table_exists($table)) return [];
		if(!in_array($table,$this->get_tables(),true)) return false;
		try{
			if ($this->dbtype === 'sqlite'){
				$r = $this->h->query("PRAGMA table_info({$table});");
				foreach($r as $i) $a[]=$i['name'];
			} else {
				$r = $this->h->query("SHOW COLUMNS FROM {$table};");
				foreach($r as $i) $a[]=$i['Field'];
			}
			return $a;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return [];
	}
	#行を取得 (テーブル名, [取得する列, 条件式, 条件式のプレースホルダーリスト, 列名を指定して並び替え, 昇順])
	public function get_rows(string $table, array $cols=[], string $where='', array $where_fmt=[], string $sort='', bool $reverse=false):array{
		$a = [];
		if(!$this->table_exists($table)) return $a;
		try{
			$cl = $this->get_cols($table);
			$c = ['*'];
			if ($cols !== []){
				$c = [];
				foreach($cols as $i)if(in_array($i,$cl,true)) $c[]=$i;
			}
			if($where!=='') $where=' WHERE '.$where;
			if ($sort!=='' && in_array($sort,$cl,true)){
				$sort = ' ORDER BY '.$sort.' ';
				$sort .= $reverse?'DESC':'ASC';
			}
			$p = $this->h->prepare("SELECT ".implode(',',$c)." FROM {$table}{$where}{$sort};");
			$p->execute($where_fmt);
			$rows = [];
			foreach($p as $row) $rows[]=$row;
			return $rows;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return [];
	}
	#値を変更 (テーブル名, [キー=>変更後の値,...]連想配列, [条件式, 条件式のプレースホルダーリスト])
	#例: 'table_name', ['key1'=>'value1','key2'=>value2], 'id=?', [$id]
	public function change_row(string $table, array $replace, string $where='', array $where_fmt=[]):bool{
		if(!$this->table_exists($table)) return false;
		try{
			if($where!=='') $where=' WHERE '.$where;
			$b = [];
			$s = [];
			$is_dict = false;
			foreach ($where_fmt as $k=>$v){
				if((int)$k!==$k) $is_dict=true;
			}
			if ($is_dict){
				foreach ($replace as $k=>$v){
					$s[] = "{$k}=:{$k}";
					$b[':'.$k] = $v;
				}
			} else {
				foreach ($replace as $k=>$v){
					$s[] = "{$k}=?";
					$b[] = $v;
				}
			}
			$p = $this->h->prepare("UPDATE {$table} SET ".implode(',',$s)."{$where};");
			if($where_fmt!==[]) $b=array_merge($b, $where_fmt);
			$p->execute($b);
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#列を変更
	public function change_col(string $table, string $col, string $new_name):bool{
		if(!$this->table_exists($table)) return false;
		try{
			$this->h->exec("ALTER TABLE {$table} RENAME COLUMN {$col} TO {$new_name};");
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#行を削除 whereを指定しないと全部削除
	public function remove_row(string $table, string $where='', array $where_fmt=[]):bool{
		if(!$this->table_exists($table)) return false;
		try{
			if($where!=='') $where=' WHERE '.$where;
			$p = $this->h->prepare("DELETE FROM {$table}{$where};");
			$p->execute($where_fmt);
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#列を追加
	public function append_col(string $table, string $name, string $type):bool{
		if(!$this->table_exists($table)) return false;
		try{
			$this->exec("ALTER TABLE {$table} ADD COLUMN {$name} {$type};");
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#列を削除
	public function remove_col(string $table, string $name):bool{
		if(!$this->table_exists($table)) return false;
		try{
			$this->exec("ALTER TABLE {$table} DROP COLUMN {$name};");
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#プリペアドステートメントで実行
	public function exec_secure(string $sql_query, array $fmt){
		if(!$this->is_connect) return false;
		try{
			$p = $this->h->prepare($sql_query);
			$p->execute($fmt);
			return $p;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#任意クエリ実行
	public function exec(string $sql_query){
		if(!$this->is_connect) return false;
		try{
			return $this->h->query($sql_query);
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
			return false;
		}
	}
}


