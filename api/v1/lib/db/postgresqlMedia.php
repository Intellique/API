<?php
	require_once("dbMedia.php");

	trait PostgresqlDBMedia {
		public function getMedia($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!is_numeric($id))
				return false;

			$query = "SELECT id, uuid, label, mediumserialnumber, name, status, firstused, usebefore, lastread, lastwrite, loadcount, readcount, writecount, operationcount, nbtotalblockread, nbtotalblockwrite, nbreaderror, nbwriteerror, nbfiles, blocksize, freeblock, totalblock, haspartition, append, type, writelock, archiveformat, mediaformat, pool FROM media WHERE id = $1";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_media_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$media = pg_fetch_assoc($result);

			$media['id'] = intval($media['id']);
			$media['uuid'] = $media['uuid'];
			$media['label'] = $media['label'];
			$media['mediumserialnumber'] = $media['mediumserialnumber'];
			$media['name'] = $media['name'];
			$media['type'] = $media['type'];
			$media['firstused'] = dateTimeParse($media['firstused']);
			$media['usebefore'] = dateTimeParse($media['usebefore']);
			$media['lastread'] = dateTimeParse($media['lastread']);
			$media['lastwrite'] = dateTimeParse($media['lastwrite']);
			$media['loadcount'] = intval($media['loadcount']);
			$media['readcount'] = intval($media['readcount']);
			$media['writecount'] = intval($media['writecount']);
			$media['operationcount'] = intval($media['operationcount']);
			$media['nbtotalblockread'] = intval($media['nbtotalblockread']);
			$media['nbtotalblockwrite'] = intval($media['nbtotalblockwrite']);
			$media['nbreaderror'] = intval($media['nbreaderror']);
			$media['nbwriteerror'] = intval($media['nbwriteerror']);
			$media['nbfiles'] = intval($media['nbfiles']);
			$media['blocksize'] = intval($media['blocksize']);
			$media['freeblock'] = intval($media['freeblock']);
			$media['totalblock'] = intval($media['totalblock']);
			$media['haspartition'] = $media['haspartition'] == 't' ? true : false;
			$media['append'] = $media['append'] == 't' ? true : false;
			$media['writelock'] = $media['writelock'] == 't' ? true : false;
			$media['archiveformat'] = $this->getArchiveFormat($media['archiveformat']);
			$media['mediaformat'] = $this->getMediaFormat($media['mediaformat']);
			$media['pool'] = $this->getPool($media['pool'], $rowLock);

			return $media;
		}

		public function getMediaFormat($id) {
			if (!is_numeric($id))
				return false;

			if (!$this->prepareQuery("select_media_format_by_id", "SELECT id, name, datatype, mode, maxloadcount, maxreadcount, maxwritecount, maxopcount, lifespan, capacity, blocksize, densitycode, supportpartition, supportmam FROM mediaformat WHERE id = $1"))
				return null;

			$result = pg_execute("select_media_format_by_id", array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$mediaformat = pg_fetch_assoc($result);

			$mediaformat['id'] = intval($mediaformat['id']);
			$mediaformat['maxloadcount'] = intval($mediaformat['maxloadcount']);
			$mediaformat['maxreadcount'] = intval($mediaformat['maxreadcount']);
			$mediaformat['maxwritecount'] = intval($mediaformat['maxwritecount']);
			$mediaformat['maxopcount'] = intval($mediaformat['maxopcount']);
			$mediaformat['capacity'] = intval($mediaformat['capacity']);
			$mediaformat['blocksize'] = intval($mediaformat['blocksize']);
			$mediaformat['densitycode'] = intval($mediaformat['densitycode']);
			$mediaformat['supportpartition'] = $mediaformat['supportpartition'] == 't' ? true : false;
			$mediaformat['supportmam'] = $mediaformat['supportmam'] == 't' ? true : false;

			return $mediaformat;
		}

		public function getMediaFormatByName($name) {
			if (!$this->prepareQuery("select_media_format_by_name", "SELECT id FROM mediaformat WHERE name = $1 LIMIT 1"))
				return null;

			$result = pg_execute("select_media_format_by_name", array($name));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$mediaformat = pg_fetch_array($result);
			return intval($mediaformat[0]);
		}

		public function getMediaFormats(&$params) {
			$total_rows = 0;
			$query_params = array();

			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*) FROM mediaformat";
				$query_name = "select_total_mediaformat";

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

			$query = "SELECT id FROM mediaformat";

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

			$query_name = "select_media_formats_" . md5($query);
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

		public function getMediasByParams(&$params) {
			$query_common = " FROM media";
			$query_params = array();

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query_common .= ' WHERE name ~* $' . count($query_params);
			}

			if (isset($params['pool'])) {
				$query_params[] = $params['pool'];
				if (count($query_params) > 1)
					$query_common .= ' AND pool = $' . count($query_params);
				else
					$query_common .= ' WHERE pool = $' . count($query_params);
			}

			if (isset($params['type'])) {
				$query_params[] = $params['type'];
				if (count($query_params) > 1)
					$query_common .= ' AND type = $' . count($query_params);
				else
					$query_common .= ' WHERE type = $' . count($query_params);
			}

			if (isset($params['archiveformat'])) {
				$query_params[] = $params['archiveformat'];
				if (count($query_params) > 1)
					$query_common .= ' AND archiveformat = $' . count($query_params);
				else
					$query_common .= ' WHERE archiveformat = $' . count($query_params);
			}

			if (isset($params['mediaformat'])) {
				$query_params[] = $params['mediaformat'];
				if (count($query_params) > 1)
					$query_common .= ' AND mediaformat = $' . count($query_params);
				else
					$query_common .= ' WHERE mediaformat = $' . count($query_params);
			}

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_medias_" . md5($query);

				if (!$this->prepareQuery($query_name, $query))
					return array(
						'query' => $query,
						'query_name' => $query_name,
						'query_prepared' => false,
						'query_executed' => false,
						'query_params' => &$query_params,
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
						'query_params' => &$query_params,
						'rows' => array(),
						'total_rows' => 0
					);

				$row = pg_fetch_array($result);
				$total_rows = intval($row[0]);
			}

			if (isset($params['order_by'])) {
				$query_common .= ' ORDER BY ' . $params['order_by'];

				if (isset($params['order_asc']) && $params['order_asc'] === false)
					$query_common .= ' DESC';
			}

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query_common .= ' LIMIT $' . count($query_params);
			}

			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query_common .= ' OFFSET $' . count($query_params);
			}

			$query = 'SELECT id, pool' . $query_common;
			$query_name = "select_medias_by_params_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'query_params' => &$query_params,
					'rows' => array(),
					'total_rows' => 0
				);

			$result = pg_execute($query_name, $query_params);
			if ($result === false)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'query_params' => &$query_params,
					'rows' => array(),
					'total_rows' => 0
				);

			if (pg_num_rows($result) == 0)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => true,
					'query_params' => &$query_params,
					'rows' => array(),
					'total_rows' => 0
				);

			$rows = array();
			while ($row = pg_fetch_assoc($result)) {
				$row['id'] = intval($row['id']);
				if ($row['pool'])
					$row['pool'] = intval($row['pool']);
				$rows[] = $row;
			}
			if ($total_rows === 0)
				$total_rows = count($rows);

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'query_params' => &$query_params,
				'rows' => &$rows,
				'total_rows' => $total_rows,
				'params' => &$params
			);
		}

		public function getMediasByPool($pool, &$params) {
			$query_common = " FROM media WHERE pool = $1 ORDER BY id";
			$query_params = array($pool);

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_medias_by_pool";

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

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}
			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_medias_by_pool_" . md5($query);
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

		public function getMediasWithoutPool($mediaformat, &$params) {
			$query_common = " FROM media WHERE pool IS NULL";
			$query_params = array();
			if ($mediaformat != null) {
				$query_common .= " AND mediaformat = $1";
				$query_params[] = $mediaformat;
			}
			$query_common .= " ORDER BY id";

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_medias_with_no_pool";

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

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}
			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_medias_with_no_pool_" . md5($query);
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

		public function getMediasByPoolgroup($user_poolgroup, &$params) {
			$query_common = " FROM media m INNER JOIN pooltopoolgroup ptpg ON m.pool = ptpg.pool AND ptpg.poolgroup = $1";
			$query_params = array($user_poolgroup);

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_medias_by_user_poolgroup";

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

			$query = "SELECT id" . $query_common. ' ORDER BY id';

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}
			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_medias_by_user_poolgroup_" . md5($query);
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

		public function updateMedia(&$media) {
			if (!$this->prepareQuery("update_media", "UPDATE media SET name = $1, label = $2 WHERE id = $3"))
				return null;

			$result = pg_execute("update_media", array($media['name'], $media['label'], $media['id']));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>
