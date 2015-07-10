<?php
	trait PostgresqlDBMetadata {
		public function createMetadata($id, $key, $value, $type, $userId) {
			if (!$this->prepareQuery("insert_metadata", "INSERT INTO metadata (key, value, id, type) VALUES ($1, $2, $3, $4)"))
				return null;

			$value = json_encode($value, JSON_FORCE_OBJECT);

			$result = pg_execute($this->connect, "insert_metadata", array($key, $value, $id, $type));

			if ($result === false)
				return null;

			return true;
		}

		public function deleteMetadata($id, $key, $type, $userId) {
			if (!$this->prepareQuery('delete_metadata_by_key', "DELETE FROM metadata WHERE id = $1 AND key = $2 AND type = $3"))
				return null;

			$result = pg_execute($this->connect, 'delete_metadata_by_key', array($id, $key, $type));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function deleteMetadatas($id, $type, $userId) {
			if (!$this->prepareQuery('delete_metadatas', "DELETE FROM metadata WHERE id = $1 AND type = $2"))
				return null;

			$result = pg_execute($this->connect, 'delete_metadatas', array($id, $type));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function getMetadata($id, $key, $type) {
			if (!isset($id) || !isset($key) || !isset($type))
				return array('error' => true, 'founded' => false, 'value' => null);

			if (!$this->prepareQuery('select_metadata_by_key', "SELECT value FROM metadata WHERE id = $1 AND key = $2 AND type = $3 LIMIT 1"))
				return array('error' => true, 'founded' => false, 'value' => null);

			$result = pg_execute($this->connect, 'select_metadata_by_key', array($id, $key, $type));

			if ($result === false)
				return array('error' => true, 'founded' => false, 'value' => null);

			if (pg_num_rows($result) == 0)
				return array('error' => false, 'founded' => false, 'value' => null);

			$row = pg_fetch_assoc($result);

			return array('error' => false, 'founded' => true, 'value' => json_decode($row['value'], true));
		}

		public function getMetadatas($id, $type) {
			if (!isset($id) || !isset($type))
				return false;

			if (!$this->prepareQuery('select_metadatas_by_id', "SELECT key, value FROM metadata WHERE id = $1 AND type = $2"))
				return null;

			$result = pg_execute($this->connect, 'select_metadatas_by_id', array($id, $type));

			if ($result === false)
				return null;

			$meta = array();

			while ($row = pg_fetch_assoc($result))
				$meta[$row['key']] = json_decode($row['value'], true);

			return $meta;
		}

		public function updateMetadata($id, $key, $value, $type, $userId) {
			if (!$this->prepareQuery("update_metadata", "UPDATE metadata SET value = $1 WHERE id = $2 AND key = $3 AND type = $4"))
				return null;

			$value = json_encode($value, JSON_FORCE_OBJECT);

			$result = pg_execute($this->connect, "update_metadata", array($value, $id, $key, $type));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>