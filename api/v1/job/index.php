<?php
/**
 * \addtogroup job
 * \page job
 * \section Delete_job Job deletion
 * To delete a job,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/job/ \endverbatim
 * \param id : job id
 * \return HTTP status codes :
 *   - \b 200 Deletion successful
 *   - \b 400 Job id is required
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Job not found
 *   - \b 500 Query failure
 *
 * \section Job_Info Job information
 * To get job information,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/job/ \endverbatim
 * \param id : job id
 * \return HTTP status codes :
 *   - \b 200 Query successful
 *     \verbatim Job information is returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Job not found
 *   - \b 500 Query failure
 *
 * \section Jobs_id Jobs ids
 * To get jobs ids list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/job/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name   |  Type   |                                  Description                                        |                               Constraint                               |
 * | :------: | :-----: | :---------------------------------------------------------------------------------: | :--------------------------------------------------------------------: |
 * | order_by | enum    |order by column                                                                      | single value from : 'id', 'name', 'nextstart', 'status', 'update'      |
 * | order_asc| boolean |\b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing|                        |
 * | limit    | integer |specifies the maximum number of rows to return                                       | limit > 0                                                              |
 * | offset   | integer |specifies the number of rows to skip before starting to return rows                  | offset >= 0                                                            |
 *
 * \warning <b>To get multiple jobs ids list do not pass an id or ids as parameter</b>
 * \return HTTP status codes :
 *   - \b 200 Query successful
 *     \verbatim Jobs ids list is returned \endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 *
 * \section Update_job Job update
 * To update a job,
 * use \b PUT method
 * \verbatim path : /storiqone-backend/api/v1/job/ \endverbatim
 * \param job : JSON encoded object
 * \li \c id (integer) : job id
 * \li \c name (string) : job name
 * \li \c nextstart (timestamp(0) with time zone) : job nextstart
 * \li \c interval (integer) : job interval
 * \li \c repetition (integer) : job repetition
 * \li \c status (string) : job status
 * \li \c metadata (JSON) : job metadata
 * \li \c options (JSON) : job options
 * \return HTTP status codes :
 *   - \b 200 Job updated successfully
 *   - \b 400 Bad request - Either ; job information is required or incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 *
 * \note \ref Date "Date time formats supported"
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("dbSession.php");

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
		case 'DELETE':
			checkConnected();

			if (!isset($_GET['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'DELETE api/v1/job => Trying to delete a job without specifying job id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Job id is required'));
			}

			$dbDriver->startTransaction();

			$job = checkPermissions($_GET['id'], true);

			if ($job['job'] === null || $job['permission'] === false)
				$dbDriver->cancelTransaction();

			if ($job['failure']) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/job => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkPermissions(%s, %s)', $_GET['id'], "true"), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'job' => array()
				));
			} elseif (!$job['found'])
				httpResponse(404, array(
					'message' => 'Job not found',
					'job' => array()
				));
			elseif ($job['permission'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'DELETE api/v1/job => A non-admin user tried to delete a job', $_SESSION['user']['id']);
				httpResponse(403, array(
					'message' => 'Permission denied',
					'job' => array()
				));
			}

			$delete_status = $dbDriver->deleteJob($_GET['id']);

			if ($delete_status)
				$dbDriver->finishTransaction();
			else
				$dbDriver->cancelTransaction();

			if ($delete_status === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/job => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('deleteJob(%s)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($delete_status === false)
				httpResponse(404, array('message' => 'Job not found'));
			else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/job => Job %s deleted', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Deletion successful'));
			}

			break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				$dbDriver->startTransaction();

				$job = checkPermissions($_GET['id'], true);

				$dbDriver->cancelTransaction();

				if ($job['failure']) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/job => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkPermissions(%s, %s)', $_GET['id'], true), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'job' => array()
					));
				} elseif (!$job['found'])
					httpResponse(404, array(
						'message' => 'Job not found',
						'job' => array()
					));
				elseif ($job['permission'] === false)
					httpResponse(403, array(
						'message' => 'Permission denied',
						'job' => array()
					));
				else {
					$job = $job['job'];
					$job['nextstart'] = $job['nextstart']->format(DateTime::ISO8601);
					$job['update'] = $job['update']->format(DateTime::ISO8601);
					httpResponse(200, array(
						'message' => 'Query successful',
						'job' => $job
					));
				}
			} else {
				$params = array();
				$ok = true;

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

				$limit = null;
				if (isset($_GET['limit'])) {
					if (is_numeric($_GET['limit']) && $_GET['limit'] > 0)
						$limit = intval($_GET['limit']);
					else
						$ok = false;
				}

				$offset = 0;
				if (isset($_GET['offset'])) {
					if (is_numeric($_GET['offset']) && $_GET['offset'] >= 0)
						$offset = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$dbDriver->startTransaction();

				$jobs = $dbDriver->getJobs($params);

				if ($jobs['query_executed'] == false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/job => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobs(%s)', $params), $_SESSION['user']['id']);
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

				$dbDriver->cancelTransaction();

				httpResponse(200, array(
					'message' => 'Query successful',
					'jobs_id' => $jobsId,
					'total_rows' => $iRow
				));
			}
		break;

		case 'PUT':
			checkConnected();

			$job = httpParseInput();

			if (!isset($job) || !isset($job['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/job => Trying to update a job without specifying job informations', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Job information is required'));
			}

			// id
			$check_job = $dbDriver->getJob($job['id']);

			if ($check_job === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/job => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJob(%s)', $job['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_job === false)
				httpResponse(400, array('message' => 'Incorrect input'));

			if (!$_SESSION['user']['isadmin'] && ($_SESSION['user']['id'] != $check_job['login'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/job => A non-admin user tried to update a job', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$ok = true;

			// name
			if ($ok)
				$ok = isset($job['name']) && is_string($job['name']);
			if ($ok) {
				if (strlen($job['name']) > 255)
					$ok = false;
			}

			// nextstart
			if ($ok) {
				$ok = isset($job['nextstart']);
				$job['nextstart'] = dateTimeParse($job['nextstart']);
				if ($job['nextstart'] === null)
					$ok = false;
			}

			// interval
			if ($ok)
				$ok = array_key_exists('interval', $job) && (is_int($job['interval']) || is_null($job['interval']));
			if ($ok && is_int($job['interval']))
				$ok = $job['interval'] > 0;

			// repetition
			if ($ok)
				$ok = isset($job['repetition']) && is_int($job['repetition']);

			// status
			if ($ok)
				$ok = isset($job['status']) && is_string($job['status']);

			// metadata
			if ($ok)
				$ok = isset($job['metadata']) && is_array($job['metadata']);

			// options
			if ($ok)
				$ok = isset($job['options']) && is_array($job['options']);

			// gestion des erreurs
			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$result = $dbDriver->updateJob($job);

			if ($result) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/job => Job %s updated',$job['id']) , $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Job updated successfully'));
			}
			else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/job => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, vprintf('updateJob(%s)', $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_ALL_METHODS & ~HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
