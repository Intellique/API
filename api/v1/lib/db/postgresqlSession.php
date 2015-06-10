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

		public function deleteUser($id) {
			if (!$this->prepareQuery('delete_user_by_id', 'DELETE FROM users WHERE id = $1'))
				return null;

			$result = pg_execute($this->connect, 'delete_user_by_id', array($id));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
		}

		public function getPoolgroup($id) {
			if (!isset($id))
				return false;

			if (!$this->prepareQuery('select_poolgroup_by_id', 'SELECT id, uuid, name FROM poolgroup WHERE id = $1 LIMIT 1'))
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
				$isPrepared = $this->prepareQuery('select_user_by_id', 'SELECT id, login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled FROM users WHERE id = $1 LIMIT 1');
				if (!$isPrepared)
					return null;

				$result = pg_execute($this->connect, 'select_user_by_id', array($id));
			} else {
				$isPrepared = $this->prepareQuery('select_user_by_login', 'SELECT id, login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled FROM users WHERE login = $1 LIMIT 1');
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
			$row['poolgroup'] = intval($row['poolgroup']);
			$row['disabled'] = $row['disabled'] == 't' ? true : false;

			$metas = array();
			$list_metas = split(', ', $row['meta']);
			foreach ($list_metas as $value) {
				list($key, $val) = split('=>', $value);
				$key = substr($key, 1, strlen($key) - 2);
				$val = substr($val, 1, strlen($val) - 2);
				$metas[$key] = json_decode($val, true);
			}
			$row['meta'] = $metas;

			return $row;
		}

		public function getUsers(&$params) {
			$query_common = " FROM users";

			$query_params = array();

			$total_rows = 0;
			if (isset($params['limit']) or isset($params['offset'])) {
				$query = "SELECT COUNT(*)" . $query_common;
				$query_name = "select_total_users_id";

				if (!$this->prepareQuery($this->connect, $query_name, $query))
					return array(
						'query' => $query,
						'query name' => $query_name,
						'query prepared' => false,
						'query executed' => false,
						'rows' => array(),
						'total_rows' => 0
					);

				$result = pg_execute($this->connect, $query_name, $query_params);
				if ($result === false)
					return array(
						'query' => $query,
						'query name' => $query_name,
						'query prepared' => true,
						'query executed' => false,
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

			if (!$this->prepareQuery($this->connect, $query_name, $query))
				return array(
					'query' => $query,
					'query name' => $query_name,
					'query prepared' => false,
					'query executed' => false,
					'rows' => array(),
					'total_rows' => $total_rows
				);

			$result = pg_execute($this->connect, $query_name, $query_params);
			if ($result === false)
				return array(
					'query' => $query,
					'query name' => $query_name,
					'query prepared' => true,
					'query executed' => false,
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
				'query name' => $query_name,
				'query prepared' => true,
				'query executed' => true,
				'rows' => $rows,
				'total_rows' => $total_rows
			);
		}
	}
?>
