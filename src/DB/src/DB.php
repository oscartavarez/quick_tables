<?php
	class DB{
		public $pdo = null;
		private $tables = [];
		private $table = null;
		private $dbDriver = null;
		private $dbName = null;
		private $dbHost = null;
		private $dbUser = null;
		private $dbPass = null;
		private $dbPrefix = '';
		private $dsn = "";
		private $exception = null;
		private $prefix = '';
		private $row = null;
		private $options = [];
		public	$debug = 0;
		static private $instance = null;

		public function __construct($dsn = "", $dbUser, $dbPass, $dbPrefix, $options = []){
			if(empty($dsn)){
				trigger_error("dsn must be set", E_USER_ERROR);
			}

			$this->pdo = $this->connect($dsn, $dbUser, $dbPass, $options);
			$this->dbuser	= $dbUser;
			$this->dbpass	= $dbPass;
			$this->dbPrefix = $dbPrefix;
			$this->dsn = $dsn;
			$this->options = $options;
		}

		public static function getInstance($dsn = "", $dbUser, $dbPass, $dbPrefix, $options = []){
			if($dsn === $this->dsn){
				return self::$instance;
			}
			return self::$instance = new DB($dsn, $dbUser, $dbPass, $dbPrefix, $options);
		}

		protected function connect($dsn, $dbUser, $dbPass, $options = []){
			try {
				$dbh = new PDO($dsn, $dbUser, $dbPass, $options);
				$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
				$this->pdo = $dbh;
				$this->tables = $this->_getTables();
				return $dbh;
			} catch (PDOException $e) {
				$this->exception_handler($e);
			}
		}

		public function getDbPrefix(){
			return $this->dbPrefix;
		}

		public function exception_handler($exception) {
			$this->exception = $exception;
			echo "Exception: Error " , $exception, "\n";
			echo "try DB->getError() to get full details \n";
		}

		public function getTables(){
			return $this->_getTables();
		}


		public function getTable($tableName, $key = null){
			return new Table($this, $tableName, $key);
		}

		private function _getTables(){
			$tables = $this->_query("SHOW TABLES");

			if(!$tables){
				return [];
			}

			return (array)$tables->fetchAll(PDO::FETCH_COLUMN);
		}

		public function changeDb($dbname){
			$name = $this->pdo->quote($dbname);
			$name = str_replace("'", "`", $name);
			$changed = $this->pdo->exec("USE {$dbname};");
			$error = $this->getError($this->pdo);

			if($error){
				exit("error: ".$error."\n");
			}

			return $changed;
		}

		public function close(){
			$this->pdo = null;
		}

		public function query($query){
			$return =  $this->_query($query);

			if($return){
				return $return->fetchAll(PDO::FETCH_OBJ);
			}

			return null;
		}

		public function setDebug($value = 0){
			$this->debug = $value;
		}

		private function _query($query){
			if(!$query) {
				trigger_error("query param is missing!", E_USER_ERROR);
			}

			$result = $this->pdo->query($query);
			$error = $this->getError($this->pdo);


			if($error){
				trigger_error("Error: ".$error, E_USER_ERROR);
			}

			return $result;
		}

		public function execute($sql, array $values = []){
			if((isset($sql) && $sql) && (isset($values) && count($values) > 0)){

				$stmt = $this->pdo->prepare($sql);
				$return = $stmt->execute($values);
				$error = $this->getError($stmt);

				if($error) {
					trigger_error("Error: ". $error, E_USER_ERROR);
				}

				$rows = (array)$stmt->fetchAll(PDO::FETCH_OBJ);

				if(count($rows) > 0){
					return $rows;
				}

				return $return;
			}

			trigger_error("Error: invalid sql or empty values on execute function", E_USER_ERROR);
		}

		public function getError($pdo){
			$_error = $pdo->errorInfo();
			if($_error[0] === "00000"){
				return false;
			}

			$error = $_error[1];

			if($this->debug){
				$error = $_error[2];
			}

			return $error;
		}

		public function multiQuery($file){
			if(!$file) {
				trigger_error("query file empty!", E_USER_ERROR);
			}

			$sqls = $this->splitSql($sql);

			foreach ($sqls as $key => $sql) {
				if(strlen($sql) > 1){
					$this->pdo->exec($file);
				}
			}
		}

		public function startTransaction(){
			$this->pdo->beginTransaction();
		}

		public function commit(){
			$this->pdo->commit();
		}

		public function rollback(){
			$this->pdo->rollBack();
		}

		//Taken from Joomla

		protected function splitSql($sql){
			$start = 0;
			$open = false;
			$char = '';
			$end = strlen($sql);
			$queries = array();

			for ($i = 0; $i < $end; $i++)
			{
					$current = substr($sql, $i, 1);
					if (($current == '"' || $current == '\''))
					{
							$n = 2;

							while (substr($sql, $i - $n + 1, 1) == '\\' && $n < $i)
							{
									$n++;
							}

							if ($n % 2 == 0)
							{
									if ($open)
									{
											if ($current == $char)
											{
													$open = false;
													$char = '';
											}
									}
									else
									{
											$open = true;
											$char = $current;
									}
							}
					}

					if (($current == ';' && !$open) || $i == $end - 1)
					{
							$queries[] = substr($sql, $start, ($i - $start + 1));
							$start = $i + 1;
					}
			}

			return $queries;
		}
	}

	final class Row{
		private $table = null;
		private $columns = [];
		private $prefix;
		private $tableKey = 0;
		private $tableName = "";
		public	$db = null;

		public function __construct(Table $table, $key, $keyValue){
			$this->table = $table;
			$this->db = $table->db;
			$this->prefix = $this->db->getDbPrefix();
			$this->tableName = $table->getTableName();
			$this->tableKey = $key;
			$this->keyValue = $keyValue;

			$result = $this->table->execute("SELECT * FROM ". $this->prefix . $this->tableName. ' WHERE '. $this->tableKey. ' = ?', [$keyValue]);
			$error = $this->db->getError($this->db->pdo);

			if($error) {
				trigger_error("Error: ". $error, E_USER_ERROR);
			}

			if(!$result){
				return $this->columns;
			}

			$rows =  $result;

			if(count($rows)){
				$row = $rows[0];
				$this->columns = $row;
			}
		}

		public function update(){
			return $this->db->updateObject($this->columns);
		}

		public function delete(){
			if(!$this->$keyValue) {
				trigger_error("key value is required at delete method!", E_USER_ERROR);
			}

			$result = $this->db->execute("DELETE FROM ". $this->prefix . $this->tableName. ' WHERE '. $this->tableKey. ' = ?', [$key]);
			$error = $this->db->getError($this->db->pdo);

			if($error) {
				trigger_error("Error: ". $error, E_USER_ERROR);
			}

			return $result;
		}

		public function getColumn($column){
			if(isset($this->columns->{$column}) && $this->columns->{$column}){
				return $this->columns->{$column};
			}

			return null;
		}

		public function getColumns(){
			return $this->columns;
		}

		public function setColumn($column, $newValue){
			if(isset($this->columns->{$column}) && $this->columns->{$column}){
				if(isset($newValue)){
					$this->columns->{$column} = $newValue;
					return true;
				}

				trigger_error("newValue needs to be set in SetTableMember function", E_USER_ERROR);
			}

			return false;
		}
	}

	final class Table{
		private $tableKey = 0;
		private $tableName = "";
		private $columns = array();
		private $prefix = null;
		public  $db = null;

		public function __construct(DB $db, $tableName, $key = null){
			if(empty($tableName)){
				trigger_error("tableName must be set", E_USER_ERROR);
			}

			$this->prefix = $db->getDbPrefix();
			$table = $db->pdo->quote($this->prefix . $tableName);
			$table = str_replace("'", "`", $table);
            $sql = "SHOW COLUMNS FROM $table";
			$result = $db->query($sql);
			$error = $db->getError($db->pdo);

			if($error){
				exit("error: SQL ".$error."\n");
			}

			$columns = $result;

            $this->tableName = $tableName;

			if($key){
				$this->tableKey = $key;
			}

			if(count($columns)){
				$this->columns = $columns;
			}

			$this->db = $db;
		}

		public function setKey($key = null){
			$this->tableKey = $key;
		}

		public function getTableName(){
			return $this->tableName;
		}

		public function load($id = 0){
			if(!$id) {
				trigger_error("key param is required at load method!", E_USER_ERROR);
			}

			if(!$this->tableKey) {
				trigger_error("key param not defined at getTable method!", E_USER_ERROR);
			}

			return new Row($this, $this->tableKey, $id);
		}


		public function delete($id){
			if(!$id) {
				trigger_error("key param is required at delete method!", E_USER_ERROR);
			}

			if(!$this->tableKey) {
				trigger_error("key param not defined at getTable method!", E_USER_ERROR);
			}

			$result = $this->db->execute("DELETE FROM ". $this->prefix . $this->tableName. ' WHERE '. $this->tableKey. ' = ?', [$id]);
			$error = $this->db->getError($this->db->pdo);

			if($error) {
				trigger_error("Error: ". $error, E_USER_ERROR);
			}

			return $result;
		}


		public function selectRows($start = 0, $limit = 0, $where = [], $orderby = ""){
			$limitcondition = "";

			if($limit){
				$limitcondition = " LIMIT $start,$limit";
			}

			$_where = [];
			$cnt = 0;
			foreach ($where as $key => $value) {
				$val = $this->db->pdo->quote($value->value);
				if($cnt === 0){
					$_where[] = " WHERE {$value->column} {$value->operator} {$val} ";
				} else {
					$_where[] = " AND {$value->column} {$value->operator} {$val} ";
				}
				$cnt++;
			}

			$whereString = implode(" ", $_where);

			$sql = "SELECT * FROM {$this->prefix}{$this->tableName} {$whereString} {$limitcondition}";
			$rows = $this->db->query($sql);
			$error = $this->db->getError($this->db->pdo);

			if($error) {
				trigger_error("Error: ". $error, E_USER_ERROR);
			}

			if(!$rows){
				return [];
			}

			return $rows;
		}

		public function getTableColumns(){
			$table = $this->db->pdo->quote($this->db->dbPrefix . $this->db->tableName);
			$table = str_replace("'", "`", $table);
            $sql = "SHOW COLUMNS FROM $table";
			$result = $this->db->_query($sql);
			$error = $this->db->getError($this->db->pdo);

			if($error){
				exit("error: SQL ".$error."\n");
			}

			$columns = (array)$result->fetchAll(PDO::FETCH_OBJ);
			return $columns;
		}

		public function execute($sql, $values = []){
			if(!preg_match("/{$this->tableName}/", $sql)){
				trigger_error("Cannot use execute out of {$this->tableName} context", E_USER_ERROR);
			}

			return $this->db->execute($sql, $values);
		}

		public function query($sql){
			if(!preg_match("/{$this->tableName}/", $sql)){
				trigger_error("Cannot use execute out of {$this->tableName} context", E_USER_ERROR);
			}

			return $this->db->query($sql);
		}

		public function getColumns(){
			return $this->columns;
		}

		private function getKeyValue(){
			$value = null;
			foreach($this->columns as $_key => $_value){
				if($this->tableKey === $_key){
					$value = $_value;
					break;
				}
			}
			return $value;
		}

		public function updateObject($data = null){
			if(!$data){
				trigger_error("Needs some data to update", E_USER_ERROR);
			}

			if(!$this->tableKey){
				trigger_error("This function needs a primary key", E_USER_ERROR);
			}

			$keys = array_keys((array)$data);
			$values = array_values((array)$data);
			$updateStr = [];

			foreach ($keys as $key => $value) {
				$updateStr[] = $value." = ?";
			}

			$keyValue = $this->getKeyValue();

			$update = implode(', ', $updateStr);
			//casting to int escape value
			$id = (int)$data->{$this->tableKey};

			if(!$id){
				trigger_error("Invalid id", E_USER_ERROR);
			}

			$sql = "UPDATE {$this->prefix}{$this->tableName} SET {$update} WHERE {$this->tableKey} = {$id}";
			$stmt = $this->db->pdo->prepare($sql);
			$return = $stmt->execute($values);
			$error = $this->db->getError($stmt);

			if($error) {
				trigger_error("Error: ". $error, E_USER_ERROR);
			}

			return $return;
		}

		public function insertObject($data = null){

			$keys = array_keys((array)$data);

			if(!$data || !count($keys)){
				trigger_error("Needs some data to insert", E_USER_ERROR);
			}

			$_values = array_values((array)$data);
			$insertColumnsStr = [];
			$insertValuesStr = [];

			foreach ($keys as $key => $value) {
				$column = $this->db->pdo->quote($keys[$key]);
				$column = str_replace("'", "`", $column);
				$insertColumnsStr[] = $column;
				$insertValuesStr[] = "?";
			}

			$values = "VALUES(".implode(', ', $insertValuesStr).")";
			$columns = "(".implode(', ', $insertColumnsStr).")";

			$sql = "INSERT INTO {$this->prefix}{$this->tableName}  {$columns} {$values}";
			$stmt = $this->db->pdo->prepare($sql);
			$return = $stmt->execute($_values);
			$error = $this->db->getError($stmt);

			if($error) {
				trigger_error("Error: ". $error, E_USER_ERROR);
			}

			return $return;
		}
	}
?>
