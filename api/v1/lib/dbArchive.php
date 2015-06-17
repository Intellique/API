<?php
	require_once('db.php');

	/**
	 * \brief specific interface for archive object
	 */
	interface DB_Archive extends DB {
		/**
		 * \brief Get an archive by its ID
		 * \param $id : archive's ID
		 * \return archive's information
		 * \note No permission check will be performed
		 */
		public function getArchive($id);

		/**
		 * \brief Get an archive ID list for user \i $user_id
		 * \param $user_id : a user
		 * \param &$params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', etc
		 */
		public function getArchives($user_id, &$params);

		/**
		 * \brief Update an archive
		 * \param &$archive : an archive
		 * \return \b null on failure, \b false if no archive were updated or \b true on success
		 */
		public function updateArchive(&$archive);
	}

	require_once("db/${db_config['driver']}Archive.php");

	$className = ucfirst($db_config['driver']) . 'DBArchive';
	$dbDriver = new $className($db_config);
?>
