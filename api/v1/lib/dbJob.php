<?php
	/**
	 * \brief specific interface for job
	 */
	interface DB_Job {
		/**
		 * \brief create a job
		 * \param $job : hash table
		 * \li \c pool id (integer) : pool id
		 * \li \c files (JSON) : files to be archived
		 * \li \c name (string) : archive name
		 * \li \c type (string) : archival task type
		 * \li \c host (string) : machine name executes archival task
		 * \li \c login (integer) : user id
		 * \li \c metadata [optional] (JSON) : archive metadata
		 * \li \c date [optional] (JSON) : archival task nextstart date
		 * \return <b>New job id</b> or \b NULL on query execution failure
		 */
		public function createJob(&$job);

		/**
		 * \brief get host id by name
		 * \param $name : Host name
		 * \return <b>Host id</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getHost($name);

		/**
		 * \brief get job type id by name
		 * \param $name : Job type name
		 * \return <b>Job type id</b>, <b>empty array</b> if not found, \b NULL on query execution failure
		 */
		public function getJobTypeId($name);

		/**
		 * \brief get selected file or create it if not exists
		 * \param $path : selected file path
		 * \return <b>Selected file id</b>, \b NULL on query execution failure
		 */
		public function getSelectedFile($path);

		/**
		 * \brief link job table to selectedfile table
		 * \param $jobId : Job id
		 * \param $selectedfileId : Selectedfile id
		 * \return \b TRUE on insertion success, \b NULL on query execution failure
		 */
		public function linkJobToSelectedfile($jobId, $selectedfileId);
	}
?>