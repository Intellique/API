<?php
	require_once('conf.php');

	/**
	 * \brief common interface
	 */
	interface DB {
		/**
		 * \brief cancel current transaction
		 * \return \b TRUE on success
		 */
		public function cancelTransaction();

		/**
		 * \brief finish current transaction by commiting it
		 * \return \b TRUE on success
		 */
		public function finishTransaction();

		/**
		 * \brief check if a connection to database exists
		 * \return \b TRUE on success, \b FALSE on failure
		 */
		public function isConnected();

		/**
		 * \brief start new transaction
		 * \return \b TRUE on success
		 */
		public function startTransaction();
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
		public function getValue($column);
	}
?>
