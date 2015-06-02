<?php
	require_once('conf.php');

	/**
	 * \brief common interface.
	 */
	interface DB {
		/**
		 * \brief check if a connection to database exists.
		 * \return \b TRUE on success, \b FALSE on failure
		 */
		public function isConnected();
	}
?>