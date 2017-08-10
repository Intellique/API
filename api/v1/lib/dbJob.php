<?php
	/**
	 * \brief Specific interface for job
	 */
	interface DB_Job {
		/**
		 * \brief Create a archival task
		 * \param $job : hash table
 		 * \li \c pool id (integer) : pool id
		 * \li \c files (string array) : files to be archived
		 * \li \c name (string) : archive name
		 * \li \c nextstart [optional] (string) : archival task nextstart date, <em>default value : now</em>
		 * \li \c metadata [optional] (object) : archive metadata, <em>default value : empty object</em>
		 * \return <b>New job id</b> or \b NULL on query execution failure
		 *
		 * \brief Create a restore task
		 * \param $job : hash table
		 * \li \c archive id (integer) : archive id
		 * \li \c files (string array) : files to be restored
		 * \li \c name [optional] (string) : restore task name, <em>default value : archive name</em>
		 * \li \c nextstart [optional] (string) : restore task nextstart date, <em>default value : now</em>
		 * \li \c destination [optional] (string) : restoration destination path, <em>default value : original path</em>
		 * \return <b>New job id</b> or \b NULL on query execution failure
		 */
		public function createJob(&$job);

		/**
		 * \brief Delete a job
		 * \param $id : job id
		 * \return \b TRUE on deletion success, \b FALSE when no job was deleted, \b NULL on query execution failure
		 */
		public function deleteJob($id);

		/**
		 * \brief Get host id by name
		 * \param $name : host name
		 * \return <b>Host id</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getHost($name);

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
		 * \brief Get job type id by name
		 * \param $name : job type name
		 * \return <b>Job type id</b>, <b>empty array</b> if not found, \b NULL on query execution failure
		 */
		public function getJobTypeId($name);

		/**
		 * \brief Get job types list
		 * \return <b>Job types list</b>, \b FALSE if no result, \b NULL on query execution failure
		 */
		public function getJobTypes();

		/**
		 * \brief Get selected file or create it if not exists
		 * \param $path : selected file path
		 * \return <b>Selected file id</b>, \b NULL on query execution failure
		 */
		public function getSelectedFile($path);

		/**
		 * \brief Insert into restoreto table a job id with a destination path
		 * \param $jobId : job id
		 * \param $path : destination path
		 * \return \b TRUE on insertion success, \b NULL on query execution failure
		 */
		public function insertIntoRestoreTo($jobId, $path);

		/**
		 * \brief Link job table to selectedfile table
		 * \param $jobId : job id
		 * \param $selectedfileId : selectedfile id
		 * \return \b TRUE on insertion success, \b NULL on query execution failure
		 */
		public function linkJobToSelectedfile($jobId, $selectedfileId);

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
	}
?>
