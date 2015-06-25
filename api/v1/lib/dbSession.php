<?php
	require_once('db.php');

	/**
	 * \brief specific interface for user, session, job, jobtype
	 */
	interface DB_Session extends DB {
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
		 * \return <b>New user ID</b> or \b NULL on query execution failure
		 */
		public function createUser(&$user);

		/**
		 * \brief delete a job
		 * \param $id : Job id
		 * \return \b TRUE on deletion success, \b FALSE when no job was deleted, \b NULL on query execution failure
		 */
		public function deleteJob($id);

		/**
		 * \brief delete a user
		 * \param $id : User id
		 * \return \b TRUE on deletion success, \b FALSE when no user was deleted, \b NULL on query execution failure
		 */
		public function deleteUser($id);

		/**
		 * \brief get job by id
		 * \param $id : Job id
		 * \return <b>Job information</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getJob($id);

		/**
		 * \brief get jobs ID list
		 *
		 * <b>Optional parameters</b>
		 * \li \c $params['order_by'] (enum) order by column
		 * \li \c $params['order_asc'] (boolean) ascending/descending order
		 * \li \c $params['limit'] (integer) maximum number of rows to return
		 * \li \c $params['offset'] (integer) number of rows to skip before starting to return rows
		 * \return <b>Jobs ID list</b> and <b>total rows</b>
		 */
		public function getJobs(&$params);

		/**
		 * \brief get jobtype name list
		 * \return <b>Jobtype name list</b>, <b>empty array</b> if not found, \b NULL on query execution failure
		 */
		public function getJobType();

		/**
		 * \brief get poolgroup by id
		 * \param $id : Poolgroup id
		 * \return <b>Poolgroup information</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getPoolgroup($id);

		/**
		 * \brief get user by id or login
		 * \param $id : User id or null
		 * \param $login : User login or null
		 * \return <b>User informations</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getUser($id, $login);

		/**
		 * \brief get users ID list
		 *
		 * <b>Optional parameters</b>
		 * \li \c $params['order_by'] (enum) order by column
		 * \li \c $params['order_asc'] (boolean) ascending/descending order
		 * \li \c $params['limit'] (integer) maximum number of rows to return
		 * \li \c $params['offset'] (integer) number of rows to skip before starting to return rows
		 * \return <b>Users ID list</b> and <b>total rows</b>
		 */
		public function getUsers(&$params);

		/**
		 * \brief update a job
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
		 * \brief update a user
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
