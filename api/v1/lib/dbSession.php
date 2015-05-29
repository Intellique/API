<?php
	require_once('db.php');

	interface DB_Session extends DB {
		public function getUser($id, $login);
	}

	require_once("db/${db_config['driver']}Session.php");

	$className = ucfirst($db_config['driver']) . 'DBSession';
	$dbDriver = new $className($db_config);
?>