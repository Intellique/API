<?php
	require_once("dateTime.php");
	require_once("postgresql.php");
	require_once("postgresqlJob.php");
	require_once("postgresqlMetadata.php");
	require_once("postgresqlPermission.php");

	class PostgresqlDBArchive extends PostgresqlDB implements DB_Archive {
		use PostgresqlDBJob, PostgresqlDBMetadata, PostgresqlDBPermission;

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

		public function getArchiveFormatByName($name){
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

		public function getFilesFromArchive($id, &$params) {
			$query = "SELECT id, name, type, mimetype, ownerid, owner, groupid, groups, perm, ctime, mtime, size FROM archivefile WHERE id IN (SELECT archivefile FROM archivefiletoarchivevolume WHERE archivevolume IN (SELECT id from archivevolume WHERE archive = $1))";
			$query_params = array($id);

			if (isset($params['order_by'])) {
				$query .= ' ORDER BY ' . $params['order_by'];

				if (isset($params['order_asc']) && $params['order_asc'] === false)
					$query .= ' DESC';
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
			$pool['pooloriginal'] = intval($pool['pooloriginal']);
			$pool['poolmirror'] = intval($pool['poolmirror']);
			$pool['deleted'] = $pool['deleted'] == 't' ? true : false;

			return $pool;
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

		public function updateMedia(&$media) {
			if (!$this->prepareQuery("update_media", "UPDATE media SET name = $1, label = $2 WHERE id = $3"))
				return null;

			$result = pg_execute("update_media", array($media['name'], $media['label'], $media['id']));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function updatePool(&$pool) {
			if (!$this->prepareQuery("update_pool", "UPDATE pool SET deleted = $1 WHERE id = $2"))
				return null;

			$result = pg_execute("update_pool", array($pool['deleted'], $pool['id']));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>
