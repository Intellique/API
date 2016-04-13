<?php
	require_once('db.php');
	require_once("dbJob.php");
	require_once("dbPermission.php");

	/**
	 * \brief Specific interface for user, session, job, jobtype
	 */
	interface DB_Session extends DB, DB_Job, DB_Permission {
		/**
		 * \brief create a user
		 * \param $user : PHP object
		 * \li \c login (string) : user login
		 * \li \c password (string) : user password
		 * \li \c fullname (string) : user fullname
		 * \li \c email (string) : user email
		 * \li \c homedirectory (string) : user homedirectory
		 * \li \c isadmin (boolean) : administration rights
		 * \li \c canarchive (boolean) : archive rights
		 * \li \c canrestore (boolean) : restoration rights
		 * \li \c meta (JSON) : user metadata
		 * \li \c poolgroup (integer) : user poolgroup
		 * \li \c disabled (boolean) : login rights
		 * \return <b>New user id</b> or \b NULL on query execution failure
		 */
		public function createUser(&$user);

		/**
		 * \brief Delete a job
		 * \param $id : job id
		 * \return \b TRUE on deletion success, \b FALSE when no job was deleted, \b NULL on query execution failure
		 */
		public function deleteJob($id);

		/**
		 * \brief Delete a user
		 * \param $id : user id
		 * \return \b TRUE on deletion success, \b FALSE when no user was deleted, \b NULL on query execution failure
		 */
		public function deleteUser($id);

		/**
		* \brief Get application's apikey by key
		* \param $apikey : apikey
		* \return <b>Id of Apikey</b>, \b FALSE if not found, \b NULL on query execution failure
		*/
		public function getApiKeyByKey($apikey);

		/**
		 * \brief Get job by id
		 * \param $id : job id
		 * \return <b>Job information</b>, \b FALSE if not found, \b NULL on query execution failure
		 * \note \ref Date "Date time formats supported"
		 */
		public function getJob($id);

		/**
		 * \brief Get jobs id list
		 *
		 * <b>Optional parameters</b>
		 * \li \c $params['order_by'] (enum) order by column
		 * \li \c $params['order_asc'] (boolean) ascending/descending order
		 * \li \c $params['limit'] (integer) maximum number of rows to return
		 * \li \c $params['offset'] (integer) number of rows to skip before starting to return rows
		 * \return <b>Jobs id list</b> and <b>total rows</b>
		 */
		public function getJobs(&$params);

		/**
		 * \brief Get job type name list
		 * \return <b>Job type name list</b>, <b>empty array</b> if not found, \b NULL on query execution failure
		 */
		public function getJobType();

		/**
		 * \brief Get poolgroup by id
		 * \param $id : Poolgroup id
		 * \return <b>Poolgroup information</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getPoolgroup($id);

		/**
		 * \brief Get user by id or login
		 * \param $id : User id or null
		 * \param $login : User login or null
		 * \return <b>User informations</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getUser($id, $login);

		/**
		 * \brief Get users id list
		 *
		 * <b>Optional parameters</b>
		 * \li \c $params['order_by'] (enum) order by column
		 * \li \c $params['order_asc'] (boolean) ascending/descending order
		 * \li \c $params['limit'] (integer) maximum number of rows to return
		 * \li \c $params['offset'] (integer) number of rows to skip before starting to return rows
		 * \return <b>Users id list</b> and <b>total rows</b>
		 */
		public function getUsers(&$params);

		/**
		 * \brief Update a job
		 * \param $job : PHP object
		 * \li \c id (integer) : job id
		 * \li \c name (string) : job name
		 * \li \c nextstart (timestamp(0) with time zone) : job nextstart
		 * \li \c interval (integer) : job interval
		 * \li \c repetition (integer) : job repetition
		 * \li \c status (string) : job status
		 * \li \c metadata (JSON) : job metadata
		 * \li \c options (JSON) : job options
		 * \return \b TRUE on update success, \b FALSE when no user was updated, \b NULL on query execution failure
		 */
		public function updateJob(&$job);

		/**
		 * \brief Update a user
		 * \param $user : PHP object
		 * \li \c id (integer) : user id
		 * \li \c login (string) : user login
		 * \li \c password (string) : user password
		 * \li \c fullname (string) : user fullname
		 * \li \c email (string) : user email
		 * \li \c homedirectory (string) : user homedirectory
		 * \li \c isadmin (boolean) : administration rights
		 * \li \c canarchive (boolean) : archive rights
		 * \li \c canrestore (boolean) : restoration rights
		 * \li \c meta (JSON) : user metadata
		 * \li \c poolgroup (integer) : user poolgroup
		 * \li \c disabled (boolean) : login rights
		 * \return \b TRUE on update success, \b FALSE when no user was updated, \b NULL on query execution failure
		 */
		public function updateUser(&$user);
	}

	require_once("db/${db_config['driver']}Session.php");

	$className = ucfirst($db_config['driver']) . 'DBSession';
	$dbDriver = new $className($db_config);
?>
