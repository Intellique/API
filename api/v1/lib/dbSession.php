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
	}
?>
