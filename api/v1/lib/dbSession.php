<?php
	require_once('db.php');
	require_once("dbJob.php");
	require_once("dbPermission.php");
	require_once("dbUser.php");

	/**
	 * \brief Specific interface for user, session, job, jobtype
	 */
	interface DB_Session extends DB, DB_Job, DB_Permission, DB_User {
		/**
		* \brief Get application's apikey by key
		* \param $apikey : apikey
		* \return <b>Id of Apikey</b>, \b FALSE if not found, \b NULL on query execution failure
		*/
		public function getApiKeyByKey($apikey);

		/**
		 * \brief Get pool(s) assigned to the poolgroup id
		 * \return <b>Pool id list</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getPooltopoolgroup($id);

		/**
		 * \brief Get poolgroup by id
		 * \param $id : Poolgroup id
		 * \return <b>Poolgroup information</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getPoolgroup($id);

		/**
		 * \brief Update a poolgroup
		 * \param $poolgroup : PHP object
		 * \param $poolsToChange : PHP object
		 * \param $newPools : PHP object
		 * \return \b TRUE on update success, \b FALSE when no pool was found, \b NULL on query execution failure
		 */
		public function updatePoolgroup($poolgroup, $poolsToChange, $newPools);
	}

	require_once("db/${db_config['driver']}Session.php");

	$className = ucfirst($db_config['driver']) . 'DBSession';
	$dbDriver = new $className($db_config);
?>
