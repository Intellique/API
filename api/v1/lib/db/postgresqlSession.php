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

		public function getPoolgroup($id) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_poolgroup_by_id', "SELECT id, uuid, name FROM poolgroup WHERE id = $1 LIMIT 1"))
				return null;

			$result = pg_execute($this->connect, 'select_poolgroup_by_id', array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);

			$row['id'] = intval($row['id']);

			return $row;
		}

		public function getPooltopoolgroup($id) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_pooltopoolgroup', "SELECT * FROM pooltopoolgroup WHERE poolgroup = $1"))
				return null;

			$result = pg_execute($this->connect, 'select_pooltopoolgroup', array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$rows = array();
			while ($row = pg_fetch_array($result))
				$rows[] = intval($row[0]);

			return $rows;
		}

		public function updatePoolgroup($poolgroup, $poolsToChange, $newPools) {
			if (!$this->prepareQuery("select_pool_for_update","SELECT * FROM pool WHERE id = $1"))
				return null;

			foreach($newPools as $value) {
				$result = pg_execute("select_pool_for_update", array($value));
					if ($result === false)
						return null;
					if (pg_num_rows($result) == 0)
						return false;
			}

			if (!$this->prepareQuery("update_poolgroup","UPDATE pooltopoolgroup SET pool = $1 WHERE poolgroup = $2 AND pool = $3"))
				return null;

			if (!$this->prepareQuery("insert_poolgroup","INSERT INTO pooltopoolgroup VALUES ($2, $1)"))
				return null;

			if (count($newPools) < count($poolsToChange)) {
				if (!$this->prepareQuery("delete_poolgroup","DELETE FROM pooltopoolgroup"))
					return null;

				$result = pg_execute("delete_poolgroup", array());
					if ($result === false)
						return null;

				foreach($newPools as $value) {
					$result = pg_execute("insert_poolgroup", array($poolgroup, $value));
					if ($result === false)
						return null;
				}
				return true;
			}

			foreach($newPools as $key => $value) {
				if (($key + 1) > count($poolsToChange)) {
					$result = pg_execute("insert_poolgroup", array($poolgroup, $value));
					if ($result === false)
						return null;
				} else {
					$result = pg_execute("update_poolgroup", array($value, $poolgroup, $poolsToChange[$key]));
					if ($result === false)
						return null;
				}
			}
			return true;
		}
	}
?>
