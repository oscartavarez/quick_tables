<?php
final class Table{
	private $tableKey = 0;
	private $tableName = "";
	private $columns = array();
	private $prefix = null;
	public $db = null;

	public function __construct(DB $db, $tableName, $key = null){
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

	public function getTableName(){
		return $this->tableName;
	}

	public function load(int $id = 0){
		if(!$id) {
			trigger_error("key param is required at load method!", E_USER_ERROR);
		}

		if(!$this->tableKey) {
			trigger_error("key param not defined at getTable method!", E_USER_ERROR);
		}

		return new TableLoad($this, $this->tableKey, $id);
	}


	public function delete(int $id){
		if(!$id) {
			trigger_error("key param is required at delete method!", E_USER_ERROR);
		}

		if(!$this->tableKey) {
			trigger_error("key param not defined at getTable method!", E_USER_ERROR);
		}

		$result = $this->db->query("DELETE FROM ". $this->prefix . $this->tableName. ' WHERE '. $this->tableKey. ' = '. $id);
		$error = $this->db->getError($this->db->pdo);

		if($error) {
			trigger_error("Error: ". $error, E_USER_ERROR);
		}

		return $result;
	}


	public function selectRows(int $start = 0, int $limit = 0, $where = [], $orderby = ""){
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

	public function save(){
		return $this->db->updateObject($this->db->tableColumns);
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

	public function updateObject(object $data = null){
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

		$sql = "UPDATE {$this->prefix}{$this->tableName} SET {$update} WHERE {$this->tableKey} = {$keyValue}";
		$stmt = $this->db->pdo->prepare($sql);
		$return = $stmt->execute($values);
		$error = $this->db->getError($stmt);

		if($error) {
			trigger_error("Error: ". $error, E_USER_ERROR);
		}

		return $return;
	}

	public function insertObject(object $data = null){

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
