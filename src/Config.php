<?php
	class Config{
		public $dbdriver = 'mysql';
		public $dbname = 'test';
		public $dbprefix = 'test_';
		public $dbuser = 'root';
	   	public $dbpass = '';
		public $dbhost = 'localhost';
		public $dboptions = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode=""'];
		public $path = './quick_tables/src/';
		public $secret = 'dB48TjkTJFuYXzHptPw2c/01XOWNRnfl33+Xy/4gpFQ=';
	}

?>
