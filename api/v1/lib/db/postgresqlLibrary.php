<?php
	require_once("dbLibrary.php");

	trait PostgresqlDBLibrary {
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

		public function getDrivesByChanger($id) {
			$query_name = 'get_drives_by_changer';
			if (!$this->prepareQuery($query_name, "SELECT cs.index AS drivenumber, d.id, d.model, d.vendor, d.serialnumber, d.status FROM changerslot cs INNER JOIN drive d ON cs.drive = d.id WHERE cs.changer = $1 AND cs.drive IS NOT NULL"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array($id));
			if (!$query_result)
				return false;

			$result = array();
			while ($row = pg_fetch_assoc($query_result)) {
				$row['id'] = intval($row['id']);
				$row['drivenumber'] = intval($row['drivenumber']);
				$result[] = $row;
			}

			return $result;
		}

		public function getPhysicalLibraries() {
			$query_name = 'get_physical_librairies';
			if(!$this->prepareQuery($query_name, "SELECT c.id, c.model, c.vendor, c.serialnumber, c.status, c.isonline, c.action FROM changer c WHERE c.serialnumber NOT IN (SELECT uuid::TEXT FROM vtl) AND c.enable AND serialnumber NOT IN (SELECT serialnumber FROM drive WHERE changer <> c.id)"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array());
			if (!$query_result)
				return false;

			$result = array();
			while ($row = pg_fetch_assoc($query_result)) {
				$row['id'] = intval($row['id']);
				$row['isonline'] = $row['isonline'] == 't';
				$result[] = $row;
			}

			return $result;
		}

		public function getSlotsByChanger($id) {
			$query_name = 'get_slots_by_changer';
			if (!$this->prepareQuery($query_name, "SELECT changer || '_' || index AS changerslotid, index AS slotnumber, CASE WHEN isieport THEN 'import / export' ELSE 'storage' END AS type, media FROM changerslot WHERE changer = $1 AND drive IS NULL ORDER BY index"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array($id));
			if (!$query_result)
				return false;

			$result = array();

			while ($row = pg_fetch_assoc($query_result)) {
				$row['slotnumber'] = intval($row['slotnumber']);
				$row['media'] = isset($row['media']) ? intval($row['media']) : NULL;
				$result[] = $row;
			}

			return $result;
		}

		public function getSlotByDrive($id) {
			$query_name = 'get_slots_by_drive';
			if (!$this->prepareQuery($query_name, "SELECT cs.changer || '_' || cs.index AS slotid, cs.index AS slotnumber, cs.media FROM changerslot cs INNER JOIN drive d ON cs.drive = d.id AND d.id = $1"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array($id));
			if (!$query_result)
				return false;

			$result = array();

			while ($row = pg_fetch_assoc($query_result)) {
				$row['media'] = isset($row['media']) ? intval($row['media']) : NULL;
				$row['type'] = 'drive';
				$row['slotnumber'] = intval($row['slotnumber']);
				$result[] = $row;
			}

			return $result;
		}

		public function getStandaloneDrives() {
			$query_name = 'get_standalone_drives';
			if (!$this->prepareQuery($query_name, "SELECT d.id AS driveid, c.model, c.vendor, d.serialnumber AS driveserialnumber, d.status FROM changer c LEFT JOIN drive d ON d.changer = c.id AND d.serialnumber = c.serialnumber WHERE c.serialnumber NOT IN (SELECT uuid::TEXT FROM vtl) AND c.enable AND d.id IS NOT NULL"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array());
			if (!$query_result)
				return false;

			$result = array();
			while ($row = pg_fetch_assoc($query_result))
				$result[] = $row;

			return $result;
		}

		public function getVTL($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!is_numeric($id) || !isset($id))
				return false;

			$query = "SELECT * FROM vtl WHERE id = $1";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_vtl_" . md5($query);


			if (!$this->prepareQuery($query_name, $query))
				return NULL;

			$result = pg_execute($query_name, array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$vtl = pg_fetch_assoc($result);
			$vtl['id'] = intval($vtl['id']);
			$vtl['nbslots'] = intval($vtl['nbslots']);
			$vtl['nbdrives'] = intval($vtl['nbdrives']);
			$vtl['host'] = intval($vtl['host']);
			$vtl['deleted'] = $vtl['deleted'] == 't' ? true : false;

			return $vtl;
		}

		public function getVTLs() {
			$query_name = 'get_VTLs';
			if (!$this->prepareQuery($query_name, "SELECT DISTINCT c.id AS changerid, SUBSTRING(v.path FROM CHAR_LENGTH(SUBSTRING(v.path FROM '(.+/)[^/]+')) + 1) AS name, c.model, c.vendor, c.serialnumber AS changerserialnumber, c.status, c.isonline, c.action FROM changer c INNER JOIN vtl v ON c.serialnumber = v.uuid::TEXT AND c.enable"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array());
			if (!$query_result)
				return false;

			$result = array();
			while ($row = pg_fetch_assoc($query_result))
				$result[] = $row;

			return $result;
		}

		public function getVTLs2(&$params) {
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

		public function setLibraryAction($id, $act) {
			$query_name = 'set_library_action';
			if (!$this->prepareQuery($query_name,"UPDATE changer set action = $1 WHERE id = $2"))
				return false;
			
			return pg_execute($this->connect, $query_name, array($act, $id)) !== false;
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
