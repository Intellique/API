<?php
	require_once('conf.php');

	/**
	 * \brief common interface.
	 */
	interface DB {
		/**
		 * \brief opens a connection to a database.
		 * \return connection resource on success, FALSE on failure.
		 */
		public function isConnected();
	}
?>