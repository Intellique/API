<?php
	require_once("dateTime.php");
	require_once("dbSession.php");

	trait PostgresqlDBSession {
		public function getApiKeyByKey($apikey) {
			if (!isset($apikey))
				return false;

			$isPrepared = $this->prepareQuery('select_id_by_apikey', "SELECT id FROM application WHERE apikey = $1 LIMIT 1");
			if (!$isPrepared)
				return null;

			$result = pg_execute($this->connect, 'select_id_by_apikey', array($apikey));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);

			return intval($row['id']);
		}
	}
?>
