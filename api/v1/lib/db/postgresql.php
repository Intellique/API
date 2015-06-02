<?php
	/**
	 * \brief postgresql's implementation.
	 */
	class PostgresqlDB implements DB {
		/**
		 * \brief connection resource required by pg_*.
		 */
		protected $connect;
		/**
		 * \brief hash table which remembers prepared queries
		 *
		 * to avoid to prepare queries that have already been prepared.
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

			$this->preparedQueries = array();
		}

		public function isConnected() {
			return $this->connect != false;
		}

		/**
		 * \brief prepare an sql query.
		 * \param $stmtname : sql query's name
		 * \param $query : sql query
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