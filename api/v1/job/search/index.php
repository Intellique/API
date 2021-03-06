<?php
/**
 * \addtogroup search
 * \page search
 * \subpage jobsearch
 * \section Search_job Searching jobs
 * To search jobs and then to get jobs ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/job/search \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name      | string  | search a job specifying its name                                                    |                                 |
 * | pool      | integer | search a job specifying its pool                                                    |                                 |
 * | login     | integer | search a job specifying the login number of the user which planned it               |                                 |
 * | type      | integer | search a job specifying its type                                                    |                                 |
 * | status    | csv     | search a job specifying its status, allow comma separated values                    |                                 |
 * | archive   | integer | search a job specifying its archive                                                 |                                 |
 * | media     | integer | search a job specifying its media                                                   |                                 |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'size', 'name', 'type', 'owner', 'groups' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first six parameters. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query successful

 *     \verbatim Jobs ids list is returned
{
   {
   "message":"Query successful","jobs_id" => [2], "total_rows" => 1
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Jobs not found
 *   - \b 500 Query failure
 */
	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	function checkPermissions($jobId, $returnJob) {
		global $dbDriver;

		$job = $dbDriver->getJob($jobId);

		if ($job === null || $job === false)
			return $returnJob ? array('failure' => $job === null, 'job' => null, 'permission' => false, 'found' => false) : $job;

		$ok = $_SESSION['user']['isadmin'] || $job['login'] == $_SESSION['user']['id'];
		$failed = false;

		if (!$ok && isset($job['archive'])) {
			$checkArchivePermission = $dbDriver->checkArchivePermission($job['archive'], $_SESSION['user']['id']);
			if ($checkArchivePermission === null) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkArchivePermission(%s, %s)', $job['archive'], $_SESSION['user']['id']), $_SESSION['user']['id']);
				$failed = true;
			} elseif ($checkArchivePermission === true)
				$ok = true;
		}

		if (!$failed && !$ok && isset($job['pool'])) {
			$checkPoolPermission = $dbDriver->checkPoolPermission($job['pool'], $_SESSION['user']['id']);
			if ($checkPoolPermission === null) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkPoolPermission(%s, %s)', $job['pool'], $_SESSION['user']['id']), $_SESSION['user']['id']);
				$failed = true;
			} elseif ($checkPoolPermission === true)
				$ok = true;
		}

		if ($failed)
			return $returnJob ? array('failure' => true, 'job' => null, 'permission' => false, 'found' => false) : null;

		if ($returnJob)
			return $ok ? array('failure' => false, 'job' => $job, 'permission' => true, 'found' => true) : array('failure' => false, 'job' => $job, 'permission' => false, 'found' => true);

		return $ok;
	}

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			$params = array();
			$ok = true;

			if (isset($_GET['name']))
				$params['name'] = $_GET['name'];

			if (isset($_GET['pool'])) {
				$pool = filter_var($_GET['pool'], FILTER_VALIDATE_INT);
				if ($pool === false)
					$ok = false;
				else
					$params['pool'] = $pool;
			}

			if (isset($_GET['login'])) {
				$login = filter_var($_GET['login'], FILTER_VALIDATE_INT);
				if ($login === false)
					$ok = false;
				else
					$params['login'] = $login;
			}

			if (isset($_GET['type'])) {
				$type = filter_var($_GET['type'], FILTER_VALIDATE_INT);
				if ($type === false)
					$ok = false;
				else
					$params['type'] = $type;
			}

			if (isset($_GET['status'])) {
				$statuses = array('disable', 'error', 'finished', 'pause', 'running', 'scheduled', 'stopped', 'waiting');
				$params['status'] = array();

				foreach (explode(',', $_GET['status']) as $status) {
					if (in_array($status, $statuses))
						$params['status'][] = $status;
					else {
						$ok = false;
						break;
					}
				}
			}

			if (isset($_GET['archive'])) {
				$archive = filter_var($_GET['archive'], FILTER_VALIDATE_INT);
				if ($archive === false)
					$ok = false;
				else
					$params['archive'] = $archive;
			}

			if (isset($_GET['media'])) {
				$media = filter_var($_GET['media'], FILTER_VALIDATE_INT);
				if ($media === false)
					$ok = false;
				else
					$params['media'] = $media;
			}

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'name', 'nextstart', 'status', 'update')) !== false)
					$params['order_by'] = $_GET['order_by'];
				else
					$ok = false;

				if (isset($_GET['order_asc'])) {
					$is_asc = filter_var($_GET['order_asc'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
					if ($is_asc !== null)
						$params['order_asc'] = $is_asc;
					else
						$ok = false;
				}
			}

			if (isset($_GET['limit'])) {
				$limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 1)));
				if ($limit !== false)
					$params['limit'] = $limit;
				else
					$ok = false;
			}

			if (isset($_GET['offset'])) {
				$offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 0)));
				if ($offset !== false)
					$params['offset'] = $offset;
				else
					$ok = false;
			} else
				$offset = 0;

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$dbDriver->startTransaction();

			$jobs = $dbDriver->getJobs($params);
			if ($jobs['query_prepared'] === false) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/job/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobs(%s)', var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'jobs_id' => array(),
					'total_rows' => 0
				));
			} elseif ($jobs['query_executed'] === false) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/job/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobs(%s)', var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'jobs_id' => array(),
					'total_rows' => 0
				));
			}

			$iRow = 0;
			$jobsId = array();
			$iter = $jobs['iterator'];

			while ($iRow < $offset && $iter->hasNext()) {
				$result = $iter->next();
				if (checkPermissions($result->getValue(0), false))
					$iRow++;
			}

			while ((($limit !== null && $iRow < $offset + $limit) || $limit === null) && $iter->hasNext()) {
				$result = $iter->next();
				$jobId = $result->getValue(0);

				if (checkPermissions($jobId, false)) {
					$jobsId[] = $jobId;
					$iRow++;
				}
			}

			while ($iter->hasNext()) {
				$result = $iter->next();
				if (checkPermissions($result->getValue(0), false))
					$iRow++;
			}

			if (count($jobsId) === 0) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/job/search => User %s cannot get the job ids list', $_SESSION['user']['login']), $_SESSION['user']['id']);
				httpResponse(404, array('message' => 'Jobs not found',));
			}

			$dbDriver->cancelTransaction();

			httpResponse(200, array(
				'message' => 'Query successful',
				'jobs_id' => $jobsId,
				'total_rows' => $iRow
			));
			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
		}
?>
