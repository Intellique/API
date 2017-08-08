<?php
	require_once("dbUser.php");

	trait PostgresqlDBUser {
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

		public function deleteUser($id) {
			if (!$this->prepareQuery('delete_user_by_id', "DELETE FROM users WHERE id = $1"))
				return null;

			$result = pg_execute($this->connect, 'delete_user_by_id', array($id));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
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

		public function getUserById($id, $rowLock = DB::DB_ROW_LOCK_NONE) {
			$query = 'SELECT id, login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled FROM users WHERE id = $1 LIMIT 1';

			switch ($rowLock) {
				case DB::DB_ROW_LOCK_SHARE:
					$query .= ' FOR SHARE';
					break;

				case DB::DB_ROW_LOCK_UPDATE:
					$query .= ' FOR UPDATE';
					break;
			}

			$query_name = "select_user_by_id_" . md5($query);

			if (!$this->prepareQuery($query_name, $query))
				return null;

			$result = pg_execute($this->connect, 'select_user_by_login', array($login));
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
