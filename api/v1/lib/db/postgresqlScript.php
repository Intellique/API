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
	}
?>
