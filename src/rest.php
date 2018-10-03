
<?php

/*
 * DataTables example server-side processing script.
 *
 * Please note that this script is intentionally extremely simply to show how
 * server-side processing can be implemented, and probably shouldn't be used as
 * the basis for a large complex system. It is suitable for simple use cases as
 * for learning.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */

session_start();
$quickTable = $_SESSION['quick_table'];
$primaryKey = '';
$_columns = [];
$_columnsNames = [];
$table = '';
$sql_details = [
	'user' => '',
	'pass' => '',
	'db'   => '',
	'host' => ''
];

function getRealName($name, $columns){
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

if(count($quickTable)){
	$table = $quickTable['config']['dbprefix'].$quickTable['table'];
	$_columns = $quickTable['columns'];
	$sql_details['user'] = $quickTable['config']['dbuser'];
	$sql_details['pass'] = $quickTable['config']['dbpass'];
	$sql_details['host'] = $quickTable['config']['dbhost'];
	$sql_details['db']   = $quickTable['config']['dbname'];

	$cnt = 0;
	foreach((array)$quickTable['columnsName'] as $columnNameKey => $columnNameValue){
		$_columnsNames[] = ['db' => getRealName($columnNameValue, $_columns), 'dt' => $cnt];
		$cnt++;
	}

	foreach($_columns as $columnKey => $columnValue){
		if($columnValue->Key === 'PRI'){
			$primaryKey = $columnValue->Field;
		}
	}
}

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

error_reporting(E_ALL);
ini_set("display_errors", 1);

require( './ssp.class.php' );
echo json_encode(SSP::simple( $_GET, $sql_details, $table, $primaryKey, $_columnsNames ));
