<?php
	require_once('conf.php');
	require_once('dbArchive.php');
	require_once('dbJob.php');
	require_once('dbLibrary.php');
	require_once('dbMedia.php');
	require_once('dbMetadata.php');
	require_once('dbPermission.php');
	require_once('dbPool.php');
	require_once('dbSession.php');
	require_once('dbUser.php');

	/**
	 * \brief Common interface
	 */
	interface DB extends DB_Archive, DB_Job, DB_Library, DB_Media, DB_Metadata, DB_Permission, DB_Pool, DB_Session, DB_User {
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

	require_once("db/${db_config['driver']}.php");
	$className = ucfirst($db_config['driver']) . 'DB';
	$dbDriver = new $className($db_config);
?>
