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

			return $row;
		}

		public function getUsers() {
			$isPrepared = $this->prepareQuery('select_users_id', 'SELECT id FROM users');
			if (!$isPrepared)
				return null;

			$result = pg_execute($this->connect, 'select_users_id', array());

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$ids = array();

			while ($row = pg_fetch_array($result)) {
				$ids[] = intval($row[0]);
			}

			return $ids;
		}
	}
?>
