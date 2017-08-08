<?php
	/**
	 * \brief Specific interface for user
	 */
	interface DB_User extends DB {
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
		 * \brief Delete a user
		 * \param $id : user id
		 * \return \b TRUE on deletion success, \b FALSE when no user was deleted, \b NULL on query execution failure
		 */
		public function deleteUser($id);

		/**
		 * \brief Get user by id or login
		 * \param $id : User id or null
		 * \param $login : User login or null
		 * \param $completeInfo : if \b TRUE, get all user's informations. Else, get only login, full name and email.
		 * \return <b>User informations</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getUser($id, $login, $completeInfo);

		/**
		 * \brief Get user by id or login
		 * \param $id : User id or null
		 * \param $rowLock : put a lock on media with id $id
		 * \return <b>User informations</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getUserById($id, $rowLock = DB::DB_ROW_LOCK_NONE);

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
?>
