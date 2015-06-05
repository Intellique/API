<?php
	require_once("postgresql.php");

	class PostgresqlDBSession extends PostgresqlDB implements DB_Session {
		public function getUser($id, $login) {
			if (!isset($id) && !isset($login))
				return false;

			if (isset($id)) {
				$isPrepared = $this->prepareQuery('select_user_by_id', 'SELECT id, login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled FROM users WHERE id = $1 LIMIT 1');
				if (!$isPrepared)
					return false;

				$result = pg_execute($this->connect, 'select_user_by_id', array($id));
			} else {
				$isPrepared = $this->prepareQuery('select_user_by_login', 'SELECT id, login, password, salt, fullname, email, homedirectory, isadmin, canarchive, canrestore, meta, poolgroup, disabled FROM users WHERE login = $1 LIMIT 1');
				if (!$isPrepared)
					return false;

				$result = pg_execute($this->connect, 'select_user_by_login', array($login));
			}

			if ($result === false || pg_num_rows($result) == 0)
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
				return false;

			$result = pg_execute($this->connect, 'select_users_id', array());

			if ($result === false)
				return false;

			$ids = array();

			while ($row = pg_fetch_array($result)) {
				$ids[] = intval($row[0]);
			}

			return $ids;
		}
	}
?>
