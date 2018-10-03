<?php
	class Config{
		public $dbdriver = 'mysql';
		public $dbname = 'demodev_schoolworx';
		public $dbprefix = 'jos_';
		public $dbuser = 'root';
	   	public $dbpass = '';
		public $dbhost = '127.0.0.1';
		public $dboptions = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode=""'];
	}

?>
