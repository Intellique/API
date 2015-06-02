<?php
	require_once('db.php');

	/**
	 * \brief specific interface for user, session, job.
	 */
	interface DB_Session extends DB {
		/**
		 * \brief get user by id or login.
		 * \param $id : id of user or null
		 * \param $login : login of user or null
		 * \return user or null if not found
		 */
		public function getUser($id, $login);
	}

	require_once("db/${db_config['driver']}Session.php");

	$className = ucfirst($db_config['driver']) . 'DBSession';
	$dbDriver = new $className($db_config);
?>