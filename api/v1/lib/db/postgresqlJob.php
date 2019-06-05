<?php
	require_once("dbJob.php");

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

		public function deleteJob($id) {
			if (!$this->prepareQuery('delete_job_by_id', "DELETE FROM job WHERE id = $1"))
				return null;

			$result = pg_execute($this->connect, 'delete_job_by_id', array($id));

			if ($result === false)
				return null;

			return pg_affected_rows($result) > 0;
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

		public function getJob($id) {
			if (!isset($id) || !is_numeric($id))
				return false;

			if (!$this->prepareQuery('select_job_by_id', "SELECT j.id, j.name, jt.name AS type, TRIM(BOTH '\"' FROM TO_JSON(j.nextstart)::TEXT) AS nextstart, EXTRACT(EPOCH FROM j.interval) AS interval, j.repetition, j.status, TRIM(BOTH '\"' FROM TO_JSON(j.update)::TEXT) AS update, j.archive, j.backup, j.media, j.pool, j.host, j.login, j.metadata, j.options FROM job j INNER JOIN jobtype jt ON j.type = jt.id WHERE j.id = $1 LIMIT 1 FOR UPDATE"))
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

			if (!$this->prepareQuery('select_job_run_by_job', 'SELECT id, numrun, starttime, endtime, status, step, done, exitcode, stoppedbyuser FROM jobrun WHERE job = $1 ORDER BY id'))
				return $row;

			$result = pg_execute($this->connect, 'select_job_run_by_job', array($id));
			if ($result === false)
				return $row;

			$row['runs'] = array();
			while ($jr = pg_fetch_assoc($result)) {
				$jr['id'] = intval($jr['id']);
				$jr['numrun'] = intval($jr['numrun']);
				$jr['starttime'] = dateTimeParse($jr['starttime']);
				$jr['endtime'] = dateTimeParse($jr['endtime']);
				$jr['done'] = floatval($jr['done']);
				$jr['exitcode'] = intval($jr['exitcode']);
				$jr['stoppedbyuser'] = PostgresqlDB::getBoolean($jr['stoppedbyuser']);
				$row['runs'][] = $jr;
			}

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

			if (isset($params['status'])) {
				if ($clause_where)
					$query .= ' AND status IN (';
				else {
					$query .= ' WHERE status IN (';
					$clause_where = true;
				}

				for ($i = 0; $i < count($params['status']); $i++) {
					$query_params[] = $params['status'][$i];
					$query .= '$' . count($query_params);
					if ($i + 1 < count($params['status']))
						$query .= ',';
				}

				$query .= ')';
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

		public function getJobTypes() {
			if (!$this->prepareQuery("select_job_types", "SELECT name FROM jobtype"))
				return null;

			$result = pg_execute("select_job_types", array());
			if ($result === false)
				return null;

			if (pg_num_rows($result) == 0)
				return false;

			$jobtypes = pg_fetch_assoc($result);

			return $jobtypes;
		}

		public function getSelectedFile($path) {
			$path = rtrim($path,'/');
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
	}
?>
