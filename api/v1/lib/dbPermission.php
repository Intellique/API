<?php
	/**
	 * \brief specific interface for permission
	 */
	interface DB_Permission extends DB {
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
	}
?>
