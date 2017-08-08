<?php
	require_once("dateTime.php");
	require_once("postgresql.php");
	require_once("postgresqlJob.php");
	require_once("postgresqlMedia.php");
	require_once("postgresqlMetadata.php");
	require_once("postgresqlPermission.php");
	require_once("postgresqlPool.php");
	require_once("postgresqlUser.php");

	class PostgresqlDBArchive extends PostgresqlDB implements DB_Archive {
		use PostgresqlDBJob, PostgresqlDBMedia, PostgresqlDBMetadata, PostgresqlDBPermission, PostgresqlDBPool, PostgresqlDBUser;

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

		public function createVTL(&$vtl) {
			if (!$this->prepareQuery("create_vtl", "INSERT INTO vtl(uuid, path, prefix, nbslots, nbdrives, mediaformat, host, deleted) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING id"))
				return NULL;

			if (isset($vtl['deleted']))
				$deleted = $vtl['deleted'] ? "TRUE" : "FALSE";
			else
				$deleted = "FALSE";

			$result = pg_execute("create_vtl", array($vtl['uuid'], $vtl['path'], $vtl['prefix'], $vtl['nbslots'], $vtl['nbdrives'], $vtl['mediaformat'], $vtl['host'], $deleted));

			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			return intval($row[0]);
		}

		public function deleteVTL($id) {
			if (!$this->prepareQuery("delete_vtl", "UPDATE vtl SET deleted = true WHERE id = $1"))
				return NULL;

			$result = pg_execute("delete_vtl", array($id));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
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

			if (pg_num_rows($result) == 0)
				return false;

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
			$query_common = " FROM archive WHERE (creator = $1 OR owner = $1 OR id IN (SELECT av.archive FROM archivevolume av INNER JOIN media m ON av.sequence = 0 AND av.media = m.id WHERE m.pool IN (SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $1 AND u.poolgroup = ppg.poolgroup)))";
			$query_params = array($user['id']);

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query_common .= ' AND name ~* $' . count($query_params);
			}

			if (isset($params['creator'])) {
				$query_params[] = $params['creator'];
				$query_common .= ' AND creator = $' . count($query_params);
			}

			if (isset($params['owner'])) {
				$query_params[] = $params['owner'];
				if (is_numeric($params['owner']))
					$query_common .= ' AND owner = $' . count($query_params);
				else
					$query_common .= ' AND owner IN (SELECT id FROM users WHERE login = $' . count($query_params) .')';
			}

			if (isset($params['archivefile'])) {
				$query_params[] = $params['archivefile'];
				$query_common .= ' AND id IN (SELECT archive FROM milestones_files WHERE archivefile = $' . count($query_params) .')';
			}

			if (isset($params['deleted'])) {
				if ($params['deleted'] === 'no')
					$query_common .= ' AND NOT deleted';
				else if ($params['deleted'] === 'only')
					$query_common .= ' AND deleted';
			}

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_archives_by_user_" + md5($query);

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

		public function getArchivesByMedia($id) {
			if (!is_numeric($id))
				return false;

			if (!$this->prepareQuery("select_archives_by_media", "SELECT DISTINCT archive FROM archivevolume WHERE media = $1"))
				return null;

			$result = pg_execute("select_archives_by_media", array($id));
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

		public function getArchiveFile($id) {
			if (!is_numeric($id))
				return false;

			if (!$this->prepareQuery("select_archivefile_by_id", "SELECT id, archivefile.name, milestones_files.archive, archivefile.mimetype, ownerid, owner, groupid, groups, ctime, mtime, size, medias FROM archivefile JOIN milestones_files ON archivefile.id=milestones_files.archivefile WHERE id = $1"))
				return null;

			$result = pg_execute("select_archivefile_by_id", array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$archivefile = pg_fetch_assoc($result);

			return $archivefile;
		}

		public function getArchiveFilesByParams(&$params) {
			$query_common = ' FROM milestones_files';
			$query_params = array();
			$clause_where = false;

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query_common .= ' WHERE name ~* $' . count($query_params);
				$clause_where = true;
			}

			if (isset($params['archive'])) {
				$query_params[] = $params['archive'];
				if ($clause_where)
					$query_common .= ' AND archive = $' . count($query_params);
				else {
					$query_common .= ' WHERE archive = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['type'])) {
				$query_params[] = $params['type'];
				if ($clause_where)
					$query_common .= ' AND type = $' . count($query_params);
				else {
					$query_common .= ' WHERE type = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['mimetype'])) {
				$query_params[] = $params['mimetype'];
				if ($clause_where)
					$query_common .= ' AND mimetype = $' . count($query_params);
				else {
					$query_common .= ' WHERE mimetype = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['archive_name'])) {
				$query_params[] = $params['archive_name'];
				if ($clause_where)
					$query_common .= ' AND archive_name = $' . count($query_params);
				else {
					$query_common .= ' WHERE archive_name = $' . count($query_params);
					$clause_where = true;
				}
			}

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

			$query = 'SELECT archivefile' . $query_common;
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
				'rows' => $archivefiles,
				'total_rows' => $total_rows
			);
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

		public function getDevice($id) {
			if (!$this->prepareQuery("select_slots_and_media","SELECT cs.changer, cs.index, cs.drive, m.label, m.mediumserialnumber, m.status, m.freeblock, m.totalblock FROM changerslot cs LEFT JOIN media m ON cs.media = m.id WHERE changer = $1 AND cs.enable = $2 ORDER BY cs.index"))
				return null;

			if (!$this->prepareQuery("select_changer","SELECT id, model, vendor, serialnumber, status, isonline FROM changer WHERE id = $1 AND enable = $2"))
				return null;

			if (!$this->prepareQuery("select_drives","SELECT d.id, d.model, d.vendor, d.serialnumber, d.status, cs.index FROM drive d INNER JOIN changerslot cs ON id = drive WHERE d.changer = $1 AND d.enable = $2"))
				return null;



			$result = pg_execute($this->connect, 'select_changer', array($id, 't'));
			if ($result === false)
				return null;
			if (pg_num_rows($result) == 0)
				return false;
			$row = pg_fetch_array($result);
			$changer = array('changerid' => $row['id'], 'model' => $row['model'], 'vendor' => $row['vendor'], 'changerserialnumber' => $row['serialnumber'], 'status' => $row['status'], 'isonline' => $row['isonline'], 'drives' => array(), 'slots' => array());


			$result = pg_execute($this->connect, 'select_drives', array($id, 't'));
			if ($result === false)
				return null;
			$drives = array();
			while ($row = pg_fetch_array($result))
				$drives[] = array('drivenumber' => $row['index'], 'driveid' => $row['id'], 'model' => $row['model'], 'vendor' => $row['vendor'], 'driveserialnumber' => $row['serialnumber'], 'status' => $row['status'], 'slot' => array());


			$result = pg_execute($this->connect, 'select_slots_and_media', array($id, 't'));
			if ($result === false)
				return null;
			$slots = array();
			$driveslot = array();
			while ($row = pg_fetch_array($result)) {
				if ($row['drive'] === null) {
					$slots[] = array('slotnumber' => $row['index'], 'slottype' => "storage", 'chanegrid' => $row['changer'], 'chanegrslotid' => $row['changer']."_".$row['index'], 'medialabel' => $row['label'], 'mediaserialnumber' => $row['mediumserialnumber'], 'mediastatus' => $row['status'], 'freeblock' => $row['freeblock'], 'totalblock' => $row['totalblock']);
				}
				else {
					$driveslot[] = array('driveid' => $row['drive'], 'slotid' => $row['drive'].'_'.$row['index'], 'slotnumber' => $row['index'], 'slottype' => "drive", 'medialabel' => $row['label'], 'mediaserialnumber' => $row['mediumserialnumber'], 'mediastatus' => $row['status'], 'freeblock' => $row['freeblock'], 'totalblock' => $row['totalblock']);

					foreach ($drives as &$list) {
					if ($list['driveid'] == $row['drive'])
						$list['slot'] = end($driveslot);
					}
				}
			}



			$changer['drives'] = $drives;
			$changer['slots'] = $slots;
			$return = array('changer' => $changer);
			return $return;
		}

		public function getDevices(&$params) {
			$query_common = 'FROM changer WHERE enable = $1';
			$query_params = array('t');

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_changers";

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

			$query = "SELECT id " . $query_common;

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}

			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_changers_" . md5($query);
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

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'rows' => $rows,
				'total_rows' => count($rows)
			);
		}

		public function getDevicesByParams(&$params) {

			$query_common = " FROM changer";
			$query_params = array();

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_changers";

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

			$clause_where = false;

			if (isset($params['isonline'])) {
				$query_params[] = $params['isonline'];
				$query .= ' WHERE isonline = $' . count($query_params);
				$clause_where = true;
			}

			if (isset($params['enable'])) {
				$query_params[] = $params['enable'];
				if ($clause_where)
					$query .= ' AND enable = $' . count($query_params);
				else {
					$query .= ' WHERE enable = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['model'])) {
				$query_params[] = $params['model'];
				if ($clause_where)
					$query .= ' AND model = $' . count($query_params);
				else {
					$query .= ' WHERE model = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['vendor'])) {
				$query_params[] = $params['vendor'];
				if ($clause_where)
					$query .= ' AND vendor = $' . count($query_params);
				else {
					$query .= ' WHERE vendor = $' . count($query_params);
					$clause_where = true;
				}
			}

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

			$query_name = "select_chagers_by_params_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => false,
					'query_executed' => false,
					'rows' => array(),
					'total_rows' => $total_rows
				);

			$result = pg_execute($query_name, $query_params);
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
				'rows' => $rows,
				'total_rows' => count($rows)
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

		public function getVTL($id) {
			if (!is_numeric($id) || !isset($id))
				return false;

			if (!$this->prepareQuery("select_vtl", "SELECT * FROM vtl WHERE id = $1"))
				return NULL;

			$result = pg_execute("select_vtl", array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$vtl = pg_fetch_assoc($result);
			$vtl['deleted'] = $vtl['deleted'] == 't' ? true : false;

			return $vtl;
		}

		public function getVTLs(&$params) {
			$query_common = 'FROM vtl';
			$query_params = array();

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_vtls";

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

			$query = "SELECT id " . $query_common;

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}

			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_vtls_" . md5($query);
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

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'rows' => $rows,
				'total_rows' => count($rows)
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
			if (!$this->prepareQuery("update_archive", "UPDATE archive SET name = $1, owner = $2, canappend = $3, deleted = $4 WHERE id = $5"))
				return null;

			$canappend = $archive['canappend'] ? "TRUE" : "FALSE";
			$deleted = $archive['deleted'] ? "TRUE" : "FALSE";

			$result = pg_execute("update_archive", array($archive['name'], $archive['owner'], $canappend, $deleted, $archive['id']));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function updateVTL($vtl) {
			if (!$this->prepareQuery("update_vtl", "UPDATE vtl SET uuid = $2, path = $3, prefix = $4, nbslots = $5, nbdrives = $6, mediaformat = $7, host = $8, deleted = $9 WHERE id = $1"))
				return null;

			$deleted = $vtl['deleted'] ? "TRUE" : "FALSE";

			$result = pg_execute("update_vtl", array($vtl['id'], $vtl['uuid'], $vtl['path'], $vtl['prefix'], $vtl['nbslots'], $vtl['nbdrives'], $vtl['mediaformat'], $vtl['host'], $deleted));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>
