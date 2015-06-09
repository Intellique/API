<?php
	require_once('db.php');

	/**
	 * \brief specific interface for user, session, job
	 */
	interface DB_Session extends DB {
		/**
		 * \brief create a user
		 * \param $user : PHP object
		 * \li \c login (string) user login
		 * \li \c password (string) user password
		 * \li \c fullname (string) user fullname
		 * \li \c email (string) user email
		 * \li \c homedirectory (string) user homedirectory
		 * \li \c isadmin (boolean) administration rights
		 * \li \c canarchive (boolean) archive rights
		 * \li \c canrestore (boolean) restoration rights
		 * \li \c meta (object) user metadatas
		 * \li \c poolgroup (integer) user poolgroup
		 * \li \c disabled (boolean) login rights
		 * \return <b>New user ID</b> or \b NULL on query execution failure
		 */
		public function createUser(&$user);

		/**
		 * \brief delete a user
		 * \param $id : User id
		 * \return \b TRUE on deletion success, \b FALSE deletion failure, \b NULL on query execution failure
		 */
		public function deleteUser($id);

		/**
		 * \brief get poolgroup by id
		 * \param $id : User id
		 * \return <b>Poolgroup informations</b> or \b FALSE if not found
		 */
		public function getPoolgroup($id);

		/**
		 * \brief get user by id or login
		 * \param $id : User id or null
		 * \param $login : User login or null
		 * \return <b>User informations</b> or \b FALSE if not found
		 */
		public function getUser($id, $login);

		/**
		 * \brief get users ID list
		 * \return <b>Users ID list</b> or \b FALSE if not found
		 */
		public function getUsers();
	}

	require_once("db/${db_config['driver']}Session.php");

	$className = ucfirst($db_config['driver']) . 'DBSession';
	$dbDriver = new $className($db_config);
?>
