<?php
	require_once('conf.php');

	/**
	 * \brief common interface
	 */
	interface DB {
		/**
		 * \brief check archive permission
		 * \param $archive_id : archive ID
		 * \param $user_id : user ID
		 * \return \b null on query or a boolean value corresponding to the permission
		 */
		public function checkArchivePermission($archive_id, $user_id);

		/**
		 * \brief check pool permission
		 * \param $pool_id : pool ID
		 * \param $user_id : user ID
		 * \return \b null on query or a boolean value corresponding to the permission
		 */
		public function checkPoolPermission($pool_id, $user_id);

		/**
		 * \brief check if a connection to database exists
		 * \return \b TRUE on success, \b FALSE on failure
		 */
		public function isConnected();
	}
?>