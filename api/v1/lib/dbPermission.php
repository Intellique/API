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
		 */
		public function checkArchivePermission($archive_id, $user_id);

		/**
		 * \brief Check pool permission
		 * \param $pool_id : pool id
		 * \param $user_id : user id
		 * \return \b NULL on query or a boolean value corresponding to the permission
		 */
		public function checkPoolPermission($pool_id, $user_id);
	}
?>