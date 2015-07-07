<?php
	/**
	 * \brief specific interface for job
	 */
	interface DB_Job {
		/**
		 * \brief create a archival task
		 * \param $job : hash table
 		 * \li \c pool id (integer) : pool id
		 * \li \c files (string array) : files to be archived
		 * \li \c name (string) : archive name
		 * \li \c metadata [optional] (object) : archive metadata, <em>default value : empty object</em>
		 * \li \c nextstart [optional] (string) : archival task nextstart date, <em>default value : now</em>
		 * \return <b>New job id</b> or \b NULL on query execution failure
		 *
		 * \brief create a restore task
		 * \param $job : hash table
		 * \li \c archive id (integer) : archive id
		 * \li \c name [optional] (string) : restore task name, <em>default value : archive name</em>
		 * \li \c nextstart [optional] (string) : restore task nextstart date, <em>default value : now</em>
		 * \param filesFound : archive files array
		 * \li \c files (string array) : files to be restored
		 * \param destination [optional] : restoration destination path
		 * \li \c destination [optional] (string) : restoration destination path, <em>default value : original path</em>
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
		 * \brief insert into restoreto table a job id with a destination path
		 * \param $jobId : job id
		 * \param $path : destination path
		 * \return \b TRUE on insertion success, \b NULL on query execution failure
		 */
		public function insertIntoRestoreTo($jobId, $path);

		/**
		 * \brief link job table to selectedfile table
		 * \param $jobId : Job id
		 * \param $selectedfileId : Selectedfile id
		 * \return \b TRUE on insertion success, \b NULL on query execution failure
		 */
		public function linkJobToSelectedfile($jobId, $selectedfileId);
	}
?>