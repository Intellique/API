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

	/**
	 * \brief interface for iterate results
	 */
	interface DBResultIterator {
		/**
		 * \brief Returns true if the iteration has more elements. (In other words, returns true if next() would return an element rather than throwing an exception.)
		 * \return \b TRUE if the iteration has more elements
		 */
		public function hasNext();

		/**
		 * \brief Returns the next element in the iteration
		 * \return <b>the next element</b> in the iteration
		 */
		public function next();
	}

	/**
	 * \brief interface for result row
	 */
	interface DBRow {
		/**
		 * \brief get value from a specified column
		 * \return <b>the value from a specified column</b>
		 */
		public function getValue($column = 0);
	}
?>