<?php
	/**
	 * \brief specific interface for permission
	 */
	interface DB_Permission {
		/**
		 * \brief Check archive permission
		 * \param $archive_id : archive id
		 * \param $user_id : user id
		 * \return \b NULL on query or a boolean value corresponding to the permission
		 * \note return \b true if $archive_id does not exist
		 */
		public function checkArchivePermission($archive_id, $user_id);

		/**
		 * \brief Check archive file permission
		 * \param $archive_id : archive file id
		 * \param $user_id : user id
		 * \return \b NULL on query or a boolean value corresponding to the permission
		 */
		public function checkArchiveFilePermission($archivefile_id, $user_id);

		/**
		 * \brief Check pool permission
		 * \param $pool_id : pool id
		 * \param $user_id : user id
		 * \return \b NULL on query or a boolean value corresponding to the permission
		 */
		public function checkPoolPermission($pool_id, $user_id);

		/**
		 * \brief Get user by id or login
		 * \param $id : User id or null
		 * \param $rowLock : put a lock on archive with id $id
		 * \return <b>User informations</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getUser(integer $id, $rowLock = DB::DB_ROW_LOCK_NONE);
	}
?>
