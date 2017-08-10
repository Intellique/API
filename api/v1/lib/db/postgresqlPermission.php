<?php
	require_once("dbPermission.php");

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
			if (!$this->prepareQuery("check_archivefile_permission", "SELECT NOT EXISTS(SELECT * FROM archivefile WHERE id = $1) OR EXISTS(SELECT * FROM milestones_files WHERE archivefile = $1 AND (archive IN (SELECT id FROM archive WHERE NOT deleted AND (creator = $2 OR owner = $2)) OR pool IN (SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $2 AND u.poolgroup = ppg.poolgroup)))"))
				return null;

			$result = pg_execute("check_archivefile_permission", array($archivefile_id, $user_id));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			$row[0] = $row[0] == 't' ? true : false;
			return $row[0];
		}

		public function checkPoolPermission($pool_id, $user_id) {
			if (!$this->prepareQuery("check_pool_permission", "SELECT NOT EXISTS(SELECT * FROM pool WHERE id = $1) OR EXISTS(SELECT * FROM pooltopoolgroup WHERE pool = $1 AND poolgroup = (SELECT poolgroup FROM users WHERE id = $2))"))
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
