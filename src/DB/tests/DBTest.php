<?php
	use PHPUnit\Framework\TestCase;

	class DBTest extends TestCase{
		public function testConnect(){
			$db = new DB(
				$dsn = "mysql:host=127.0.0.1;dbname=demodev_schoolworx;",
				$user = "root",
				$password = "",
				$prefix = "jos_",
				$options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="Traditional"']
			);

			$db->setDebug(1);

			$this->assertInstanceOf(DB::class, $db);
			$this->assertNotNull($db->pdo);
			return $db;
		}

		/**
		** @depends testConnect
		**/

		public function testGetTable($db){
			$table = $db->getTable("users", "id");
			$this->assertInstanceOf(Table::class, $table);

			return $table;
		}

		/**
		** @depends testGetTable
		**/

		public function testTable($table){
			$this->assertInstanceOf(Table::class, $table);
			return $table->load(42);
		}

		/**
		** @depends testTable
		**/

		public function testTableLoad($tableLoad){
			$this->assertInstanceOf(TableLoad::class, $tableLoad);
		}
	}

?>
