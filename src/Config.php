<?php
	class Config{
		public $dbdriver = 'mysql';
		public $dbname = 'demodev_schoolworx';
		public $dbprefix = 'jos_';
		public $dbuser = 'root';
	   	public $dbpass = '';
		public $dbhost = 'msw1-db';
		public $dboptions = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode=""'];
		public $path = '/tools/quick_tables/src/';
		public $secret = 'dB48TjkTJFuYXzHptPw2c/01XOWNRnfl33+Xy/4gpFQ=';
	}

?>
