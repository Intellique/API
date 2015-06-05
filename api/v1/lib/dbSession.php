<?php
	require_once('db.php');

	/**
	 * \brief specific interface for user, session, job
	 */
	interface DB_Session extends DB {
		/**
		 * \brief get user by id or login
		 * \param $id : User id or null
		 * \param $login : User login or null
		 * \return user or null if not found
		 */
		public function getUser($id, $login);

		/**
		 * \brief get users ID list
		 * \return Users ID list
		 */
		public function getUsers();
	}

	require_once("db/${db_config['driver']}Session.php");

	$className = ucfirst($db_config['driver']) . 'DBSession';
	$dbDriver = new $className($db_config);
?>