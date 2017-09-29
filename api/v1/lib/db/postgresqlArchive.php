<?php
	require_once("dateTime.php");
	require_once("dbArchive.php");

	trait PostgresqlDBArchive {
		public function checkArchiveMirrorInCommon($archiveA, $archiveB) {
			$query = 'SELECT COUNT(*) > 0 FROM archivetoarchivemirror a1 INNER JOIN archivetoarchivemirror a2 ON a1.archivemirror = a2.archivemirror WHERE a1.archive = $1 AND a2.archive = $2';
			$query_params = array($archiveA, $archiveB);
			$query_name = 'check_archive_mirror_in_common';

			if (!$this->prepareQuery($query_name, $query))
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'result' => null,
					'query_params' => &$query_params
				);

			$result = pg_execute($query_name, $query_params);
			if ($result === false)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'result' => null,
					'query_params' => &$query_params
				);

			$row = pg_fetch_array($result);

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'result' => $row[0] == 't' ? true : false,
				'query_params' => &$query_params
			);
		}

		public function getArchive($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!is_numeric($id))
				return false;

			$query = "SELECT id, uuid, name, creator, owner, canappend, deleted FROM archive WHERE id = $1";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query .= ' LIMIT 1';

			$query_name = "select_archive_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$archive = pg_fetch_assoc($result);

			$archive['id'] = intval($archive['id']);
			$archive['creator'] = intval($archive['creator']);
			$archive['owner'] = intval($archive['owner']);
			$archive['canappend'] = $archive['canappend'] == 't' ? true : false;
			$archive['deleted'] = $archive['deleted'] == 't' ? true : false;

			$archive['metadata'] = $this->getMetadatas($id, 'archive');


			$query = "SELECT id, sequence, size, starttime, endtime, checktime, checksumok, media, mediaposition, jobrun, purged FROM archivevolume WHERE archive = $1 ORDER BY sequence";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_archive_volume_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, array($id));
			if ($result === false)
				return null;

			$archive['volumes'] = array();
			$archive['size'] = 0;

			while ($archivevolume = pg_fetch_assoc($result)) {
				$archive['volumes'][] = array(
					'id' => intval($archivevolume['id']),
					'sequence' => intval($archivevolume['sequence']),
					'size' => intval($archivevolume['size']),
					'starttime' => dateTimeParse($archivevolume['starttime']),
					'endtime' => dateTimeParse($archivevolume['endtime']),
					'checktime' => dateTimeParse($archivevolume['checktime']),
					'checksumok' => $archivevolume['checksumok'] == 't' ? true : false,
					'media' => intval($archivevolume['media']),
					'mediaposition' => intval($archivevolume['mediaposition']),
					'jobrun' => intval($archivevolume['jobrun']),
					'purged' => intval($archivevolume['purged'])
				);
				$archive['size'] += intval($archivevolume['size']);
			}

			return $archive;
		}

		public function getArchives(&$user, &$params) {
			$query_common = " FROM archive";
			$query_params = array();

			if (isset($params['archivefile']) or isset($params['media']) or isset($params['pool']) or isset($params['poolgroup'])) {
				$query_common .= " WHERE id IN (SELECT av.archive FROM archivevolume av";

				if (isset($params['archivefile'])) {
					$query_params[] = $params['archivefile'];
					$query_common .= " INNER JOIN archivefiletoarchivevolume af2av ON av.id = af2av.archivevolume AND af2av.archivefile = $" . count($query_params);
				}

				if (isset($params['pool']) or isset($params['poolgroup'])) {
					$query_common .= " INNER JOIN media m ON av.media = m.id WHERE";

					if (isset($params['pool']) and isset($params['poolgroup'])) {
						$query_params[] = $params['pool'];
						$query_common .= " m.pool = $" . count($query_params) . " AND EXISTS(SELECT * FROM pooltopoolgroup WHERE pool = $" . count($query_params);
						$query_params[] = $params['poolgroup'];
						$query_common .= " AND poolgroup = $" . count($query_params) . ")";
					} elseif (isset($params['poolgroup'])) {
						$query_params[] = $params['poolgroup'];
						$query_common .= " m.pool IN (SELECT pool FROM pooltopoolgroup WHERE poolgroup = $" . count($query_params) . ")";
					} else {
						$query_params[] = $params['pool'];
						$query_common .= " m.pool = $" . count($query_params);
					}

					if (isset($params['media'])) {
						$query_params[] = $params['media'];
						$query_common .= " AND m.id = $" . count($query_params);
					}
				} elseif (isset($params['media'])) {
					$query_params[] = $params['media'];
					$query_common .= " WHERE av.media = $" . count($query_params);
				}

				$query_common .= ")";
			}

			if (isset($params['name'])) {
				$query_params[] = $params['name'];

				if (count($query_params) > 1)
					$query_common .= ' AND';
				else
					$query_common .= ' WHERE';

				$query_common .= ' name ~* $' . count($query_params);
			}

			if (isset($params['creator'])) {
				$query_params[] = $params['creator'];

				if (count($query_params) > 1)
					$query_common .= ' AND';
				else
					$query_common .= ' WHERE';

				if (is_integer($params['creator']))
					$query_common .= ' creator = $' . count($query_params);
				else
					$query_common .= ' creator = (SELECT id FROM users WHERE login = $' . count($query_params) .' LIMIT 1)';
			}

			if (isset($params['owner'])) {
				$query_params[] = $params['owner'];

				if (count($query_params) > 1)
					$query_common .= ' AND';
				else
					$query_common .= ' WHERE';

				if (is_integer($params['owner']))
					$query_common .= ' owner = $' . count($query_params);
				else
					$query_common .= ' owner = (SELECT id FROM users WHERE login = $' . count($query_params) .' LIMIT 1)';
			}

			if (isset($params['uuid'])) {
				$query_params[] = $params['uuid'];

				if (count($query_params) > 1)
					$query_common .= ' AND';
				else
					$query_common .= ' WHERE';

				$query_common .= ' uuid = $' . count($query_params);
			}

			if (isset($params['deleted'])) {
				if (count($query_params) > 0)
					$query_common .= ' AND';
				else
					$query_common .= ' WHERE';

				if ($params['deleted'] === 'no')
					$query_common .= ' NOT deleted';
				else if ($params['deleted'] === 'only')
					$query_common .= ' deleted';
			}

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_archives_by_user_" . md5($query);

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
			if ($total_rows == 0)
				$total_rows = pg_num_rows($result);

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

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'params' => &$query_params,
				'rows' => &$rows,
				'total_rows' => $total_rows
			);
		}

		public function getArchivesByMedia($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!is_numeric($id))
				return false;

			$query = "SELECT DISTINCT archive FROM archivevolume WHERE media = $1";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_archives_by_media_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, array($id));
			if ($result === false)
				return null;

			$archives = array();
			while ($row = pg_fetch_array($result))
				$archives[] = intval($row[0]);

			return $archives;
		}

		public function getArchivesByPool($id) {
			$query = 'SELECT id FROM archive WHERE id IN (SELECT archive FROM archivevolume WHERE media IN (SELECT id FROM media WHERE pool = $1)) AND NOT deleted';
			$query_name = "select_archives_by_pool";
			$query_params = array();
			if (isset($id))
				$query_params[] = $id;

			if (!$this->prepareQuery($query_name, $query))
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => 0,
					'query_params' => &$query_params
				);

			$result = pg_execute($query_name, $query_params);
			if ($result === false)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => 0,
					'query_params' => &$query_params
				);

			$rows = array();
			while ($row = pg_fetch_array($result))
				$rows[] = intval($row[0]);

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'rows' => &$rows,
				'total_rows' => count($rows),
				'query_params' => &$query_params
			);
		}

		public function getArchiveFile($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!is_numeric($id))
				return false;

			$query = "SELECT id, archivefile.name, json_object_agg(milestones_files.archive, medias::JSON) AS archives, archivefile.mimetype, ownerid, owner, groupid, groups, ctime, mtime, size FROM archivefile JOIN milestones_files ON archivefile.id = milestones_files.archivefile WHERE id = $1 GROUP BY id, archivefile.name, archivefile.mimetype, ownerid, owner, groupid, groups, ctime, mtime, size";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_archivefile_by_id" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$archivefile = pg_fetch_assoc($result);
			$archivefile['archives'] = json_decode($archivefile['archives']);
			$archivefile['groupid'] = intval($archivefile['groupid']);
			$archivefile['id'] = intval($archivefile['id']);
			$archivefile['ownerid'] = intval($archivefile['ownerid']);
			$archivefile['size'] = intval($archivefile['size']);

			return $archivefile;
		}

		public function getArchiveFilesByParams(&$params, $userId) {
			$query_common = ' FROM milestones_files mf INNER JOIN archive a ON mf.archive = a.id AND NOT a.deleted WHERE pool IN (SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $1 AND u.poolgroup = ppg.poolgroup)';
			$query_params = array($userId);

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query_common .= ' AND mf.name ~* $' . count($query_params);
			}

			if (isset($params['archive'])) {
				$query_params[] = $params['archive'];
				$query_common .= ' AND archive = $' . count($query_params);
			}

			if (isset($params['type'])) {
				$query_params[] = $params['type'];
				$query_common .= ' AND type = $' . count($query_params);
			}

			if (isset($params['mimetype'])) {
				$query_params[] = $params['mimetype'];
				$query_common .= ' AND mimetype = $' . count($query_params);
			}

			if (isset($params['archive_name'])) {
				$query_params[] = $params['archive_name'];
				$query_common .= ' AND archive_name = $' . count($query_params);
			}

			if (count($params) == 1)
				$query_common .= ' OR a.creator = $1 OR a.owner = $1';

			$total_rows = 0;
			if (isset($params['limit']) || isset($params['offset'])) {
				$query = 'SELECT COUNT(*)' . $query_common;
				$query_name = 'select_count_from_milestones_files_' . md5($query);
				if (!$this->prepareQuery($query_name, $query)) {
					return array(
						'query' => $query,
						'query_name' => $query_name,
						'query_prepared' => false,
						'query_executed' => false,
						'rows' => array(),
						'total_rows' => 0
					);
				}

				$result = pg_execute($this->connect, $query_name, $query_params);
				if ($result === false) {
					return array(
						'query' => $query,
						'query_name' => $query_name,
						'query_prepared' => true,
						'query_executed' => false,
						'rows' => array(),
						'total_rows' => 0
					);
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
				$row = pg_fetch_array($result);
				$total_rows = intval($row[0]);
			}

			$query = 'SELECT mf.archivefile' . $query_common;
			$query_name = "select_archive_files_by_params_" . md5($query);

			if (!$this->prepareQuery($query_name, $query)) {
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => 0
				);
			}

			$result = pg_execute($query_name, $query_params);
			if ($result === false) {
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => 0
				);
			}

			if ($total_rows == 0)
				$total_rows = pg_num_rows($result);

			if (pg_num_rows($result) == 0) {
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => true,
					'rows' => array(),
					'total_rows' => $total_rows
				);
			}

			$archivefiles = array();
			while ($row = pg_fetch_array($result))
				$archivefiles[] = intval($row[0]);

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'query_params' => &$query_params,
				'rows' => $archivefiles,
				'total_rows' => $total_rows
			);
		}

		public function getArchiveFormat($id) {
			if (!is_numeric($id))
				return false;

			if (!$this->prepareQuery("select_archive_format_by_id", "SELECT id, name, readable, writable FROM archiveformat WHERE id = $1"))
				return null;

			$result = pg_execute("select_archive_format_by_id", array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$archiveformat = pg_fetch_assoc($result);

			$archiveformat['id'] = intval($archiveformat['id']);
			$archiveformat['readable'] = $archiveformat['readable'] == 't' ? true : false;
			$archiveformat['writable'] = $archiveformat['writable'] == 't' ? true : false;

			return $archiveformat;
		}

		public function getArchiveFormatByName($name) {
			if (!$this->prepareQuery("select_archive_format_by_name", "SELECT id FROM archiveformat WHERE name = $1 LIMIT 1"))
				return null;

			$result = pg_execute("select_archive_format_by_name", array($name));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$archiveformat = pg_fetch_array($result);
			return intval($archiveformat[0]);
		}

		public function getArchiveFormats(&$params) {
			$total_rows = 0;
			$query_params = array();

			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*) FROM archiveformat";
				$query_name = "select_total_archiveformat";

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

			$query = "SELECT id FROM archiveformat";

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

			$query_name = "select_archive_formats_" . md5($query);
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

		public function getArchiveMirrorsByPool($pool, $poolMirror) {
			$query_name = 'select_archive_mirror_by_pool';
			$query = 'SELECT a.id, a2am.archivemirror FROM archive a LEFT JOIN archivetoarchivemirror a2am ON a.id = a2am.archive LEFT JOIN archivemirror am ON a2am.archivemirror = am.id AND am.poolmirror = $2 WHERE NOT a.deleted AND a.id IN (SELECT archive FROM archivevolume WHERE sequence = 0 AND media IN (SELECT id FROM media WHERE pool = $1))';
			$query_params = array($pool, $poolMirror);

			if (!$this->prepareQuery($query_name, $query))
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'result' => null,
					'query_params' => &$query_params
				);

			$result = pg_execute($query_name, $query_params);
			if ($result === false)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'result' => null,
					'query_params' => &$query_params
				);

			$rows = array();
			while ($row = pg_fetch_array($result))
				$rows[] = array(intval($row[0]), intval($row[1]));

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'result' => &$rows,
				'query_params' => &$query_params
			);
		}

		public function getFilesFromArchive($id, &$params) {
			$total_rows = 0;
			$count_query = "SELECT count(*) FROM archivefile WHERE id IN (SELECT archivefile FROM archivefiletoarchivevolume WHERE archivevolume IN (SELECT id from archivevolume WHERE archive = $1))";
			$query_name = "select_filecount_from_archive";
			if (!$this->prepareQuery($query_name, $count_query)) {
				return array(
					'query' => $count_query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'total_rows' => 0,
					'iterator' => null
				);
			}

			$result = pg_execute($this->connect, $query_name, array($id));
			if ($result === false) {
				return array(
					'query' => $count_query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'total_rows' => 0,
					'iterator' => null
				);
			}

			$row = pg_fetch_array($result);
			$total_rows = intval($row[0]);

			$query = "SELECT id, name, type, mimetype, ownerid, owner, groupid, groups, perm, ctime, mtime, size FROM archivefile WHERE id IN (SELECT archivefile FROM archivefiletoarchivevolume WHERE archivevolume IN (SELECT id from archivevolume WHERE archive = $1))";
			$query_params = array($id);

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

			$query .= ' FOR SHARE';

			$query_name = "select_files_from_archive_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'total_rows' => 0,
					'iterator' => null
				);

			$result = pg_execute($this->connect, $query_name, $query_params);
			if ($result === false)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'total_rows' => 0,
					'iterator' => null
				);

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'total_rows' => $total_rows,
				'iterator' => new PostgresqlDBResultIterator($result, array(
					'id' => 'getInteger',
					'name' => 'get',
					'type' => 'get',
					'mimetype' => 'get',
					'ownerid' => 'getInteger',
					'owner' => 'get',
					'groupid' => 'getInteger',
					'groups' => 'get',
					'perm' => 'getInteger',
					'ctime' => 'getDate',
					'mtime' => 'getDate',
					'size' => 'getInteger'
				), true)
			);
		}

		public function isArchiveSynchronized($id) {
			$query = "SELECT * FROM (SELECT archive, lastupdate = MAX(lastupdate) OVER (PARTITION BY archivemirror) AS synchronized FROM archivetoarchivemirror) AS am WHERE archive = $1";
			$query_name = "is_archive_synchronized";

			if (!$this->prepareQuery($query_name, $query))
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => 0
				);

			$result = pg_execute($this->connect, $query_name, array($id));
			if ($result === false)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => 0
				);

			$synchronized = true;
			if ($row = pg_fetch_array($result))
				$synchronized = $row[1] == 't' ? true : false;

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'synchronized' => $synchronized
			);
		}

		public function updateArchive(&$archive) {
			$query = "UPDATE archive SET";
			$query_params = array();

			if (isset($archive['name'])) {
				$query .= " name = $1";
				$query_params[] = $archive['name'];
			}

			if (isset($archive['owner'])) {
				if (count($query_params) > 0)
					$query .= ",";
				$query_params[] = $archive['owner'];
				$query .= " owner = $" . count($query_params);
			}

			if (isset($archive['canappend'])) {
				if (count($query_params) > 0)
					$query .= ",";
				$query_params[] = $archive['canappend'] ? "TRUE" : "FALSE";
				$query .= " canappend = $" . count($query_params);
			}

			if (isset($archive['deleted'])) {
				if (count($query_params) > 0)
					$query .= ",";
				$query_params[] = $archive['deleted'] ? "TRUE" : "FALSE";
				$query .= " deleted = $" . count($query_params);
			}

			if (count($query_params) == 0)
				return null;

			$query_params[] = $archive['id'];
			$query .= " WHERE id = $" . count($query_params);
			$query_name = "update_archive_" . md5($query);


			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, $query_params);
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>
