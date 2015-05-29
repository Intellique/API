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

			if (pg_num_rows($result) == 0)
				return false;

			return pg_fetch_assoc($result);
		}
	}
?>