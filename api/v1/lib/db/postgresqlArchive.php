<?php
	require_once("postgresql.php");

	class PostgresqlDBArchive extends PostgresqlDB implements DB_Archive {
		public function checkArchivePermission($archive_id, $user_id) {
			if (!$this->prepareQuery("check_archive_permission", "SELECT COUNT(*) > 0 AS granted FROM archivevolume av INNER JOIN media m ON av.sequence = 0 AND av.media = m.id WHERE av.archive = $1 AND m.pool IN ( SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $2 AND u.poolgroup = ppg.poolgroup)"))
				return null;

			$result = pg_execute("check_archive_permission", array($archive_id, $user_id));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			$row[0] = $row[0] == 't' ? true : false;
			return $row[0];
		}

		public function getArchive($id) {
			if (!$this->prepareQuery("select_archive_by_id", "SELECT id, uuid, name, creator, owner, deleted FROM archive WHERE id = $1 AND NOT deleted"))
				return null;

			$result = pg_execute("select_archive_by_id", array($archive_id));
			if ($result === false)
				return null;

			$row = pg_fetch_assoc($result);

			$row['id'] = intval($row['id']);
			$row['creator'] = intval($row['creator']);
			$row['owner'] = intval($row['owner']);
			$row['deleted'] = $row['deleted'] == 't' ? true : false;

			return $row;
		}
	}
?>
