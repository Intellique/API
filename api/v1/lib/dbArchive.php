<?php
	require_once('db.php');

	/**
	 * \brief specific interface for archive object
	 */
	interface DB_Archive extends DB {
		/**
		 * \brief check user permission
		 * \param $archive_id : archive ID
		 * \param $user_id : user ID
		 * \return \b null on query or a boolean value corresponding to the permission
		 */
		public function checkArchivePermission($archive_id, $user_id);

		/**
		 * \brief Get an archive by its ID
		 * \param $id : archive's ID
		 * \return archive's information
		 * \note No permission check will be performed
		 */
		public function getArchive($id);

		public function getArchives($user_id, &$params);
	}

	require_once("db/${db_config['driver']}Archive.php");

	$className = ucfirst($db_config['driver']) . 'DBArchive';
	$dbDriver = new $className($db_config);
?>
