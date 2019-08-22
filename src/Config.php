<?php
    require_once("/msw1/configuration.php");

	class Config{
		public $dbdriver = 'mysql';
		public $dbname = 'test';
		public $dbprefix = 'test_';
		public $dbuser = 'root';
	   	public $dbpass = '';
		public $dbhost = 'msw1-db';
		public $dboptions = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode=""'];
		public $path = '/tools/quick_tables/src/';
		public $secret = '';

        public function __construct(){}
	}

?>
