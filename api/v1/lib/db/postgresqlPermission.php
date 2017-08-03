<?php
	trait PostgresqlDBPermission {
		public function checkArchivePermission($archive_id, $user_id) {
			if (!$this->prepareQuery("check_archive_permission", "SELECT NOT EXISTS(SELECT * FROM archive WHERE id = $1) OR EXISTS(SELECT * FROM archive WHERE id = $1 AND (creator = $2 OR owner = $2)) OR EXISTS(SELECT * FROM archivevolume av INNER JOIN media m ON av.archive = $1 AND av.sequence = 0 AND av.media = m.id WHERE m.pool IN (SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $2 AND u.poolgroup = ppg.poolgroup))"))
				return null;

			$result = pg_execute("check_archive_permission", array($archive_id, $user_id));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			$row[0] = $row[0] == 't' ? true : false;
			return $row[0];
		}

		public function checkArchiveFilePermission($archivefile_id, $user_id) {
			if (!$this->prepareQuery("check_archivefile_permission", "SELECT COUNT(*) > 0 AS granted FROM archive WHERE id IN (SELECT av.archive FROM archivevolume av INNER JOIN media m ON av.media = m.id WHERE m.pool IN (SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $2 AND u.poolgroup = ppg.poolgroup) AND av.id IN (SELECT archivevolume FROM archivefiletoarchivevolume WHERE archivefile = $1))"))
				return null;

			$result = pg_execute("check_archivefile_permission", array($archivefile_id, $user_id));
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
	}
?>
