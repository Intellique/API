<?php
	trait PostgresqlDBJob {
		public function createJob(&$job) {
			if (!$this->prepareQuery("insert_job", "INSERT INTO job (name, type, nextstart, interval, repetition, archive, backup, media, pool, host, login, metadata, options) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13) RETURNING id"))
				return null;

			$job['nextstart'] = $job['nextstart']->format(DateTime::ISO8601);
			if (!isset($job['repetition']))
				$job['repetition'] = 1;
			$job['metadata'] = json_encode($job['metadata'], JSON_FORCE_OBJECT);
			$job['options'] = json_encode($job['options'], JSON_FORCE_OBJECT);

			$result = pg_execute($this->connect, "insert_job", array($job['name'], $job['type'], $job['nextstart'], $job['interval'], $job['repetition'], $job['archive'], $job['backup'], $job['media'], $job['pool'], $job['host'], $job['login'], $job['metadata'], $job['options']));

			if ($result === false)
				return null;

			$row = pg_fetch_assoc($result);

			return intval($row['id']);
		}

		public function getHost($name) {
			if (!isset($name))
				return false;

			if (!$this->prepareQuery('select_host_by_name', "SELECT id FROM host WHERE name = $1 LIMIT 1"))
				return null;

			$result = pg_execute($this->connect, 'select_host_by_name', array($name));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);
			return intval($row['id']);
		}

		public function getJobTypeId($name) {
			if (!isset($name))
				return false;

			if (!$this->prepareQuery('select_jobtype_by_name', "SELECT id FROM jobtype WHERE name = $1 LIMIT 1"))
				return null;

			$result = pg_execute($this->connect, 'select_jobtype_by_name', array($name));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$row = pg_fetch_assoc($result);
			return intval($row['id']);
		}

		public function getSelectedFile($path) {
			if (!$this->prepareQuery('select_selectedfile_by_path', "SELECT id FROM selectedfile WHERE path = $1 LIMIT 1"))
				return null;

			$result = pg_execute($this->connect, 'select_selectedfile_by_path', array($path));

			if ($result === false)
				return null;

			if (pg_num_rows($result) == 1) {
				$row = pg_fetch_assoc($result);
				return intval($row['id']);
			}

			if (!$this->prepareQuery("insert_selectedfile", "INSERT INTO selectedfile (path) VALUES ($1) RETURNING id"))
				return null;

			$result = pg_execute($this->connect, "insert_selectedfile", array($path));

			if ($result === false)
				return null;

			$row = pg_fetch_assoc($result);

			return intval($row['id']);
		}

		public function insertIntoRestoreTo($jobId, $path) {
			if (!$this->prepareQuery("insert_restoreto", "INSERT INTO restoreto (path, job) VALUES ($1, $2)"))
				return null;

			$result = pg_execute($this->connect, "insert_restoreto", array($path, $jobId));

			if ($result === false)
				return null;

			return true;
		}

		public function linkJobToSelectedfile($jobId, $selectedfileId) {
			if (!$this->prepareQuery("insert_jobtoselectedfile", "INSERT INTO jobtoselectedfile (job, selectedfile) VALUES ($1, $2)"))
				return null;

			$result = pg_execute($this->connect, "insert_jobtoselectedfile", array($jobId, $selectedfileId));

			if ($result === false)
				return null;

			return true;
		}
	}
?>