<?php
	require_once("dateTime.php");
	require_once("postgresql.php");
	require_once("postgresqlJob.php");
	require_once("postgresqlPermission.php");

	class PostgresqlDBSession extends PostgresqlDB implements DB_Session {
		use PostgresqlDBJob, PostgresqlDBPermission;

		public function createUser(&$user) {
			if (!$this->prepareQuery("insert_user", "INSERT INTO users (login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12) RETURNING id"))
				return null;

			$isadmin = $user['isadmin'] ? "TRUE" : "FALSE";
			$canarchive = $user['canarchive'] ? "TRUE" : "FALSE";
			$canrestore = $user['canrestore'] ? "TRUE" : "FALSE";
			$meta = json_encode($user['meta'], JSON_FORCE_OBJECT);
			$disabled = $user['disabled'] ? "TRUE" : "FALSE";

			$result = pg_execute($this->connect, "insert_user", array($user['login'], $user['password'], $user['salt'], $user['fullname'], $user['email'], $user['homedirectory'], $isadmin, $canarchive, $canrestore, $meta, $user['poolgroup'], $disabled));

			if ($result === false)
				return null;

			$row = pg_fetch_assoc($result);

			return intval($row['id']);
		}

		public function deleteJob($id) {
			if (!$this->prepareQuery('delete_job_by_id', "DELETE FROM job WHERE id = $1"))
				return null;

			$result = pg_execute($this->connect, 'delete_job_by_id', array($id));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function deleteUser($id) {
			if (!$this->prepareQuery('delete_user_by_id', "DELETE FROM users WHERE id = $1"))
				return null;

			$result = pg_execute($this->connect, 'delete_user_by_id', array($id));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function getApiKeyByKey($apikey) {
			if (!isset($apikey))
				return false;

			$isPrepared = $this->prepareQuery('select_id_by_apikey', "SELECT id FROM application WHERE apikey = $1 LIMIT 1");
			if (!$isPrepared)
				return null;

			$result = pg_execute($this->connect, 'select_id_by_apikey', array($apikey));
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);

			return intval($row['id']);
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

		public function getJobs(&$params) {
			$query = "SELECT id FROM job";
			$query_params = array();
			$clause_where = false;

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

			if (isset($params['login'])) {
				$query_params[] = $params['login'];
				if ($clause_where)
					$query .= ' AND login = $' . count($query_params);
				else {
					$query .= ' WHERE login = $' . count($query_params);
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

			if (isset($params['archive'])) {
				$query_params[] = $params['archive'];
				if ($clause_where)
					$query .= ' AND archive = $' . count($query_params);
				else {
					$query .= ' WHERE archive = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['media'])) {
				$query_params[] = $params['media'];
				if ($clause_where)
					$query .= ' AND media = $' . count($query_params);
				else {
					$query .= ' WHERE media = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['order_by'])) {
				$query .= ' ORDER BY ' . $params['order_by'];

				if (isset($params['order_asc']) && $params['order_asc'] === false)
					$query .= ' DESC';
			}

			$query .= ' FOR SHARE';

			$query_name = "select_jobs_id_" . md5($query);

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
				'iterator' => new PostgresqlDBResultIterator($result, array('getInteger'), false)
			);
		}

		public function getJobType() {
			if (!$this->prepareQuery('select_jobtype', "SELECT name FROM jobtype"))
				return null;

			$result = pg_execute($this->connect, 'select_jobtype', array());

			if ($result === false)
				return null;

			return pg_fetch_all_columns($result);
		}

		public function getPoolgroup($id) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_poolgroup_by_id', "SELECT id, uuid, name FROM poolgroup WHERE id = $1 LIMIT 1"))
				return null;

			$result = pg_execute($this->connect, 'select_poolgroup_by_id', array($id));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);

			$row['id'] = intval($row['id']);

			return $row;
		}

		public function getPooltopoolgroup($id) {
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

		public function getUser($id, $login, $completeInfo) {
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

			$user = array(
				'id' => intval($row['id']),
				'login' => $row['login'],
				'fullname' => $row['fullname'],
				'email' => $row['email']
			);

			if ($completeInfo) {
				$user['password'] = $row['password'];
				$user['salt'] = $row['salt'];
				$user['homedirectory'] = $row['homedirectory'];
				$user['isadmin'] = $row['isadmin'] == 't' ? true : false;
				$user['canarchive'] = $row['canarchive'] == 't' ? true : false;
				$user['canrestore'] = $row['canrestore'] == 't' ? true : false;
				$user['meta'] = json_decode($row['meta']);
				$user['poolgroup'] = PostgresqlDB::getInteger($row['poolgroup']);
				$user['disabled'] = $row['disabled'] == 't' ? true : false;
			}

			return $user;
		}

		public function getUsers(&$params) {
			$query_common = " FROM users";
			$clause_where = false;
			$query_params = array();

			if (isset($params['poolgroup'])) {
				$query_params[] = $params['poolgroup'];
				$query_common .= ' WHERE poolgroup = $' . count($query_params);
				$clause_where = true;
			}

			if (isset($params['login'])) {
				$query_params[] = $params['login'];
				if ($clause_where)
					$query_common .= ' AND login ~* $' . count($query_params);
				else {
					$query_common .= ' WHERE login ~* $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['isadmin'])) {
				$query_params[] = $params['isadmin'];
				if ($clause_where)
					$query_common .= ' AND isadmin = $' . count($query_params);
				else {
					$query_common .= ' WHERE isadmin = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['canarchive'])) {
				$query_params[] = $params['canarchive'];
				if ($clause_where)
					$query_common .= ' AND canarchive = $' . count($query_params);
				else {
					$query_common .= ' WHERE canarchive = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['canrestore'])) {
				$query_params[] = $params['canrestore'];
				if ($clause_where)
					$query_common .= ' AND canrestore = $' . count($query_params);
				else {
					$query_common .= ' WHERE canrestore = $' . count($query_params);
					$clause_where = true;
				}
			}

			if (isset($params['disabled'])) {
				$query_params[] = $params['disabled'];
				if ($clause_where)
					$query_common .= ' AND disabled = $' . count($query_params);
				else {
					$query_common .= ' WHERE disabled = $' . count($query_params);
					$clause_where = true;
				}
			}

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_users";

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

			$query_name = 'select_users_' . md5($query);

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

		public function updateJob(&$job) {
			if (!$this->prepareQuery("update_job", "UPDATE job SET name = $1, nextstart = $2, interval = $3, repetition = $4, status = $5, metadata = $6, options = $7 WHERE id = $8"))
				return null;

			$metadata = json_encode($job['metadata'], JSON_FORCE_OBJECT);
			$options = json_encode($job['options'], JSON_FORCE_OBJECT);

			$result = pg_execute($this->connect, "update_job", array($job['name'], $job['nextstart']->format(DateTime::ISO8601), $job['interval'], $job['repetition'], $job['status'], $metadata, $options, $job['id']));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function updatePoolgroup($poolgroup, $poolsToChange, $newPools) {
			if (!$this->prepareQuery("select_pool_for_update","SELECT * FROM pool WHERE id = $1"))
				return null;

			foreach($newPools as $value) {
				$result = pg_execute("select_pool_for_update", array($value));
					if ($result === false)
						return null;
					if (pg_num_rows($result) == 0)
						return false;
			}

			if (!$this->prepareQuery("update_poolgroup","UPDATE pooltopoolgroup SET pool = $1 WHERE poolgroup = $2 AND pool = $3"))
				return null;

			if (!$this->prepareQuery("insert_poolgroup","INSERT INTO pooltopoolgroup VALUES ($2, $1)"))
				return null;

			if (count($newPools) < count($poolsToChange)) {
				if (!$this->prepareQuery("delete_poolgroup","DELETE FROM pooltopoolgroup"))
					return null;

				$result = pg_execute("delete_poolgroup", array());
					if ($result === false)
						return null;

				foreach($newPools as $value) {
					$result = pg_execute("insert_poolgroup", array($poolgroup, $value));
					if ($result === false)
						return null;
				}
				return true;
			}

			foreach($newPools as $key => $value) {
				if (($key + 1) > count($poolsToChange)) {
					$result = pg_execute("insert_poolgroup", array($poolgroup, $value));
					if ($result === false)
						return null;
				} else {
					$result = pg_execute("update_poolgroup", array($value, $poolgroup, $poolsToChange[$key]));
					if ($result === false)
						return null;
				}
			}
			return true;
		}

		public function updateUser(&$user) {
			if (!$this->prepareQuery("update_user", "UPDATE users SET login = $1, password = $2, salt = $3, fullname = $4, email = $5, homedirectory = $6, isadmin = $7, canarchive = $8, canrestore = $9, meta = $10, poolgroup = $11, disabled = $12 WHERE id = $13"))
				return null;

			$isadmin = $user['isadmin'] ? "TRUE" : "FALSE";
			$canarchive = $user['canarchive'] ? "TRUE" : "FALSE";
			$canrestore = $user['canrestore'] ? "TRUE" : "FALSE";
			$disabled = $user['disabled'] ? "TRUE" : "FALSE";
			$meta = json_encode($user['meta'], JSON_FORCE_OBJECT);

			$result = pg_execute($this->connect, "update_user", array($user['login'], $user['password'], $user['salt'], $user['fullname'], $user['email'], $user['homedirectory'], $isadmin, $canarchive, $canrestore, $meta, $user['poolgroup'], $disabled, $user['id']));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>
