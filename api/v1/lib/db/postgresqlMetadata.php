<?php
	require_once("dbMetadata.php");

	trait PostgresqlDBMetadata {
		public function createMetadata($id, $key, $value, $type, $userId) {
			if (!$this->prepareQuery("insert_metadata", "INSERT INTO metadata (id, type, key, value, login) VALUES ($1, $2, $3, $4, $5)"))
				return null;

			$value = json_encode($value, JSON_FORCE_OBJECT);

			$result = pg_execute($this->connect, "insert_metadata", array($id, $type, $key, $value, $userId));

			if ($result === false)
				return null;

			return true;
		}

		public function deleteMetadata($id, &$key, $type, $userId) {
			if (!$this->prepareQuery('delete_metadata_by_key', "WITH up AS (UPDATE metadata SET login = $4 WHERE id = $1 AND type = $2 AND key = $3) DELETE FROM metadata WHERE id = $1 AND type = $2 AND key = $3"))
				return null;

			foreach ($key as $value) {
				$result = pg_execute($this->connect, 'delete_metadata_by_key', array($id, $type, $value, $userId));
				if ($result === false)
					return null;
				if (pg_affected_rows($result) == 0)
					return false;
			}
			return true;
		}

		public function deleteMetadatas($id, $type, $userId) {
			if (!$this->prepareQuery('delete_metadatas', "WITH up AS (UPDATE metadata SET login = $3 WHERE id = $1 AND type = $2) DELETE FROM metadata WHERE id = $1 AND type = $2"))
				return null;

			$result = pg_execute($this->connect, 'delete_metadatas', array($id, $type, $userId));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function getJobMetadatas($id, $key) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_job_metadatas_by_id', "SELECT metadata FROM job WHERE id = $1"))
				return null;

			$result = pg_execute('select_job_metadatas_by_id', array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$meta = array();

			while ($row = pg_fetch_array($result))
				$meta[] = json_decode($row[0], true);

			if (isset($key)) {
				foreach($meta as $list) {
					if (array_key_exists($key, $list))
						return $list[$key];
					else
						return false;
				}
			}

			return $meta;
		}

		public function getMetadata($id, $key, $type) {
			if (!isset($id) || !isset($key) || !isset($type) || !is_numeric($id))
				return array('error' => true, 'found' => false, 'value' => null);

			if (!$this->prepareQuery('select_metadata_by_key', "SELECT value FROM metadata WHERE id = $1 AND key = $2 AND type = $3 LIMIT 1"))
				return array('error' => true, 'found' => false, 'value' => null);

			$result = pg_execute($this->connect, 'select_metadata_by_key', array($id, $key, $type));

			if ($result === false)
				return array('error' => true, 'found' => false, 'value' => null);

			if (pg_num_rows($result) == 0)
				return array('error' => false, 'found' => false, 'value' => null);

			$row = pg_fetch_assoc($result);

			return array('error' => false, 'found' => true, 'value' => json_decode($row['value'], true));
		}

		public function getMetadatas($id, $type) {
			if (!isset($id) || !isset($type) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_metadatas_by_id', "SELECT key, value FROM metadata WHERE id = $1 AND type = $2"))
				return null;

			$result = pg_execute($this->connect, 'select_metadatas_by_id', array($id, $type));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$meta = array();

			while ($row = pg_fetch_assoc($result))
				$meta[$row['key']] = json_decode($row['value'], true);

			return $meta;
		}

		public function getPoolMetadatas($id, $key) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_pool_metadatas_by_id', "SELECT metadata FROM pool WHERE id = $1"))
				return null;

			$result = pg_execute('select_pool_metadatas_by_id', array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$meta = array();

			while ($row = pg_fetch_array($result))
				$meta[] = json_decode($row[0], true);

			if (isset($key)) {
				foreach($meta as $list) {
					if (array_key_exists($key, $list))
						return $list[$key];
					else
						return false;
				}
			}

			return $meta;
		}

		public function getUserMetadatas($id, $key) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_user_metadatas_by_id', "SELECT meta FROM users WHERE id = $1"))
				return null;

			$result = pg_execute('select_user_metadatas_by_id', array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$meta = array();

			while ($row = pg_fetch_array($result))
				$meta[] = json_decode($row['meta'], true);

			if (isset($key)) {
				foreach($meta as $list) {
					if (array_key_exists($key, $list))
						return $list[$key];
					else
						return false;
				}
			}

			return $meta;
		}

		public function updateMetadata($id, $key, $value, $type, $userId) {
			if (!$this->prepareQuery("update_metadata", "UPDATE metadata SET value = $4, login = $5 WHERE id = $1 AND type = $2 AND key = $3"))
				return null;

			$value = json_encode($value, JSON_FORCE_OBJECT);

			$result = pg_execute($this->connect, "update_metadata", array($id, $type, $key, $value, $userId));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>
