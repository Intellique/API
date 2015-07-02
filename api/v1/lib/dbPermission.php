<?php
	/**
	 * \brief specific interface for permission
	 */
	interface DB_Permission {
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
	}
?>