<?php
	require_once('conf.php');

	/**
	 * \brief Common interface
	 */
	interface DB {

		const DB_LOG_EMERGENCY = 0x1;
		const DB_LOG_ALERT = 0x2;
		const DB_LOG_CRITICAL = 0x3;
		const DB_LOG_ERROR = 0x4;
		const DB_LOG_WARNING = 0x5;
		const DB_LOG_NOTICE = 0x6;
		const DB_LOG_INFO = 0x7;
		const DB_LOG_DEBUG = 0x8;


		const DB_ROW_LOCK_NONE = 0x0;
		const DB_ROW_LOCK_SHARE = 0x1;
		const DB_ROW_LOCK_UPDATE = 0x2;

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

		/**
		 * \brief Logs user's actions
		 * \return \b TRUE on success
		 */
		public function writeLog($level, $message, $login = null);
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
