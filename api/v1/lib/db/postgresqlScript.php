<?php
	require_once("dbScript.php");

	trait PostgresqlDBScript {
		public function getScriptById($id) {
			if (!$this->prepareQuery("get_script_by_id", "SELECT name, description, path, type FROM script WHERE id = $1 LIMIT 1"))
				return NULL;
			$result = pg_execute("get_script_by_id", array($id));
			if ($result === false)
				return NULL;
			if (pg_num_rows($result) == 0)
				return false;

			return pg_fetch_assoc($result);
		}

		public function getScripts() {
			if (!$this->prepareQuery("get_scripts", "SELECT name, description, path, type FROM script ORDER BY id"))
				return NULL;
			$result = pg_execute("get_scripts", array());
			$script = array();
			while ($row = pg_fetch_assoc($result)) {
				$row['id'] = intval($row['id']);
				$script[] = $row;
			}
			return $script;
		}

		public function getScriptsByPool($id) {
			if (!$this->prepareQuery("get_scripts_by_pool", "SELECT name, description, path, type FROM script AS s, scripts AS ps WHERE ps.script = s.id AND ps.pool = $1"))
				return NULL;
			$result = pg_execute("get_scripts_by_pool", array($id));
			$script = array();
			while ($row = pg_fetch_assoc($result)) {
				$row['ps.id'] = intval($row['ps.id']);
				$script[] = $row;
			}
			return $script;
		}

		public function scriptExist(&$params) {
			if (!$this->prepareQuery("script_exist", "SELECT MAX(sequence) AS sequence FROM scripts WHERE script = $1 AND pool = $2 AND jobtype = $3"))
				return array(
					'query' => "script_exist",
					'query_prepared' => false,
					'query_executed' => false,
				);
			$result = pg_execute("script_exist", array($params['script_id'], $params['pool'], $params['jobtype']));
			if ($result === false)
				return array(
					'query' => "script_exist",
					'query_prepared' => true,
					'query_executed' => false,
				);
			$result = pg_fetch_assoc($result);
			return $result['sequence'];
		}

		public function addScript(&$params) {
			$result = $this->scriptExist($params);
			if ($result == null) {
				if (!$this->prepareQuery("script_sequence", "SELECT MAX(sequence) AS sequence FROM scripts WHERE jobtype = $1 AND pool = $2"))
					return array(
						'query' => "script_sequence",
						'query_prepared' => false,
						'query_executed' => false,
					);
				$result = pg_execute("script_sequence", array($params['jobtype'], $params['pool']));
				if ($result === false)
					return array(
						'query' => "script_sequence",
						'query_prepared' => true,
						'query_executed' => false,
					);
				$result =  pg_fetch_assoc($result);
				if (!$this->prepareQuery("add_script", "INSERT INTO scripts VALUES (DEFAULT, $1, $2, $3, $4)"))
					return array(
						'query' => "add_script",
						'query_prepared' => false,
						'query_executed' => false,
					);
				$result = pg_execute("add_script",array($result['sequence']+1, $params['jobtype'], $params['script_id'], $params['pool']));
				if ($result === false)
					return array(
						'query' => "add_script",
						'query_prepared' => true,
						'query_executed' => false,
					);
				return array(
					'query' => "add_script",
					'query_prepared' => true,
					'query_executed' => true,
					'message' => "script added",
					'action' => true
				);
			} else
				return array(
					'query' => "script_exist",
					'query_prepared' => true,
					'query_executed' => true,
					'message' => "script exist",
					'action' => false
				);
		}

		public function deleteScript(&$params) {
			$result = $this->scriptExist($params);
			if ($result != null) {
				if (!$this->prepareQuery("delete_script", "DELETE FROM scripts WHERE script = $1 AND pool = $2 AND jobtype = $3"))
					return array(
						'query' => "delete_script",
						'query_prepared' => false,
						'query_executed' => false,
					);
				$result = pg_execute("delete_script", array($params['script_id'], $params['pool'], $params['jobtype']));
				if ($result === false)
					return array(
						'query' => "delete_script",
						'query_prepared' => true,
						'query_executed' => false,
					);

				$this->prepareQuery("update_scripts", "WITH new_rank AS
				(SELECT *, rank() OVER (PARTITION BY jobtype ORDER BY sequence ASC) AS rank FROM scripts WHERE jobtype = $1 AND pool = $2)
				UPDATE scripts AS s
				SET sequence = nr.rank - 1
				FROM new_rank nr
				WHERE s.id = nr.id");

				pg_execute("update_scripts", array($params['jobtype'], $params['pool']));

				return array(
					'query' => "delete_script",
					'query_prepared' => true,
					'query_executed' => true,
					'message' => "script deleted",
					'action' => true
				);
			} else
				return array(
					'query' => "script_exist",
					'query_prepared' => true,
					'query_executed' => true,
					'message' => "script don't exist",
					'action' => false
				);
			}
		}
?>
