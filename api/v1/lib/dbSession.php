<?php
	/**
	 * \brief Specific interface for user, session, job, jobtype
	 */
	interface DB_Session {
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
		 * \brief Get poolgroups id list
		 *
		 * <b>Optional parameters</b>
		 * \li \c $params['order_by'] (enum) order by column
		 * \li \c $params['order_asc'] (boolean) ascending/descending order
		 * \li \c $params['limit'] (integer) maximum number of rows to return
		 * \li \c $params['offset'] (integer) number of rows to skip before starting to return rows
		 * \return <b>Poolgroups id list</b> and <b>total rows</b>
		 */
		public function getPoolgroups(&$params);

		/**
		 * \brief Update a poolgroup
		 * \param $poolgroup : PHP object
		 * \param $poolsToChange : PHP object
		 * \param $newPools : PHP object
		 * \return \b TRUE on update success, \b FALSE when no pool was found, \b NULL on query execution failure
		 */
		public function updatePoolgroup($poolgroup, $poolsToChange, $newPools);
	}
?>
