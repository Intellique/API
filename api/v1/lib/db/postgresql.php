<?php
	require_once("dateTime.php");

	/**
	 * \brief postgresql's implementation
	 */
	class PostgresqlDB implements DB {
		/**
		 * \brief connection resource required by pg_*
		 */
		protected $connect;
		/**
		 * \brief hash table stores prepared queries to avoid re-preparing queries that have already been prepared
		 *
		 * \verbatim Avoids preparing two queries with the same name \endverbatim
		 */
		private $preparedQueries;

		public function __construct($db_config) {
			$connection_string = "";

			if (isset($db_config['db']))
				$connection_string .= "dbname=" . $db_config['db'];
			if (isset($db_config['host']))
				$connection_string .= " host=" . $db_config['host'];
			if (isset($db_config['user']))
				$connection_string .= " user=" . $db_config['user'];
			if (isset($db_config['password']))
				$connection_string .= " password=" . $db_config['password'];
			if (isset($db_config['port']))
				$connection_string .= " port=" . $db_config['port'];

			$this->connect = pg_connect($connection_string);

			pg_query($this->connect, "SET timezone = 'GMT'");

			$this->preparedQueries = array();
		}

		public function cancelTransaction() {
			$status = pg_transaction_status($this->connect);
			switch ($status) {
				case PGSQL_TRANSACTION_INTRANS:
				case PGSQL_TRANSACTION_INERROR:
					break;

				default:
					return false;
			}

			$result = pg_query($this->connect, "ROLLBACK");
			return $result !== false;
		}

		public function finishTransaction() {
			$status = pg_transaction_status($this->connect);
			if ($status != PGSQL_TRANSACTION_INTRANS)
				return false;

			$result = pg_query($this->connect, "COMMIT");
			return $result !== false;
		}

		/**
		 * \brief return specified string
		 * \param $string : string
		 * \return \b specified string
		 */
		public static function get($string) {
			return $string;
		}

		/**
		 * \brief casts specified string into boolean
		 * \param $string : string to be casted
		 * \return \b boolean or \b null if string doesn't represent a boolean
		 */
		public static function getBoolean($string) {
			if ($string == '')
				return null;
			return filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		}

		/**
		 * \brief casts specified string into date
		 * \param $string : string to be casted
		 * \return \b date or \b null if string doesn't represent a date
		 *
		 * \note \ref Date "Date time formats supported"
		 */
		public static function getDate($string) {
			if ($string == '')
				return null;
			return dateTimeParse($string);
		}

		/**
		 * \brief casts specified string into integer
		 * \param $string : string to be casted
		 * \return \b integer or \b null if string doesn't represent a number
		 */
		public static function getInteger($string) {
			if (is_numeric($string))
				return intval($string);
			else
				return null;
		}

		public function isConnected() {
			return $this->connect != false;
		}

		/**
		 * \brief prepares an SQL query
		 * \param $stmtname : SQL query name
		 * \param $query : SQL query
		 * \return \b TRUE on success, \b FALSE on failure
		 */
		protected function prepareQuery($stmtname, $query) {
			if (array_key_exists($stmtname, $this->preparedQueries))
				return true;

			$result = pg_prepare($this->connect, $stmtname, $query);

			if ($result === false)
				return false;

			$this->preparedQueries[$stmtname] = $query;
			return true;
		}

		public function startTransaction() {
			$query = pg_query($this->connect, "BEGIN");
			return $query !== false;
		}
	}

	/**
	 * \brief postgresqlResultIterator's implementation
	 */
	class PostgresqlDBResultIterator implements DBResultIterator {
		/**
		 * \brief iterate over this result
		 */
		private $result;

		/**
		 * \brief boolean value
		 */
		private $fetchAssoc;

		/**
		 * \brief functions array to convert type
		 */
		private $functionsArray;

		/**
		 * \brief current result
		 */
		private $currentRow;

		/**
		 * \brief fetched result number
		 */
		private $nbResultFetched;

		public function __construct($result, $functionsArray, $fetchAssoc) {
			$this->result = $result;
			$this->functionsArray = $functionsArray;
			$this->fetchAssoc = $fetchAssoc;
			$this->currentRow = false;
			$this->nbResultFetched = 0;
		}

		public function hasNext() {
			if ($this->fetchAssoc)
				$this->currentRow = pg_fetch_assoc($this->result);
			else
				$this->currentRow = pg_fetch_array($this->result);
			return $this->currentRow === false ? false : true;
		}

		public function next() {
			if ($this->currentRow !== false)
				return new PostgresqlDBRow($this->result, $this->currentRow, $this->fetchAssoc, $this->functionsArray, $this->nbResultFetched++);
			return null;
		}
	}

	/**
	 * \brief postgresqlRow's implementation
	 */
	class PostgresqlDBRow implements DBRow {
		/**
		 * \brief iterate over this result
		 */
		private $result;

		/**
		 * \brief boolean value
		 */
		private $resultAssoc;

		/**
		 * \brief functions array to convert type
		 */
		private $functionsArray;

		/**
		 * \brief line number
		 */
		private $iResult;

		/**
		 * \brief current result
		 */
		private $currentRow;

		public function __construct($result, &$currentRow, $resultAssoc, &$functionsArray, $iResult) {
			$this->result = $result;
			$this->functionsArray = $functionsArray;
			$this->currentRow = $currentRow;
			$this->resultAssoc = $resultAssoc;
			$this->iResult = $iResult;
		}

		public function getValue($column) {
			if (($this->resultAssoc && array_key_exists($column, $this->currentRow)) || $column < count($this->currentRow)) {
				if (pg_field_is_null($this->result, $this->iResult, $column))
					return null;

				$fun = $this->functionsArray[$column];
				return PostgresqlDB::$fun($this->currentRow[$column]);
			}
			return null;
		}
	}
?>
