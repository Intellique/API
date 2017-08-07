<?php
	trait PostgresqlDBMedia {
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

		public function getPool($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!is_numeric($id))
				return false;

			$query = "SELECT id, uuid, name, archiveformat, mediaformat, autocheck, lockcheck, growable, unbreakablelevel, rewritable, metadata, backuppool, pooloriginal,  poolmirror, deleted FROM pool WHERE id = $1 AND NOT deleted";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_pool_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, array($id));
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
	}
?>
