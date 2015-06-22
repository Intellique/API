<?php
	require_once("postgresql.php");

	class PostgresqlDBSession extends PostgresqlDB implements DB_Session {
		public function createUser(&$user) {
			if (!$this->prepareQuery("insert_user", "INSERT INTO users(login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10 ::hstore, $11, $12) RETURNING id"))
				return null;

			$metas = array();
			foreach ($user['meta'] as $key => $value)
				$metas[] = $key . '=>' . json_encode($value);
			$meta = join(',', $metas);
			unset($metas);

			$isadmin = $user['isadmin'] ? "TRUE" : "FALSE";
			$canarchive = $user['canarchive'] ? "TRUE" : "FALSE";
			$canrestore = $user['canrestore'] ? "TRUE" : "FALSE";
			$disabled = $user['disabled'] ? "TRUE" : "FALSE";

			$result = pg_execute($this->connect, "insert_user", array($user['login'], $user['password'], $user['salt'], $user['fullname'], $user['email'], $user['homedirectory'], $isadmin, $canarchive, $canrestore, $meta, $user['poolgroup'], $disabled));

			if ($result === false)
				return null;

			$row = pg_fetch_assoc($result);

			$row['id'] = intval($row['id']);

			return $row;
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

		public function getJob($id) {
			if (!isset($id))
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
			$row['interval'] = PostgresqlDB::getInteger($row['interval']);
			$row['archive'] = PostgresqlDB::getInteger($row['archive']);
			$row['backup'] = PostgresqlDB::getInteger($row['backup']);
			$row['media'] = PostgresqlDB::getInteger($row['media']);
			$row['pool'] = PostgresqlDB::getInteger($row['pool']);
			$row['host'] = intval($row['host']);
			$row['login'] = intval($row['login']);

			return $row;
		}

		public function getJobs(&$params) {
			$query = "SELECT id FROM job";
			$query_params = array();

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
				'iterator' => new PostgresqlDBResultIterator($result, array('getInteger'))
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
			if (!isset($id))
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

		public function getUser($id, $login) {
			if (!isset($id) && !isset($login))
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
			$row['meta'] = PostgresqlDB::fromHstore($row['meta']);

			return $row;
		}

		public function getUsers(&$params) {
			$query_common = " FROM users";

			$query_params = array();

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_users_id";

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

			$query_name = 'select_users_id_' . md5($query);

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

		public function updateUser(&$user) {
			if (!$this->prepareQuery("update_user", "UPDATE users SET login = $1, password = $2, salt = $3, fullname = $4, email = $5, homedirectory = $6, isadmin = $7, canarchive = $8, canrestore = $9, meta = $10 ::hstore, poolgroup = $11, disabled = $12 WHERE id = $13"))
				return null;

			$isadmin = $user['isadmin'] ? "TRUE" : "FALSE";
			$canarchive = $user['canarchive'] ? "TRUE" : "FALSE";
			$canrestore = $user['canrestore'] ? "TRUE" : "FALSE";
			$disabled = $user['disabled'] ? "TRUE" : "FALSE";
			$meta = PostgresqlDB::toHstore($user['meta']);

			$result = pg_execute($this->connect, "update_user", array($user['login'], $user['password'], $user['salt'], $user['fullname'], $user['email'], $user['homedirectory'], $isadmin, $canarchive, $canrestore, $meta, $user['poolgroup'], $disabled, $user['id']));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}
	}
?>
