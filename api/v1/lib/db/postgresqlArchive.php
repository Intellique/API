<?php
	require_once("dateTime.php");
	require_once("postgresql.php");
	require_once("postgresqlJob.php");
	require_once("postgresqlMetadata.php");
	require_once("postgresqlPermission.php");

	class PostgresqlDBArchive extends PostgresqlDB implements DB_Archive {
		use PostgresqlDBJob, PostgresqlDBMetadata, PostgresqlDBPermission;

		public function createPool(&$pool) {
			if (!$this->prepareQuery("create_pool", "INSERT INTO pool(uuid, name, archiveformat, mediaformat, autocheck, lockcheck, growable, unbreakablelevel, metadata, backuppool, poolmirror) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11) RETURNING id"))
			return NULL;


			$lockcheck = $pool['lockcheck'] ? "TRUE" : "FALSE";
			$growable = $pool['growable'] ? "TRUE" : "FALSE";
			$backuppool = $pool['backuppool'] ? "TRUE" : "FALSE";
			$metadata = json_encode($pool['metadata'], JSON_FORCE_OBJECT);

			$result = pg_execute("create_pool", array($pool['uuid'], $pool['name'], $pool['archiveformat'], $pool['mediaformat'], $pool['autocheck'], $lockcheck, $growable, $pool['unbreakablelevel'], $metadata, $backuppool, $pool['poolmirror']));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			return intval($row[0]);
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

		public function getArchive($id) {
			if (!is_numeric($id))
				return false;

			if (!$this->prepareQuery("select_archive_by_id", "SELECT id, uuid, name, creator, owner, canappend, deleted FROM archive WHERE id = $1 AND NOT deleted"))
				return null;

			$result = pg_execute("select_archive_by_id", array($id));
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

			if (!$this->prepareQuery("select_archive_volume_by_id", "SELECT id, sequence, size, starttime, endtime, checktime, checksumok, media, mediaposition, jobrun, purged FROM archivevolume WHERE archive = $1 ORDER BY sequence"))
				return null;

			$result = pg_execute("select_archive_volume_by_id", array($id));
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

		public function getArchives($user_id, &$params) {
			$query_common = " FROM archive WHERE (creator = $1 OR owner = $1 OR id IN (SELECT av.archive FROM archivevolume av INNER JOIN media m ON av.sequence = 0 AND av.media = m.id WHERE m.pool IN (SELECT ppg.pool FROM users u INNER JOIN pooltopoolgroup ppg ON u.id = $1 AND u.poolgroup = ppg.poolgroup)))";
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

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query .= ' AND name = $' . count($query_params);
			}

			if (isset($params['creator'])) {
				$query_params[] = $params['creator'];
				$query .= ' AND creator = $' . count($query_params);
			}

			if (isset($params['owner'])) {
				$query_params[] = $params['owner'];
				if (is_numeric($params['owner']))
					$query .= ' AND owner = $' . count($query_params);
				else
					$query .= ' AND owner IN (SELECT id FROM users WHERE login = $' . count($query_params) .')';
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

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
				'rows' => $rows,
				'total_rows' => count($rows)
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

		public function getArchiveFile($id) {
			if (!is_numeric($id))
				return false;

			if (!$this->prepareQuery("select_archivefile_by_id", "SELECT id, name, mimetype, ownerid, owner, size FROM archivefile WHERE id = $1"))
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

			$query = 'SELECT id FROM archivefile';
			$query_params = array();
			$clause_where = false;

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query .= ' WHERE name = $' . count($query_params);
				$clause_where = true;
			}

			if (isset($params['owner'])) {
				$query_params[] = $params['owner'];
				if ($clause_where)
					$query .= ' AND owner = $' . count($query_params);
				else {
					$query .= ' WHERE owner = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['type'])) {
				$query_params[] = $params['type'];
				if ($clause_where)
					$query .= ' AND type = $' . count($query_params);
				else {
					$query .= ' WHERE type = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['groups'])) {
				$query_params[] = $params['groups'];
				if ($clause_where)
					$query .= ' AND groups = $' . count($query_params);
				else {
					$query .= ' WHERE groups = $' . count($query_params);
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

			$query_name = "select_archive_files_by_params_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, $query_params);
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$archivefiles = array();
			while ($row = pg_fetch_array($result))
				$archivefiles[] = intval($row[0]);

			return $archivefiles;
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

		public function getFilesFromArchive($id, &$params) {
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
					'iterator' => null
				);

			$result = pg_execute($this->connect, $query_name, $query_params);
			if ($result === false)
				return array(
					'query' => $query,
					'query_name' => $query_name,
					'query_prepared' => true,
					'query_executed' => false,
					'iterator' => null
				);

			return array(
				'query' => $query,
				'query_name' => $query_name,
				'query_prepared' => true,
				'query_executed' => true,
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

		public function getMedia($id) {
			if (!is_numeric($id))
				return false;

			if (!$this->prepareQuery("select_media_by_id", "SELECT id, uuid, label, mediumserialnumber, name, status, firstused, usebefore, lastread, lastwrite, loadcount, readcount, writecount, operationcount, nbtotalblockread, nbtotalblockwrite, nbreaderror, nbwriteerror, nbfiles, blocksize, freeblock, totalblock, haspartition, append, type, writelock, archiveformat, mediaformat, pool FROM media WHERE id = $1"))
				return null;

			$result = pg_execute("select_media_by_id", array($id));
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
			$media['pool'] = $this->getPool($media['pool']);

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

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_medias";

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

			$clause_where = false;
			$query = 'SELECT id, pool FROM media';

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query .= ' WHERE name = $' . count($query_params);
				$clause_where = true;
			}

			if (isset($params['pool'])) {
				$query_params[] = $params['pool'];
				if ($clause_where)
					$query .= ' AND pool = $' . count($query_params);
				else {
					$query .= ' WHERE pool = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['type'])) {
				$query_params[] = $params['type'];
				if ($clause_where)
					$query .= ' AND type = $' . count($query_params);
				else {
					$query .= ' WHERE type = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['nbfiles'])) {
				$query_params[] = $params['nbfiles'];
				if ($clause_where)
					$query .= ' AND nbfiles = $' . count($query_params);
				else {
					$query .= ' WHERE nbfiles = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['archiveformat'])) {
				$query_params[] = $params['archiveformat'];
				if ($clause_where)
					$query .= ' AND archiveformat = $' . count($query_params);
				else {
					$query .= ' WHERE archiveformat = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['mediaformat'])) {
				$query_params[] = $params['mediaformat'];
				if ($clause_where)
					$query .= ' AND mediaformat = $' . count($query_params);
				else {
					$query .= ' WHERE mediaformat = $' . count($query_params);
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

			$query_name = "select_medias_by_params_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, $query_params);
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$medias = array();
			while ($row = pg_fetch_array($result))
				$medias[] = $row;

			return $medias;
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

		public function getMediasByPoolgroup($user_poolgroup, &$params) {
			$query_common = " FROM media m INNER JOIN pooltopoolgroup ptpg ON m.pool = ptpg.pool AND ptpg.poolgroup = $1 ORDER BY id";
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

			$query = "SELECT id" . $query_common;

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

		public function getPool($id) {
			if (!is_numeric($id))
				return false;

			if (!$this->prepareQuery("select_pool_by_id", "SELECT id, uuid, name, archiveformat, mediaformat, autocheck, lockcheck, growable, unbreakablelevel, rewritable, metadata, backuppool, pooloriginal,  poolmirror, deleted FROM pool WHERE id = $1 AND NOT deleted"))
				return null;

			$result = pg_execute("select_pool_by_id", array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$pool = pg_fetch_assoc($result);

			$pool['id'] = intval($pool['id']);
			$pool['archiveformat'] = $this->getArchiveFormat($pool['archiveformat']);
			$pool['mediaformat'] = $this->getMediaFormat($pool['mediaformat']);
			$pool['lockcheck'] = $pool['lockcheck'] == 't' ? true : false;
			$pool['growable'] = $pool['growable'] == 't' ? true : false;
			$pool['rewritable'] = $pool['rewritable'] == 't' ? true : false;
			$pool['metadata'] = json_decode($pool['metadata']);
			$pool['backuppool'] = $pool['backuppool'] == 't' ? true : false;
			$pool['pooloriginal'] = isset($pool['pooloriginal']) ? intval($pool['pooloriginal']) : null;
			$pool['poolmirror'] = isset($pool['poolmirror']) ? intval($pool['poolmirror']) : null;
			$pool['deleted'] = $pool['deleted'] == 't' ? true : false;

			return $pool;
		}

		public function getPoolByName($name) {

			if (!$this->prepareQuery("select_pool_by_name", "SELECT id FROM pool WHERE name = $1 LIMIT 1"))
				return null;

			$result = pg_execute("select_pool_by_name", array($name));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$pool = pg_fetch_array($result);
			return intval($pool[0]);
		}

		public function getPoolsByParams(&$params) {
			$query = 'SELECT p.id FROM pool p INNER JOIN pooltopoolgroup ppg ON p.id = ppg.pool';
			$query_params = array();
			$clause_where = false;

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query .= ' WHERE p.name = $' . count($query_params) . '::TEXT';
				$clause_where = true;
			}

			if (isset($params['poolgroup'])) {
				$query_params[] = $params['poolgroup'];
				if ($clause_where)
					$query .= ' AND poolgroup = $' . count($query_params);
				else {
					$query .= ' WHERE poolgroup = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['mediaformat'])) {
				$query_params[] = $params['mediaformat'];
				if ($clause_where)
					$query .= ' AND mediaformat = $' . count($query_params);
				else {
					$query .= ' WHERE mediaformat = $' . count($query_params);
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

			$query_name = "select_pool_id_by_params _" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, $query_params);

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$pools = array();
			while ($row = pg_fetch_array($result))
				$pools[] = intval($row[0]);

			return $pools;
		}

		public function getPoolsByPoolgroup($user_poolgroup, &$params) {
			$query_common = " FROM pooltopoolgroup WHERE poolgroup = $1 ORDER BY pool";
			$query_params = array($user_poolgroup);

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_pools_by_user_poolgroup";

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

			$query = "SELECT pool" . $query_common;

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}
			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_pools_by_user_poolgroup_" . md5($query);
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

		public function updateMedia(&$media) {
			if (!$this->prepareQuery("update_media", "UPDATE media SET name = $1, label = $2 WHERE id = $3"))
				return null;

			$result = pg_execute("update_media", array($media['name'], $media['label'], $media['id']));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function updatePool(&$pool) {
			if (!$this->prepareQuery("update_pool", "UPDATE pool SET uuid = $1, name = $2, archiveformat = $3, mediaformat = $4, autocheck = $5, lockcheck = $6, growable = $7, unbreakablelevel = $8, rewritable = $9, metadata = $10, backuppool = $11, poolmirror = $12, deleted = $13 WHERE id = $14"))
				return null;

			$archiveformat = is_array($pool['archiveformat']) ? $pool['archiveformat']['id'] : $pool['archiveformat'];
			$mediaformat = is_array($pool['mediaformat']) ? $pool['mediaformat']['id'] : $pool['mediaformat'];
			$lockcheck = $pool['lockcheck'] ? "TRUE" : "FALSE";
			$growable = $pool['growable'] ? "TRUE" : "FALSE";
			$rewritable = $pool['rewritable'] ? "TRUE" : "FALSE";
			$backuppool = $pool['backuppool'] ? "TRUE" : "FALSE";
			$deleted = $pool['deleted'] ? "TRUE" : "FALSE";
			$metadata = json_encode($pool['metadata']);

			$result = pg_execute("update_pool", array($pool['uuid'], $pool['name'], $archiveformat, $mediaformat, $pool['autocheck'], $lockcheck, $growable, $pool['unbreakablelevel'], $rewritable, $metadata, $backuppool, $pool['poolmirror'], $deleted, $pool['id']));
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

		public function getJob($id) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_job_by_id', "SELECT j.id, j.name, jt.name AS type, j.nextstart, EXTRACT(EPOCH FROM j.interval) AS interval, j.repetition, j.status, j.update, j.archive, j.backup, j.media, j.pool, j.host, j.login, j.metadata, j.options FROM job j INNER JOIN jobtype jt ON j.type = jt.id WHERE j.id = $1 LIMIT 1 FOR UPDATE"))
				return null;

			$result = pg_execute($this->connect, 'select_job_by_id', array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);

			$row['id'] = intval($row['id']);
			$row['nextstart'] = dateTimeParse($row['nextstart']);
			$row['interval'] = PostgresqlDB::getInteger($row['interval']);
			$row['repetition'] = PostgresqlDB::getInteger($row['repetition']);
			$row['update'] = dateTimeParse($row['update']);
			$row['archive'] = PostgresqlDB::getInteger($row['archive']);
			$row['backup'] = PostgresqlDB::getInteger($row['backup']);
			$row['media'] = PostgresqlDB::getInteger($row['media']);
			$row['pool'] = PostgresqlDB::getInteger($row['pool']);
			$row['host'] = intval($row['host']);
			$row['login'] = intval($row['login']);
			$row['metadata'] = json_decode($row['metadata']);
			$row['options'] = json_decode($row['options']);

			return $row;
		}

		public function getUser($id, $login) {
			if ((isset($id) && !is_numeric($id)) || (isset($login) && !is_string($login)))
				return false;

			if (isset($id)) {
				$isPrepared = $this->prepareQuery('select_user_by_id', "SELECT id, login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled FROM users WHERE id = $1 LIMIT 1");
				if (!$isPrepared)
					return null;

				$result = pg_execute($this->connect, 'select_user_by_id', array($id));
			} else {
				$isPrepared = $this->prepareQuery('select_user_by_login', "SELECT id, login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled FROM users WHERE login = $1 LIMIT 1");
				if (!$isPrepared)
					return null;

				$result = pg_execute($this->connect, 'select_user_by_login', array($login));
			}

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
