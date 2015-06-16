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

		public function isConnected() {
			return $this->connect != false;
		}

		/**
		 * \brief casts specified string into integer
		 * \param $string : string to be casted
		 * \return \b integer or \b null if string doesn't represent a number
		 */
		protected static function getInteger($string) {
			if (is_int($string))
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
	}
?>