<?php
	require_once('conf.php');

	/**
	 * \brief Common interface
	 */
	interface DB {
		/**
		 * \brief Cancel current transaction
		 * \return \b TRUE on success
		 */
		public function cancelTransaction();

		/**
		 * \brief Finish current transaction by commiting it
		 * \return \b TRUE on success
		 */
		public function finishTransaction();

		/**
		 * \brief Check if a connection to database exists
		 * \return \b TRUE on success, \b FALSE on failure
		 */
		public function isConnected();

		/**
		 * \brief Start new transaction
		 * \return \b TRUE on success
		 */
		public function startTransaction();
	}

	/**
	 * \brief Interface for iterate results
	 */
	interface DBResultIterator {
		/**
		 * \brief Returns true if the iteration has more elements. (In other words, returns true if next() would return an element rather than throwing an exception.)
		 * \return \b TRUE if the iteration has more elements
		 */
		public function hasNext();

		/**
		 * \brief Returns the next element in the iteration
		 * \return <b>The next element</b> in the iteration
		 */
		public function next();
	}

	/**
	 * \brief Interface for result row
	 */
	interface DBRow {
		/**
		 * \brief Get value from a specified column
		 * \return <b>The value from a specified column</b>
		 */
		public function getValue($column);
	}
?>
