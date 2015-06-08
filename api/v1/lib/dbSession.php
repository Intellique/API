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
		 * \return New user ID or null if an error occured
		 */
		public function createUser(&$user);

		/**
		 * \brief get poolgroup by id
		 * \param $id : User id
		 * \return poolgroup informations or false if not found
		 */
		public function getPoolgroup($id);

		/**
		 * \brief get user by id or login
		 * \param $id : User id or null
		 * \param $login : User login or null
		 * \return user informations or false if not found
		 */
		public function getUser($id, $login);

		/**
		 * \brief get users ID list
		 * \return Users ID list or false if not found
		 */
		public function getUsers();
	}

	require_once("db/${db_config['driver']}Session.php");

	$className = ucfirst($db_config['driver']) . 'DBSession';
	$dbDriver = new $className($db_config);
?>
