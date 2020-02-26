<?php
	require_once("dbPool.php");

	trait PostgresqlDBPool {
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
			$pool_id = intval($row[0]);

			if ($this->createScripts($pool_id, $pool['scripts'], false))
				return $pool_id;
			else
				return null;
		}

		public function createPoolGroup(&$poolgroup) {
			if (!$this->prepareQuery('create_poolgroup', "INSERT INTO poolgroup(uuid, name) VALUES ($1, $2) RETURNING id"))
				return NULL;

			$result = pg_execute('create_poolgroup', array($poolgroup['uuid'], $poolgroup['name']));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			$poolgroup_id = intval($row[0]);

			if (count($poolgroup['pools']) > 0) {
				if (!$this->prepareQuery('link_pool_to_poolgroup', "INSERT INTO pooltopoolgroup VALUES ($1, $2)"))
					return NULL;

				foreach ($poolgroup['pools'] as &$pool_id) {
					$result = pg_execute('link_pool_to_poolgroup', array($pool_id, $poolgroup_id));
					if ($result === false)
						return null;
				}
			}

			return $poolgroup_id;
		}

		public function createPoolMirror(&$poolmirror) {
			if (!$this->prepareQuery("create_poolmirror", "INSERT INTO poolmirror(uuid, name, synchronized) VALUES ($1, $2, $3) RETURNING id"))
				return NULL;


			$synchronized = $poolmirror['synchronized'] ? "TRUE" : "FALSE";

			$result = pg_execute("create_poolmirror", array($poolmirror['uuid'], $poolmirror['name'], $synchronized));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			return intval($row[0]);
		}

		public function createPoolTemplate(&$pooltemplate) {
			if (!$this->prepareQuery("create_pooltemplate", "INSERT INTO pooltemplate(name, autocheck, lockcheck, growable, unbreakablelevel, metadata) VALUES ($1, $2, $3, $4, $5, $6) RETURNING id"))
				return NULL;


			$lockcheck = $pooltemplate['lockcheck'] ? "TRUE" : "FALSE";
			$growable = $pooltemplate['growable'] ? "TRUE" : "FALSE";
			$metadata = json_encode($pooltemplate['metadata'], JSON_FORCE_OBJECT);

			$result = pg_execute("create_pooltemplate", array($pooltemplate['name'], $pooltemplate['autocheck'], $lockcheck, $growable, $pooltemplate['unbreakablelevel'], $metadata));
			if ($result === false)
				return null;

			$row = pg_fetch_array($result);
			$pool_template_id = intval($row[0]);

			if ($this->createScripts($pool_template_id, $pooltemplate['scripts'], true))
				return $pool_template_id;
			else
				return null;
		}

		private function createScripts($pool_id, &$scripts, bool $pool_template) {
			if ($pool_template && !$this->prepareQuery("create_pool_template_scripts", "INSERT INTO PoolTemplateToScript(sequence, jobtype, script, pooltemplate)"))
				return NULL;
			else if (!$this->prepareQuery("create_pool_scripts", "INSERT INTO PoolToScript(sequence, jobtype, script, pool) VALUES ($1, $2, $3, $4)"))
				return NULL;

			foreach ($scripts as $script) {
				$result = pg_execute($pool_template ? "create_pool_template_scripts" : "create_pool_scripts", array($script['sequence'], $script['jobtype'], $script['script'], $pool_id));
				if ($result === false)
					return NULL;
			}

			return true;
		}

		public function deletePoolMirror($id) {
			if (!$this->prepareQuery("delete_poolmirror", "DELETE FROM poolmirror WHERE id = $1"))
				return NULL;

			$result = pg_execute("delete_poolmirror", array($id));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function deletePoolTemplate($id) {
			if (!$this->prepareQuery("delete_pooltemplate", "DELETE FROM pooltemplate WHERE id = $1"))
				return NULL;

			$result = pg_execute("delete_pooltemplate", array($id));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
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
			$pool['scripts'] = array();

			if (!$this->prepareQuery("get_script_by_pool", "SELECT sequence, jobtype, script FROM PoolToScript WHERE pool = $1 ORDER BY jobtype, sequence"))
				return NULL;

			$result = pg_execute('get_script_by_pool', array($id));
			if ($result === false)
				return null;

			while ($row = pg_fetch_assoc($result)) {
				$row['sequence'] = intval($row['sequence']);
				$row['jobtype'] = intval($row['jobtype']);
				$row['script'] = intval($row['script']);
				$pool['scripts'][] = $row;
			}

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
			$query = 'SELECT p.id FROM pool p';
			$query_params = array();

			if (isset($params['poolgroup']))
				$query .= ' INNER JOIN pooltopoolgroup ppg ON p.id = ppg.pool';

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query .= ' WHERE p.name ~* $' . count($query_params) . '::TEXT';
			}

			if (isset($params['poolgroup'])) {
				$query_params[] = $params['poolgroup'];
				if (count($query_params) > 0)
					$query .= ' AND ';
				else
					$query .= ' WHERE ';
				$query .= 'ppg.poolgroup = $' . count($query_params);
			}

			if (isset($params['mediaformat'])) {
				$query_params[] = $params['mediaformat'];
				if (count($query_params) > 0)
					$query .= ' AND ';
				else
					$query .= ' WHERE ';
				$query .= 'p.mediaformat = $' . count($query_params);
			}

			if (isset($params['uuid'])) {
				$query_params[] = $params['uuid'];
				if (count($query_params) > 1)
					$query .= ' AND ';
				else
					$query .= ' WHERE ';
				$query .= 'p.uuid = $' . count($query_params);
			}

			if (isset($params['backuppool'])) {
				$query_params[] = $params['backuppool'] ? "TRUE" : "FALSE";
				if (count($query_params) > 1)
					$query .= ' AND ';
				else
					$query .= ' WHERE ';
				$query .= 'p.backuppool = $' . count($query_params);
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
				return array(
					'query prepared' => false,
					'query executed' => false,
					'rows' => null,
					'total rows' => 0
				);

			$result = pg_execute($query_name, $query_params);
			if ($result === false)
				return array(
					'query prepared' => true,
					'query executed' => false,
					'rows' => null,
					'total rows' => 0
				);

			if (pg_num_rows($result) == 0)
				return array(
					'query prepared' => true,
					'query executed' => true,
					'rows' => array(),
					'total rows' => 0
				);

			$pools = array();
			while ($row = pg_fetch_array($result))
				$pools[] = intval($row[0]);

			return array(
				'query prepared' => true,
				'query executed' => true,
				'rows' => &$pools,
				'total rows' => count($pools)
			);
		}

		public function getPoolsByPoolMirror($id, $uuid) {
			$query = "SELECT id FROM pool WHERE poolmirror ";
			$query_params = array();
			if(isset($id)){
				$query .= " = $1";
				$query_params[] = $id;
			} else {
				$query .= " = (SELECT id FROM poolmirror WHERE uuid = $1 LIMIT 1)";
				$query_params[] = $uuid;
			}

			$query_name = "select_pool_in_poolmirror_" . md5($query);
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

		public function getPoolsByPoolgroup($user_poolgroup, &$params) {
			$query_common = " FROM pooltopoolgroup WHERE poolgroup = $1";
			$query_params = array($user_poolgroup);

			$deleted = false;
			if (isset($params['deleted'])) {
				$deleted = true;
				$query_common = " FROM pool WHERE id IN (SELECT pool" . $query_common . ")";

				if ($params['deleted'] === 'no')
					$query_common .= ' AND NOT deleted';
				else if ($params['deleted'] === 'only')
					$query_common .= ' AND deleted';
			}

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

			if ($deleted)
				$query = "SELECT id" . $query_common . " ORDER BY id";
			else
				$query = "SELECT pool" . $query_common . " ORDER BY pool";

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

		public function getPoolGroup($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!isset($id) || !is_numeric($id))
				return false;

			$query = "SELECT id, uuid, name FROM poolgroup WHERE id = $1 LIMIT 1";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_poolgroup_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($this->connect, $query_name, array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);

			$row['id'] = intval($row['id']);

			return $row;
		}

		public function getPoolGroups(&$params) {
			$query_common = " FROM poolgroup";
			$clause_where = false;
			$query_params = array();

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query_common .= ' WHERE name ~* $' . count($query_params);
				$clause_where = true;
			}

			if (isset($params['uuid'])) {
				$query_params[] = $params['uuid'];
				if ($clause_where)
					$query_common .= ' AND uuid = $' . count($query_params);
				else {
					$query_common .= ' WHERE uuid = $' . count($query_params);
					$clause_where = true;
				}
			}

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_poolgroups";

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

			$query_name = 'select_poolgroups_' . md5($query);

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
				'rows' => $rows,
				'total_rows' => $total_rows
			);
		}

		public function getPoolMirror($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!is_numeric($id))
				return false;

			$query = "SELECT id, uuid, name, synchronized FROM poolmirror WHERE id = $1";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_poolmirror_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$poolmirror = pg_fetch_assoc($result);
			$poolmirror['synchronized'] = $poolmirror['synchronized'] == 't' ? true : false;

			return $poolmirror;
		}

		public function getPoolMirrors(&$params) {
			$query_common = " FROM poolmirror";
			$query_params = array();

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_poolmirrors";

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

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query .= ' WHERE name ~* $' . count($query_params);
				$clause_where = true;
			}

			if (isset($params['synchronized'])) {
				$query_params[] = $params['synchronized'];
				if ($clause_where)
					$query .= ' AND synchronized = $' . count($query_params);
				else {
					$query .= ' WHERE synchronized = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}
			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_poolmirrors_" . md5($query);
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

		public function getPoolTemplate($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			if (!is_numeric($id))
				return false;

			$query = "SELECT id, name, autocheck, lockcheck, growable, unbreakablelevel, rewritable, metadata FROM pooltemplate WHERE id = $1";

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_pooltemplate_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($query_name, array($id));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$pooltemplate = pg_fetch_assoc($result);

			$pooltemplate['id'] = intval($pooltemplate['id']);
			$pooltemplate['lockcheck'] = $pooltemplate['lockcheck'] == 't' ? true : false;
			$pooltemplate['growable'] = $pooltemplate['growable'] == 't' ? true : false;
			$pooltemplate['rewritable'] = $pooltemplate['rewritable'] == 't' ? true : false;
			$pooltemplate['metadata'] = json_decode($pooltemplate['metadata']);
			$pooltemplate['scripts'] = array();

			if (!$this->prepareQuery("get_script_by_pool_template", "SELECT sequence, jobtype, script FROM PoolTemplateToScript WHERE pooltemplate = $1 ORDER BY jobtype, sequence"))
				return NULL;

			$result = pg_execute('get_script_by_pool_template', array($id));
			if ($result === false)
				return null;

			while ($row = pg_fetch_assoc($result)) {
				$row['sequence'] = intval($row['sequence']);
				$row['jobtype'] = intval($row['jobtype']);
				$row['script'] = intval($row['script']);
				$pooltemplate['scripts'][] = $row;
			}

			return $pooltemplate;
		}

		public function getPoolTemplates(&$params) {
			$query_common = " FROM pooltemplate";
			$query_params = array();

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_pooltemplates";

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

			if (isset($params['name'])) {
				$query_params[] = $params['name'];
				$query .= ' WHERE name ~* $' . count($query_params);
				$clause_where = true;
			}

			if (isset($params['autocheck'])) {
				$query_params[] = $params['autocheck'];
				if ($clause_where)
					$query .= ' AND autocheck = $' . count($query_params);
				else {
					$query .= ' WHERE autocheck = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['lockcheck'])) {
				$query_params[] = $params['lockcheck'];
				if ($clause_where)
					$query .= ' AND lockcheck = $' . count($query_params);
				else {
					$query .= ' WHERE lockcheck = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['rewritable'])) {
				$query_params[] = $params['rewritable'];
				if ($clause_where)
					$query .= ' AND rewritable = $' . count($query_params);
				else {
					$query .= ' WHERE rewritable = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['limit'])) {
				$query_params[] = $params['limit'];
				$query .= ' LIMIT $' . count($query_params);
			}
			if (isset($params['offset'])) {
				$query_params[] = $params['offset'];
				$query .= ' OFFSET $' . count($query_params);
			}

			$query_name = "select_pooltemplates_" . md5($query);
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

		public function getPoolTemplateByName($name) {

			if (!$this->prepareQuery("select_pooltemplate_by_name", "SELECT id FROM pooltemplate WHERE name = $1 LIMIT 1"))
				return null;

			$result = pg_execute("select_pooltemplate_by_name", array($name));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$pooltemplate = pg_fetch_array($result);
			return intval($pooltemplate[0]);
		}

		public function getPoolToPoolGroup($id) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_pooltopoolgroup', "SELECT * FROM pooltopoolgroup WHERE poolgroup = $1"))
				return null;

			$result = pg_execute($this->connect, 'select_pooltopoolgroup', array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$rows = array();
			while ($row = pg_fetch_array($result))
				$rows[] = intval($row[0]);

			return $rows;
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

			if (pg_affected_rows($result) > 0) {
				if (!$this->prepareQuery("delete_script_by_pool", "DELETE FROM PoolToScript WHERE pool = $1"))
					return null;

				$result = pg_execute("delete_script_by_pool", array($pool['id']));
				if ($result === false)
					return null;

				if ($this->createScripts($pool['id'], $pool['scripts'], false))
					return true;
				else
					return null;
			} else
				return false;
		}

		public function updatePoolMirror($poolmirror) {
			if (!$this->prepareQuery("update_poolmirror", "UPDATE poolmirror SET name = $1, synchronized = $2 WHERE id = $3"))
				return null;

			$synchronized = $poolmirror['synchronized'] ? "TRUE" : "FALSE";

			$result = pg_execute("update_poolmirror", array($poolmirror['name'], $synchronized, $poolmirror['id']));
			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function updatePoolTemplate(&$pooltemplate) {
			if (!$this->prepareQuery("update_pooltemplate", "UPDATE pooltemplate SET name = $1, autocheck = $2, lockcheck = $3, growable = $4, unbreakablelevel = $5, rewritable = $6, metadata = $7 WHERE id = $8"))
				return null;

			$lockcheck = $pooltemplate['lockcheck'] ? "TRUE" : "FALSE";
			$growable = $pooltemplate['growable'] ? "TRUE" : "FALSE";
			$rewritable = $pooltemplate['rewritable'] ? "TRUE" : "FALSE";
			$metadata = json_encode($pooltemplate['metadata']);

			$result = pg_execute("update_pooltemplate", array($pooltemplate['name'], $pooltemplate['autocheck'], $lockcheck, $growable, $pooltemplate['unbreakablelevel'], $rewritable, $metadata, $pooltemplate['id']));
			if ($result === false)
				return null;

			if (pg_affected_rows($result) > 0) {
				if (!$this->prepareQuery("delete_script_by_pool_template", "DELETE FROM PoolTemplateToScript WHERE pooltemplate = $1"))
					return null;

				$result = pg_execute("delete_script_by_pool_template", array($pooltemplate['id']));
				if ($result === false)
					return null;

				if ($this->createScripts($pooltemplate['id'], $pooltemplate['scripts'], true))
					return true;
				else
					return null;
			} else
				return false;
		}

		public function updatePoolGroup($poolgroup, $newPools) {
			if (!$this->prepareQuery("delete_poolgroup", "DELETE FROM pooltopoolgroup WHERE poolgroup = $1"))
				return null;

			if (!$this->prepareQuery("insert_poolgroup", "INSERT INTO pooltopoolgroup VALUES ($2, $1)"))
				return null;


			$result = pg_execute("delete_poolgroup", array($poolgroup));
			if ($result === false)
				return null;

			foreach ($newPools as $key)
				$result = pg_execute("insert_poolgroup", array($poolgroup, $key));
				if ($result === false)
					return null;

			return true;
		}
	}
?>
