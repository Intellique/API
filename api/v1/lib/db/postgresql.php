<?php
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

		public function cancel_transaction() {
			$status = pg_transaction_status($this->connection);
			switch ($status) {
				case PGSQL_TRANSACTION_INTRANS:
				case PGSQL_TRANSACTION_INERROR:
					break;

				default:
					return false;
			}

			$result = pg_execute($this->connection, "ROLLBACK", array());
			return $result !== false;
		}

		public function checkArchivePermission($archive_id, $user_id) {
			if (!$this->prepareQuery("check_archive_permission", "SELECT COUNT(*) > 0 AS granted FROM archive WHERE creator = $2 OR owner = $2 OR id IN (SELECT av.archive FROM archivevolume av INNER JOIN media m ON av.archive = $1 AND av.sequence = 0 AND av.media = m.id WHERE m.pool IN (SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $2 AND u.poolgroup = ppg.poolgroup))"))
				return null;

			$result = pg_execute("check_archive_permission", array($archive_id, $user_id));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			$row[0] = $row[0] == 't' ? true : false;
			return $row[0];
		}

		public function checkPoolPermission($pool_id, $user_id) {
			if (!$this->prepareQuery("check_pool_permission", "SELECT COUNT(*) > 0 AS granted FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $2 AND u.poolgroup = ppg.poolgroup AND ppg.pool = $1"))
				return null;

			$result = pg_execute("check_pool_permission", array($pool_id, $user_id));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			$row[0] = $row[0] == 't' ? true : false;
			return $row[0];
		}

		public function finish_transaction() {
			$status = pg_transaction_status($this->connection);
			if ($status != PGSQL_TRANSACTION_INTRANS)
				return false;

			$result = pg_execute($this->connection, "COMMIT", array());
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
		 * \brief casts specified string into integer
		 * \param $string : string to be casted
		 * \return \b integer or \b null if string doesn't represent a number
		 */
		public static function getInteger($string) {
			if (ctype_digit($string))
				return intval($string);
			else
				return null;
		}

		/**
		 * \brief convert hstore string into hashtable
		 * \param $metadatas : metadata hstore string
		 * \return \b metadata hashtable
		 */
		protected static function fromHstore($metadatas) {
			$metas = array();
			$list_metas = split(', ', $metadatas);
			foreach ($list_metas as $value) {
				list($key, $val) = split('=>', $value);
				$key = substr($key, 1, strlen($key) - 2);
				$val = substr($val, 1, strlen($val) - 2);
				$metas[$key] = json_decode($val, true);
			}
			return $metas;
		}

		public function isConnected() {
			return $this->connect != false;
		}

		/**
		 * \brief convert hashtable into hstore string
		 * \param $metadatas : metadata hashtable
		 * \return \b metadata hstore string
		 */
		protected static function toHstore(&$metadatas) {
			$metas = array();
			foreach ($metadatas as $key => $value)
				$metas[] = $key . '=>' . json_encode($value);
			$meta = join(',', $metas);
			return $meta;
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

		public function start_transaction() {
			$query = pg_execute($this->connection, "BEGIN", array());
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

		public function __construct($result, $functionsArray) {
			$this->result = $result;
			$this->functionsArray = $functionsArray;
			$this->currentRow = false;
			$this->nbResultFetched = 0;
		}

		public function hasNext() {
			$this->currentRow = pg_fetch_array($this->result);
			return $this->currentRow === false ? false : true;
		}

		public function next() {
			if ($this->currentRow !== false)
				return new PostgresqlDBRow($this->result, $this->currentRow, $this->functionsArray, $this->nbResultFetched++);
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

		public function __construct($result, &$currentRow, &$functionsArray, $iResult) {
			$this->result = $result;
			$this->functionsArray = $functionsArray;
			$this->currentRow = $currentRow;
			$this->iResult = $iResult;
		}

		public function getValue($column = 0) {
			if ($column < count($this->currentRow)) {
				if (pg_field_is_null($this->result, $this->iResult, $column))
					return null;

				$fun = $this->functionsArray[$column];
				return PostgresqlDB::$fun($this->currentRow[$column]);
			}
			return null;
		}
	}
?>
