<?php
#gaku-ura9.7.5
/*
 * このファイルはスタンドアロンで動作します。conf.phpに依存しません
 * データベースの操作
 * 例外処理やデータベースの種類による差異を自動で切り替え
 * sqlite,mysql,mariadbを想定
*/
class GakuUraSQL{
	public string $is_connect;#接続失敗記録を保持
	public string $error_msg;#例外潰してもエラー文を見たい
	public string $id_col;#一意idに使う列名(デフォルト:"id")
	private string $dbtype;
	private string $dbname;
	private object $h;#PDOハンドル
	#データベースの種類, データベースの名前, [ホスト, ユーザー名, パスワード, 追加パラメーター]
	function __construct(string $dbtype, string $dbname, string $host='', string $user='', string $passwd='', string $opt=''){
		$this->error_msg = '';
		$d = strtolower($dbtype);
		$this->dbtype = $d;
		$this->dbname = $dbname;
		$this->id_col = 'id';
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
	#table一覧取得
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
	#table存在確認
	public function table_exists(string $table):bool{
		return ($table!==''&&$this->is_connect&&in_array($table,$this->get_tables(),true));
	}
	/*
	table作成 (table名, 列[列=>型やオプション]連想配列指定)	一意idは自動作成
	例: (table_name, ['col_name'=>'型・オプション等',...])
	*/
	public function make_table(string $table, array $cols):bool{
		if(!$this->is_connect||$table==='') return false;
		try{
			$q = $this->id_col.' INT PRIMARY KEY AUTO_INCREMENT';
			if($this->dbtype==='sqlite') $q=$this->id_col.' INTEGER PRIMARY KEY AUTOINCREMENT';
			foreach($cols as $k=>$v) $q.=",{$k} {$v}";
			$this->h->exec("CREATE TABLE IF NOT EXISTS {$table} ({$q});");
			return true;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return false;
	}
	#table変更
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
	#table削除
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
	#行を追加 (table名, [列名=>値]連想配列)
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
	#列の情報を取得
	public function get_cols_info(string $table):array{
		$a = [];
		if(!$this->table_exists($table)) return [];
		if(!in_array($table,$this->get_tables(),true)) return false;
		try{
			if ($this->dbtype === 'sqlite'){
				$r = $this->h->query("PRAGMA table_info({$table});");
				foreach($r as $i) $a[$i['name']]=$i;
			} else {
				$r = $this->h->query("SHOW COLUMNS FROM {$table};");
				foreach($r as $i) $a[$i['Field']]=$i;
			}
			return $a;
		}catch(Exception $e){
			$this->error_msg = $e->getMessage();
		}
		return [];
	}
	#列を取得
	public function get_cols(string $table):array{
		return array_keys($this->get_cols_info($table));
	}
	#列の情報をSQL文に使える引数文字列の配列で取得
	public function get_cols_type(string $table, bool $add_ext=false):array{
		$i = $this->get_cols_info($table);
		if($i===[]) return [];
		$a = [];
		if ($this->dbtype === 'sqlite'){
			foreach ($i as $k=>$v){
				$a[$k] = implode('', [$v['type'],$v['notnull']?' NOT NULL':'',$v['dflt_value']?' DEFAULT '.$v['dflt_value']:'']);
			}
		} else {
			foreach ($i as $k=>$v){
				$a[$k] = implode('', [$v['Type'],$v['Null']?' NOT NULL':'',$v['Default']?' DEFAULT '.$v['Default']:'']);
			}
		}
		return $a;
	}
	#行を取得 (table名, [取得する列, 条件式, 条件式のプレースホルダーリスト, 列名を指定して並び替え, 昇順])
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
	#値を変更 (table名, [キー=>変更後の値,...]連想配列, [条件式, 条件式のプレースホルダーリスト])
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
	#列を変更 (table, 列名, これに変更, [型変更 sqliteは非対応])
	public function change_col(string $table, string $col, string $new_name, string $type=''):bool{
		if(!$this->table_exists($table)) return false;
		try{
			$this->h->exec("ALTER TABLE {$table} RENAME COLUMN {$col} TO {$new_name};");
			if($type!==''&&$this->dbtype!=='sqlite') $this->h->exec("ALTER TABLE {$table} ALTER COLUMN {$col} {$type};");
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
	#CSVインポート (table名, 単純二次元配列, 一行目をタイトルに使用, 既にtableが存在する場合に削除せず追記)
	public function import(string $table, array $rows, bool $first_is_col=false, bool $add=true):bool{
		if($rows===[]||($first_is_col&&count($rows)<2)) return false;
		$cols = [];
		$col = [];
		$pl = [];
		$pp = [];
		$a = [];
		$s = 0;
		$l = 0;
		foreach($rows as $r)if(($c=count($r))>$l) $l=$c;
		if ($first_is_col){
			foreach($rows[0]as$i) $col[]=$i;
			for($i=count($col);$i<$l;++$i) $col[]='C'.$i;
			$idi = array_search('id', $col, true);
			if ($idi !== false){
				--$l;
				unset($col[$idi]);
				for($n=count($rows),$i=0;$i<$n;++$i) unset($rows[$i][$idi]);
			}
			$s = 1;
		} else {
			for($i=0;$i<$l;++$i) $col[]='C'.$i;
		}
		foreach ($col as $i=>$v){
			$r = $rows[$s][$i]??'';
			$t = 'TEXT';
			if ((string)(int)$r === $r){
				$t = $this->dbtype==='sqlite'?'INTEGER':'INT';
			} elseif ((string)(float)$r === $r){
				$t = 'REAL';
			} elseif (in_array($r,['TRUE','FALSE'],true)){
				$t = 'BOOL';
			}
			$cols[$v] = $t;
			$pl[] = '?';
		}
		if(!$add) $this->remove_table($table);
		if ($this->table_exists($table)){
			$c = $this->get_cols($table);
			if (count($c) !== $l){
				$this->error_msg = 'not modify col';
				return false;
			}
		} elseif (!$this->make_table($table, $cols)){
			return false;
		}
		$p = '('.implode(',', $pl).')';
		foreach ($rows as $i=>$v){
			if($i<$s) continue;
			$a[] = $p;
			$c = count($v);
			if($c<$l) for($j=$c;$j<$l;++$j)$v[]='';
			$pp = array_merge($pp, $v);
		}
		return $this->exec_secure("INSERT INTO {$table}(".implode(',',$col).") VALUES ".implode(',',$a).";",$pp)!==false;
	}
	#CSVエクスポート (table名, 区切り文字,[カンマ、タブ、クォーテーションのみ可] エスケープモード[csv->\を使う html->エンティティに変換(推奨)])
	public function export(string $table, string $sep=',', string $esc='html'):string{
		if(!$this->table_exists($table)) return '';
		if($sep==='') $sep=',';
		$es = [','=>'&#44;',"\t"=>'&#09;','"'=>'&#34;',"'"=>'&#39;'];
		if(!isset($es[$sep])||!in_array($esc,['csv','html'])) return '';
		$col = $this->get_cols($table);
		$row = $this->get_rows($table);
		$rows = [];
		if($esc==='csv') $es[$sep]='\\'.$es[$sep];
		$f = [];
		foreach($col as $c) $f[]=str_replace($sep,$es[$sep],$c);
		$rows[] = implode($sep, $f);
		foreach ($row as $r){
			$d = [];
			foreach($r as $k=>$v) if(in_array($k,$col,true))$d[]=str_replace($sep,$es[$sep],$v??'');
			$rows[] = implode($sep, $d);
		}
		return implode("\n",$rows)."\n";
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


