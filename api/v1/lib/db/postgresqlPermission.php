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
			if (!$this->prepareQuery("check_pool_permission", "SELECT NOT EXISTS(SELECT * FROM pool WHERE id = $1) OR EXISTS(SELECT * FROM pooltopoolgroup WHERE pool = $1 AND poolgroup = (SELECT poolgroup FROM users WHERE id = $2))"))
				return null;

			$result = pg_execute("check_pool_permission", array($pool_id, $user_id));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			$row[0] = $row[0] == 't' ? true : false;
			return $row[0];
		}

		public function getUser(integer $id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			$query = 'SELECT id, login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled FROM users WHERE id = $1 LIMIT 1';

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_user_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($this->connect, 'select_user_by_login', array($login));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);

			$row['id'] = intval($row['id']);
			$row['isadmin'] = $row['isadmin'] == 't' ? true : false;
			$row['canarchive'] = $row['canarchive'] == 't' ? true : false;
			$row['canrestore'] = $row['canrestore'] == 't' ? true : false;
			$row['poolgroup'] = PostgresqlDB::getInteger($row['poolgroup']);
			$row['disabled'] = $row['disabled'] == 't' ? true : false;
			$row['meta'] = json_decode($row['meta']);

			return $row;
		}
	}
?>
