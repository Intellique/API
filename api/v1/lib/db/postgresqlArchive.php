<?php
	require_once("postgresql.php");

	class PostgresqlDBArchive extends PostgresqlDB implements DB_Archive {
		public function getArchive($id) {
			if (!$this->prepareQuery("select_archive_by_id", "SELECT id, uuid, name, creator, owner, canappend, deleted FROM archive WHERE id = $1 AND NOT deleted"))
				return null;

			$result = pg_execute("select_archive_by_id", array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);

			$row['id'] = intval($row['id']);
			$row['creator'] = intval($row['creator']);
			$row['owner'] = intval($row['owner']);
			$row['canappend'] = $row['canappend'] == 't' ? true : false;
			$row['deleted'] = $row['deleted'] == 't' ? true : false;

			return $row;
		}

		public function getArchives($user_id, &$params) {
			$query_common = " FROM archive WHERE creator = $1 OR owner = $1 OR id IN (SELECT av.archive FROM archivevolume av INNER JOIN media m ON av.sequence = 0 AND av.media = m.id WHERE m.pool IN (SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $1 AND u.poolgroup = ppg.poolgroup))";
			$query_params = array($user_id);

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_archives_by_user";

				if (!$this->prepareQuery($query_name, $query))
					return array(
						'query' => $query,
						'query_name' => $query_name,
						'query_prepared' => false,
						'query_executed' => false,
						'rows' => array(),
						'total_rows' => 0
					);

				$result = pg_execute($this->connect, $query_name, $query_params);
				if ($result === false)
					return array(
						'query' => $query,
						'query_name' => $query_name,
						'query_prepared' => true,
						'query_executed' => false,
						'rows' => array(),
						'total_rows' => 0
					);

				$row = pg_fetch_array($result);
				$total_rows = intval($row[0]);
			}


			$query = "SELECT id" . $query_common;

			if (isset($params['order_by'])) {
				$query .= ' ORDER BY ' . $params['order_by'];

				if (isset($params['order_asc']) && $params['order_asc'] === false)
					$query .= ' DESC';
			}
			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}
			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_archives_by_user_" . md5($query);
			if (!$this->prepareQuery($query_name, $query))
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => $total_rows
				);

			$result = pg_execute($this->connect, $query_name, $query_params);
			if ($result === false)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => $total_rows
				);

			$rows = array();
			while ($row = pg_fetch_array($result))
				$rows[] = intval($row[0]);

			if ($total_rows == 0)
				$total_rows = count($rows);

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'rows' => $rows,
				'total_rows' => $total_rows
			);
		}

		public function updateArchive(&$archive) {
			if (!$this->prepareQuery("update_archive", "UPDATE archive SET name = $1, owner = $2, canappend = $3, deleted = $4 WHERE id = $5"))
				return null;

			$canappend = $archive['canappend'] ? "TRUE" : "FALSE";
			$deleted = $archive['deleted'] ? "TRUE" : "FALSE";

			$result = pg_execute("update_archive", array($archive['name'], $archive['owner'], $canappend, $deleted, $archive['id']));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>
