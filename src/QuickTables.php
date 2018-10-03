<?php
	require_once("DB/src/DB.php");
	require_once("./Config.php");

	class QuickTables{
		private $includeJquery = 1;
		private $db = null;
		private $tableOjb = null;
		private $tableName = null;
		private $prefix = null;
		private $primaryKey = null;
		private $columns = [];
		private $is_query = 0;
		private $trOpen = "<tr>";
		private $trClose = "</tr>";
		private $thOpen = "<th>";
		private $thClose = "</th>";
		private $tdOpen = "<td>";
		private $tdClose = "</td>";
		private $tableOpen = "<table class='{{class_place}}'>";
		private $tableClose = "</table>";
		private	$theadOpen = "<thead>";
		private	$theadClose = "</thead>";
		private $tbodyOpen = "<tbody>";
		private $tbodyClose = "</tbody>";
		private $columnsName = [];
		private $columnsNameTemp = [];
		private $columnsNameLabels = [];
		private $hide = [];
		private $session = null;
		private $config = null;
		private $initOptions = [];
		private $restLocation = "./QuickTables.php";
		private	$startAjaxServer = 0;
		private	$request = [];
		private $tableHeadColor = 'rgb(193, 193, 193)';
		private $buttonsColor = '#dedede';
		private $showPrimaryKey = 1;

		public function __construct(string $table = ""){
			$this->table($table);
			$_SESSION['quick_table'] = [
				"table" => $table,
				"config" => $this->config,
				"columns" => &$this->columns,
				"columnsName" => &$this->columnsName
			];

			$this->request = $_GET;
		}

		public function table($table){
			if(empty($table)){
				trigger_error("table must be set", E_USER_ERROR);
			}

			$config = new Config();

			$this->db = $this->connection(
				"{$config->dbdriver}:host={$config->dbhost};dbname={$config->dbname};",
				$config->dbuser,
				$config->dbpass,
				$config->dbprefix,
				$config->dboptions
			);

			$this->config = (array)$config;

			$this->tableObj = $this->db->getTable($table, null);
			$this->columns = (array)$this->tableObj->getColumns();

			foreach($this->columns as $key => $column){
				$this->columns[$key]->newFieldName = "";
				$this->columns[$key]->newColumnName = "";
			}

			$this->primaryKey = $this->getPrimaryKey($this->columns);
			$this->columnsName = array_map(function($column){return $column->Field;}, $this->columns);

			$this->tableObj->setKey($this->primaryKey);
			$this->tableName = $table;
		}

		public function label($fieldName, $newName){
			if((isset($fieldName) && $fieldName) && (isset($newName) && $newName)){
				foreach($this->columns as $key => $column){
					if($this->columns[$key]->Field === $fieldName){
						$this->columns[$key]->newColumnName = $newName;
						$this->columnsNameLabels[] = [$fieldName => $newName];
						//$this->changeColumnName($fieldName, $newName);
					}
				}
			}
		}

		private function changeColumnName($fieldName, $newName){
			if((isset($fieldName) && $fieldName) && (isset($newName) && $newName)){
				foreach($this->columnsName as $key => $column){
					if($this->columnsName[$key] === $fieldName){
						$this->columnsName[$key] = $newName;
					}
				}
			}
		}

		private function dp() {
			$arg_lists = func_get_args();
			foreach ($arg_lists as $value) {
				echo '<pre>';
				print_r($value);
				echo '</pre>';
			}
		}

		private function dpe(...$args){
	        $this->dp(...$args);
	    	exit('stop_exit called');
		}

		public function connection($dsn, $user, $password, $prefix, $options){
			if(empty($dsn) || empty($user) || !isset($password)){
				trigger_error("Bad dsn", E_USER_ERROR);
			}
			$this->prefix = $prefix;
			return new DB($dsn, $user, $password, $prefix, $options);
		}

		public function hide(array $columns){
			$this->hide = $columns;
		}

		public function startTransaction(){
			$this->db->startTransaction = 1;
		}

		private function getPrimaryKey(array $columnsName){
			$pri = null;
			foreach($columnsName as $key => $name){
				if($name->Key === "PRI"){
					$pri = $name->Field;
					break;
				}
			}
			return $pri;
		}

		public function columns(array $columns){
			if(count($columns) && !$this->is_query){
				$this->columnsNameTemp = $columns;
				$this->columnsNameTemp[] = $this->primaryKey;
			}
		}

		private function prepareColumnsName(){
			$_columns = $this->getColumns();
			$columns = [];

			if(count($this->columnsNameLabels)){
				foreach($this->columnsNameLabels as $label){
					foreach($label as $key => $value){
						$foundKey = $this->findColumn($value, $_columns);
						if(is_numeric($foundKey)){
							$_columns[$foundKey] = $value;
						}
					}
				}
			}

			foreach($_columns as $key => $name){
				$columns[] = $this->thOpen . $name . $this->thClose;
			}

			$columns[] = $this->thOpen . 'Actions' . $this->thClose;
			return $this->trOpen . implode(" ", $columns). $this->trClose;
		}

		public function query($sql){
			$rows = (array)$this->tableObj->query($sql);
			$keys = [];

			if(count($rows)){
				$keys = array_keys((array)$rows[0]);
			}

			$this->is_query = 1;
			$this->columnsNames = $keys;
			$this->columnsValue = $rows;
		}

		private function findColumn($column, $columns){
			$return = null;
			foreach($columns as $key => $value){
				if($column === $value){
					$return = $key;
					break;
				}

				if($this->getRealName($column, $this->columns) === $value){
					$return = $key;
					break;
				}
			}
			return $return;
		}

		private function getColumns(){
			$columns = [];

			if(count($this->columnsNameTemp)){
				$columns = $this->columnsNameTemp;
				return $columns;
			}


			foreach($this->columns as $key => $column){
				$columns[] = $column->Field;
			}

			if(count($this->hide)){
				foreach($this->hide as $key => $column){
					$foundKey = $this->findColumn($column, $columns);
					if(is_numeric($foundKey)){
						unset($columns[$foundKey]);
					}
				}
			}

			return $columns;
		}

		private function prepareColumnsValues(){
			$fields = implode(",", $this->getColumns());

			if($this->is_query){
				$rows = $this->columnsValue;
			}else {
				$rows = (array)$this->tableObj->query("SELECT {$fields} FROM `{$this->prefix}{$this->tableName}` LIMIT 0,25");
			}

			$_values = [];
			$out = [];

			foreach($rows as $row){
				foreach($row as $key => $value){
					if($this->primaryKey !== $key){
						$_values[] = $this->tdOpen . $value . $this->tdClose;
					}
				}

				$out[] = $this->trOpen . implode(" ", $_values) . $this->trClose;
				$_values = [];
			}
			return implode(" ", $out);
		}

		public function setJquery(int $value = 1){
			$this->includeJquery($value);
		}

		public function initOptions(array $options){
			$options["ajax"] = $this->restLocation;
			$options["scrollX"] = true;
			$options["processing"] = true;
			$options["serverSide"] = true;
			$this->initOptions = json_encode($options);
		}

		private function getRealName($name, $columns){
			$return = $name;
			foreach($columns as $columnKey => $columnValue){
				//if($columnValue->Key === "PRI"){
					//$_columnsNames[] = $columnValue->Field;
					//continue;
				//}

				if($name === $columnValue->newColumnName){
					$return = $columnValue->Field;
					break;
				}
			}

			return $return;
		}

		private function prepareViewData($data, $class){
			$_data = [];
			$buttons = "";

			if($class === 'Edit'){
				$buttons = "
					<div id=\"quick_tables_control\">
						<button id=\"quick_tables_close\"><i class='fas fa-times'></i> Close</button>
						<button id=\"quick_tables_save\"><i class='fas fa-save'></i> Save</button>
					</div>
				";
			}

			if($class === 'View'){
				$buttons = "
					<div id=\"quick_tables_control\">
						<button id=\"quick_tables_close\"><i class='fas fa-times'></i> Close</button>
					</div>
				";
			}

			foreach($data as $key => $value){
				$key = ucfirst($key);
				$tmp = "
					{$this->trOpen}
						{$this->thOpen}
							{$key}
						{$this->thClose}
						{$this->tdOpen}
							{$value}
						{$this->tdClose}
					{$this->trClose}
				";
				$_data[] = $tmp;
			}
			$tbody = implode(" ", $_data);
			$tmpl = "
				<form id=\"quick_tables_form\">
					{$this->tableOpen}
						{$this->tbodyOpen}
							{$tbody}
						{$this->tbodyClose}
					{$this->tableClose}
					{$buttons}
				</form>
			";
			$tmpl = str_replace("{{class_place}}", $class, $tmpl);
			return $tmpl;
		}

		private function getEditFields(array $columns, stdClass $data){
			$fields = [];
			$row = $data;
			foreach($columns as $key => $value){
				$readOnly = $value->Key === "PRI" ? "readonly" : "";
				$size = [];
				$fieldName = $value->newColumnName ? $value->newColumnName : $value->Field;
				$fieldValue = $row->{$value->Field};
				$class = "class=\"inputs\"";

				if(!is_numeric($fieldValue)){
					$fieldValue = $row->{$value->Field} ? htmlspecialchars($row->{$value->Field}) : htmlspecialchars($value->Default);
				}

				preg_match("/\d+/", $value->Type, $size);
				$maxLength = "maxlength=\"{$size[0]}\"";

				if(preg_match("/int/", $value->Type) || preg_match("/tinyint/", $value->Type)){
					$isPrimary = $value->Key === "PRI" ? 1:0;
					$type = $value->Key === "PRI" ? "hidden" : "number";

					if($isPrimary){
						$class .= " readonly";
					}

					if(!$showPrimaryKey){
						$type = "number";
					}

					$fields[$fieldName] = "<input type=\"{$type}\" name=\"{$value->Field}\" value=\"{$fieldValue}\" {$readOnly} {$maxLength} {$class}/>";
				}

				if(preg_match("/varchar/", $value->Type)){
					$type = "text";
					$fields[$fieldName] = "<input type=\"{$type}\" name=\"{$value->Field}\" value=\"{$fieldValue}\" {$readOnly} {$maxLength} {$class}/>";
				}

				if(preg_match("/text/", $value->Type)){
					$type = "text";
					$fields[$fieldName] = "<input type=\"{$type}\" name=\"{$value->Field}\" value=\"{$fieldValue}\" {$readOnly} {$maxLength} {$class}/>";
				}

				if(preg_match("/datetime$/", $value->Type)){
					$type = "datetime-local";
					$fields[$fieldName] = "<input type=\"{$type}\" name=\"{$value->Field}\" value=\"{$fieldValue}\" {$readOnly} {$maxLength} {$class}/>";
				}

				if(preg_match("/date$/", $value->Type)){
					$type = "date";
					$fields[$fieldName] = "<input type=\"{$type}\" name=\"{$value->Field}\" value=\"{$fieldValue}\" {$readOnly} {$maxLength} {$class}/>";
				}
			}
			return $fields;
		}

		private function normalizeData(array $data){
			$return = [];
			foreach($data as $key => $value){
				$return[$value['name']] = $value['value'];
			}
			return (object)$return;
		}

		public function startAjaxServer($request){
			if(isset($request['getRow']) && $request['getRow'] && isset($request['id']) && $request['id']){
				if(isset($request['id']) && $request['id']){
					$id = (int)$request['id'];
					$row = $this->tableObj->load($id);
					$data = $row->getColumns();
					$row = $this->prepareViewData($data, "View");
					$return = ['row' => $row];
				} else {
					$return = ['error' => 'invalid id'];
				}
				exit(json_encode($return));
			}

			if(isset($request['editRow']) && $request['editRow']){
				if(isset($request['id']) && $request['id']){
					$id = (int)$request['id'];
					$columns = $this->columns;
					$data = $this->tableObj->load($id);
					$fields = $this->getEditFields($columns, $data->getColumns());
					$row = $this->prepareViewData($fields, "Edit");
					$return = ['row' => $row];
				} else {
					$return = ['error' => 'invalid id'];
				}
				exit(json_encode($return));
			}

			if(isset($request['saveRow']) && $request['saveRow']){
				if(isset($request['data']) && $request['data']){
					$data = $request['data'];
					$data = $this->normalizeData($data);
					$key = $data->{$this->primaryKey};
					$saved = $this->tableObj->updateObject($data);
					$error = $this->db->getError($this->db->pdo);
					$return = ['saved' => $saved];
				} else {
					$return = ['error' => 'invalid id'];
				}
				exit(json_encode($return));
			}

			if(isset($request['deleteRow']) && $request['deleteRow']){
				if(isset($request['id']) && $request['id']){
					$id = $request['id'];
					$deleted = $this->tableObj->delete($id);
					$return = ['deleted' => $deleted];
				} else {
					$return = ['error' => 'invalid id'];
				}
				exit(json_encode($return));
			}

			if(isset($request['draw']) && $request['draw']){
				$this->startAjaxServer = 1;
				$primaryKey = '';
				$_columns = $this->getColumns();
				$_columnsNames = [];
				$table = $this->config['dbprefix'].$this->tableName;
				$primaryKey = $this->primaryKey;

				$sql_details = [];
				$sql_details['user'] = $this->config['dbuser'];
				$sql_details['pass'] = $this->config['dbpass'];
				$sql_details['host'] = $this->config['dbhost'];
				$sql_details['db']   = $this->config['dbname'];

				$cnt = 0;
				foreach((array)$_columns as $columnNameKey => $columnNameValue){
					$_columnsNames[] = ['db' => $this->getRealName($columnNameValue, $this->columns), 'dt' => $cnt];
					$cnt++;
				}

				$foundKey = null;
				foreach($_columnsNames as $key => $value){
					if($value['db'] === $primaryKey){
						$foundKey = $key;
						break;
					}
				}

				if(!is_numeric($foundKey)){
					$_columnsNames[] = ['db' => $primaryKey, 'dt' => $cnt];
				}


				//foreach($this->columns as $columnKey => $columnValue){
					//if($columnValue->Key === 'PRI'){
						//$primaryKey = $columnValue->Field;
					//}
				//}

				// Array of database columns which should be read and sent back to DataTables.
				// The `db` parameter represents the column name in the database, while the `dt`
				// parameter represents the DataTables column identifier. In this case simple
				// indexes
				//$columns = array(
					//array( 'db' => 'first_name', 'dt' => 0 ),
					//array( 'db' => 'last_name',  'dt' => 1 ),
					//array( 'db' => 'position',   'dt' => 2 ),
					//array( 'db' => 'office',     'dt' => 3 ),
					//array(
						//'db'        => 'start_date',
						//'dt'        => 4,
						//'formatter' => function( $d, $row ) {
							//return date( 'jS M y', strtotime($d));
						//}
					//),
					//array(
						//'db'        => 'salary',
						//'dt'        => 5,
						//'formatter' => function( $d, $row ) {
							//return '$'.number_format($d);
						//}
					//)
				//);

				// SQL server connection information


				/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
				 * If you just want to use the basic configuration for DataTables with PHP
				 * server-side, there is no need to edit below this line.
				 */

				//print_r($_GET);
				//print_r($sql_details);
				//print_r($table);
				//print_r($primaryKey);
				//print_r($_columnsNames);

				//error_reporting(E_ALL);
				//ini_set("display_errors", 1);

				require( './ssp.class.php' );
				$data = (array)SSP::simple( $_GET, $sql_details, $table, $primaryKey, $_columnsNames );

				foreach((array)$data['data'] as $key => $value){
					$id = 0;

					if(is_numeric($foundKey)){
						$id = $value[$foundKey];
					} else {
						$id = array_pop($data['data'][$key]);
					}

					$data['data'][$key][] = "
						<button class='quick_table_view' data-id='{$id}'><i class='fas fa-eye'></i></button>
						<button class='quick_table_edit' data-id='{$id}'><i class='fas fa-pencil-alt'></i></button>
						<button class='quick_table_delete' data-id='{$id}'><i class='fas fa-times'></i></button>
					";
				}

				return json_encode($data);
			}
			return false;
		}

		public function setTableHeadColor(string $color){
			$this->tableHeadColor = $color;
		}

		public function render(){
			$columns = $this->prepareColumnsName();
			$values = $this->prepareColumnsValues();
			$request = count($_GET) ? $_GET:$_POST;
			$serverOutPut = $this->startAjaxServer($request);

			if($serverOutPut){
				return $serverOutPut;
			}

			$jquery =  '';

			if($this->includeJquery){
				$jquery =  '<script type="text/javascript" src="DataTables/media/js/jquery.js"></script>';
			}

			return '
			<div id="quick_tables_div">
				<link rel="stylesheet" type="text/css" href="iziModal/css/iziModal.min.css"/>
				<link rel="stylesheet" type="text/css" href="DataTables/media/css/jquery.dataTables.min.css"/>
				<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
				'.$jquery.'
				<script type="text/javascript" src="iziModal/js/iziModal.min.js"></script>
				<script type="text/javascript" src="DataTables/media/js/jquery.dataTables.min.js"></script>
				<table id="quick_tables_table" class="display nowrap">
					<thead>
						'.$columns.'
					</thead>
					<tbody>
						'.$values.'
					</tbody>
				</table>
				<div id="quick_tables_modal">
					<h1>Loading..</h1>
				</div>
				<script>
					let options = '.$this->initOptions.';
					$(document).ready(function () {
						const quickTablesModal = $("#quick_tables_modal");
						const server = "'.$this->restLocation.'";
						let table = $("#quick_tables_table").DataTable(options);

						quickTablesModal.iziModal({closeButton: false});
						quickTablesModal.iziModal("setHeaderColor", "'.$this->tableHeadColor.'");
						quickTablesModal.iziModal("setTop", 50);


						$("body").on("click", ".quick_table_view", function(e){
							let id = $(this).data("id");

							quickTablesModal.iziModal("open");
							quickTablesModal.iziModal("startLoading");

							$.getJSON(server, {getRow: true, id: id}, function(res){
								if(res.hasOwnProperty("row")){
									quickTablesModal.iziModal("setTitle", "View");
									quickTablesModal.iziModal("setIcon", "fas fa-eye");
									quickTablesModal.iziModal("setContent", res.row);
									quickTablesModal.iziModal("setWidth", "50%");
									window.setTimeout(function(){quickTablesModal.iziModal("stopLoading");}, 1000);
								}
							});
						});

						$("body").on("click", ".quick_table_edit", function(e){
							let id = $(this).data("id");

							quickTablesModal.iziModal("open");
							quickTablesModal.iziModal("startLoading");

							$.getJSON(server, {editRow: true, id: id}, function(res){
								if(res.hasOwnProperty("row")){
									quickTablesModal.iziModal("setTitle", "Edit");
									quickTablesModal.iziModal("setIcon", "fas fa-pencil-alt");
									quickTablesModal.iziModal("setContent", res.row);
									quickTablesModal.iziModal("setWidth", "50%");
									window.setTimeout(function(){quickTablesModal.iziModal("stopLoading");}, 1000);
								}
							});
						});

						$("body").on("click", "#quick_tables_close", function(e){
							e.preventDefault();
							quickTablesModal.iziModal("close");
						});

						$("body").on("click", "#close_alert", "#quick_tables_modal", function(e){
							e.preventDefault();
							quickTablesModal.iziModal("close");
							quickTablesModal.iziModal("setHeaderColor", "'.$this->tableHeadColor.'");
						});

						$("body").on("click", "#accept_alert", "#quick_tables_modal", function(e){
							let id = $(this).data("id");
							quickTablesModal.iziModal("startLoading");
							$.post(server, {deleteRow: true, id: id}, function(res){
								if(res.hasOwnProperty("deleted") && res.deleted){
									quickTablesModal.iziModal("stopLoading");
									quickTablesModal.iziModal("setHeaderColor", "'.$this->tableHeadColor.'");
									quickTablesModal.iziModal("close");
									table.ajax.reload();
								}
							}, "json");
						});

						$("body").on("click", "#quick_tables_save", function(e){
							let data = $("#quick_tables_form").serializeArray();
							e.preventDefault();
							$.post(server, {saveRow: true, data: data}, function(res){
								if(res.hasOwnProperty("saved") && res.saved){
									quickTablesModal.iziModal("close");
									table.ajax.reload();
								}
							}, "json");
						});

						$("body").on("click", ".quick_table_delete", function(e){
							let id = $(this).data("id");
							setAlert(id);

						});
						function setAlert(id){
							quickTablesModal.iziModal("setTitle", "Alert");
							quickTablesModal.iziModal("setHeaderColor", "red");
							quickTablesModal.iziModal("setContent", "<div id=\'alert_control\' align=\'center\'><p>Are you sure?</p><button id=\'close_alert\'>Close</button><button data-id="+id+" id=\'accept_alert\'>Accept</button><p></p></div>");
							quickTablesModal.iziModal("open");
						}
					});
				</script>
				<style>
					.dataTables_scrollHeadInner, .dataTable{
						/**width: 100% !important;**/
					}

					#quick_tables_modal .View{
						margin: 13px;
						padding-right: 26px;
    					width: 100%;
					}

					#quick_tables_modal .Edit{
						margin: 13px;
						padding-right: 26px;
    					width: 100%;
					}

					#quick_tables_modal .View th{
						font-family: Lato-Bold;
						font-size: 18px;
						color: #5f5f5f;
						line-height: 1.4;
						text-align: left;
						background-color: #dedede;
						padding-left: 4px;
					}

					#quick_tables_modal .Edit th{
						font-family: Lato-Bold;
						font-size: 18px;
						color: #5f5f5f;
						line-height: 1.4;
						text-align: left;
						background-color: #dedede;
						padding-left: 4px;
					}

					#quick_tables_modal .View td{
						border-bottom-style: solid;
						border-bottom-color: #d8d8d8;
						border-bottom-width: 1px;
						font-size: 17px;
					}

					#quick_tables_modal .Edit td{
						border-bottom-style: solid;
						border-bottom-color: #d8d8d8;
						border-bottom-width: 1px;
						font-size: 17px;
					}

					#quick_tables_modal .Edit td .inputs{
						width: 100%;
						padding: 7px;
					}

					#quick_tables_modal .Edit td .inputs.readonly{
						background-color: #c2c2c129;
					}

					#quick_tables_table_wrapper > div.dataTables_scroll > div.dataTables_scrollHead > div > table > thead > tr > th{
						background-color: '.$this->tableHeadColor.';
					}

					.dataTables_processing {
						z-index: 11000 !important;
					}

					#quick_tables_table button{
						border-radius: 6px;
						border: none;
						color: black;
						text-align: center;
						display: inline-block;
						font-size: 16px;
						margin: 4px 2px;
						-webkit-transition-duration: 0.4s;
						transition-duration: 0.4s;
						cursor: pointer;
						text-decoration: none;
						box-shadow: 1px 1px 1px #c1c1c1;
						background-color: '.$this->buttonsColor.'
					}

					#quick_tables_modal button{
						border-radius: 6px;
						border: none;
						color: black;
						text-align: center;
						display: inline-block;
						font-size: 16px;
						margin: 4px 2px;
						-webkit-transition-duration: 0.4s;
						transition-duration: 0.4s;
						cursor: pointer;
						text-decoration: none;
						box-shadow: 1px 1px 1px #c1c1c1;
						background-color: '.$this->buttonsColor.';
						height: 34px;
						width: 88px;
					}
					#quick_tables_modal #quick_tables_control{
						text-align: right;
						margin-right: 16px;
						margin-bottom: 10px;
					}
				</style>
			</div>
			';
		}
	}

	$data = new QuickTables("users");
	$data->startTransaction();
	$data->label("name", "Nombre");
	//$data->query("SELECT * FROM jos_users LIMIT 25;");
	//$data->columns(["name", "email", "username"]);
	//$data->label("email", "Correo");
	//$data->label("name", "Nombre");
	//$data->label("username", "Nombre Usuario");
	$data->initOptions([
		"paging" => "false"
	]);

	echo $data->render();
?>
