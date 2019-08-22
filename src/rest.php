<?php
require_once("Config.php");
require('QuickTables.php' );
$jwt = null;
if(isset($_GET["access_token"]) && $_GET["access_token"]){
	$jwt = $_GET["access_token"];
}

if(isset($_POST["access_token"]) && $_POST["access_token"]){
	$jwt = $_POST["access_token"];
}

if(!$jwt){
	exit("Error: invalid token");
}

$action = "";

if(isset($_GET["draw"]) && $_GET["draw"]){
	$action = "draw";
}

if(isset($_GET["source"]) && $_GET["source"]){
	$action = "source";
}

if(isset($_GET["getRow"]) && $_GET["getRow"]){
	$action = "view";
}

if(isset($_GET["editRow"]) && $_GET["editRow"]){
	$action = "edit";
}

if(isset($_POST["saveRow"]) && $_POST["saveRow"]){
	$action = "edit";
}

if(isset($_POST["deleteRow"]) && $_POST["deleteRow"]){
	$action = "delete";
}

if(empty($action)){
	exit("unknown action");
}

$source = (isset($_GET["source"]) && $_GET["source"]) ? 1 : 0;
$config = new Config();

$request = JWT::decode($jwt, $config->secret);

if(isset($request->quick_table) && $request->quick_table->config->secret === $config->secret){
	$data = $request->quick_table;
	$quickTable = new QuickTables($data->table, $data->primaryKey, $data->config);
    $options = json_decode($data->options, true);
	$quickTable->initOptions($options);

	if(count($data->unsetButtons)){
		if($action !== "draw"){
			if(in_array($action, $data->unsetButtons)){
				exit("forbidden action ".$action);
			}

			if(!in_array($action, ["view", "edit", "delete", "source"])){
				exit("invalid action ".$action);
			}
		}
		$quickTable->unsetActions($data->unsetButtons);
	}

	if($source){
		exit($quickTable->source());
	}

	exit($quickTable->render());
}
exit("Error: invalid token");
