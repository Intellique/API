<?php
/**
 * \addtogroup job
 * \section Delete_job Job deletion
 * To delete a job,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/job/ \endverbatim
 * \param id : job id
 * \return HTTP status codes :
 *   - \b 200 Deletion successfull
 *   - \b 400 Job id required
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
 *   - \b 200 Query successfull
 *     \verbatim Job information is returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Job not found
 *   - \b 500 Query failure
 *
 * \section Jobs_id Jobs id
 * To get jobs id list,
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
 * \warning To get jobs id list do not pass an id or ids as parameter
 * \return HTTP status codes :
 *   - \b 200 Query successfull
 *     \verbatim Jobs id list is returned \endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbSession.php");

	function checkPermissions($jobId, $returnJob) {
		global $dbDriver;

		$job = $dbDriver->getJob($jobId);

		if ($job === null || $job === false)
			return $returnJob ? array('failure' => $job === null, 'job' => null, 'permission' => false, 'found' => false) : $job;

		$ok = $_SESSION['user']['isadmin'] || $job['login'] == $_SESSION['user']['id'];
		$failed = false;

		if (!$ok && isset($job['archive'])) {
			$checkArchivePermission = $dbDriver->checkArchivePermission($job['archive'], $_SESSION['user']['id']);
			if ($checkArchivePermission === null)
				$failed = true;
			elseif ($checkArchivePermission === true)
				$ok = true;
		}

		if (!$failed && !$ok && isset($job['pool'])) {
			$checkPoolPermission = $dbDriver->checkPoolPermission($job['pool'], $_SESSION['user']['id']);
			if ($checkPoolPermission === null)
				$failed = true;
			elseif ($checkPoolPermission === true)
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
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (!isset($_GET['id'])) {
				http_response_code(400);
				echo json_encode(array('message' => 'Job id required'));
				exit;
			}

			$dbDriver->startTransaction();

			$job = checkPermissions($_GET['id'], true);

			if ($job['job'] === null || $job['permission'] === false)
				$dbDriver->cancelTransaction();

			if ($job['failure']) {
				http_response_code(500);
				echo json_encode(array(
					'message' => 'Query failure',
					'job' => array()
				));
				exit;
			} elseif (!$job['found']) {
				http_response_code(404);
				echo json_encode(array(
					'message' => 'Job not found',
					'job' => array()
				));
				exit;
			} elseif ($job['permission'] === false) {
				http_response_code(403);
				echo json_encode(array(
					'message' => 'Permission denied',
					'job' => array(),
					'debug' => $job
				));
				exit;
			}

			$delete_status = $dbDriver->deleteJob($_GET['id']);

			if ($delete_status)
				$dbDriver->finishTransaction();
			else
				$dbDriver->cancelTransaction();

			if ($delete_status === null) {
				http_response_code(500);
				echo json_encode(array('message' => 'Query failure'));
			} elseif ($delete_status === false) {
				http_response_code(404);
				echo json_encode(array('message' => 'Job not found'));
			} else {
				http_response_code(200);
				echo json_encode(array('message' => 'Deletion successfull'));
			}

			break;

		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (isset($_GET['id'])) {
				$dbDriver->startTransaction();

				$job = checkPermissions($_GET['id'], true);

				$dbDriver->cancelTransaction();

				if ($job['failure']) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'job' => array()
					));
				} elseif (!$job['found']) {
					http_response_code(404);
					echo json_encode(array(
						'message' => 'Job not found',
						'job' => array()
					));
				} elseif ($job['permission'] === false) {
					http_response_code(403);
					echo json_encode(array(
						'message' => 'Permission denied',
						'job' => array()
					));
				} else {
					http_response_code(200);
					echo json_encode(array(
						'message' => 'Query successfull',
						'job' => $job['job']
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
					if (ctype_digit($_GET['limit']) && $_GET['limit'] > 0)
						$limit = intval($_GET['limit']);
					else
						$ok = false;
				}

				$offset = 0;
				if (isset($_GET['offset'])) {
					if (ctype_digit($_GET['offset']) && $_GET['offset'] >= 0)
						$offset = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok) {
					http_response_code(400);
					echo json_encode(array('message' => 'Incorrect input'));
					exit;
				}

				$dbDriver->startTransaction();

				$jobs = $dbDriver->getJobs($params);

				if ($jobs['query_executed'] == false) {
					$dbDriver->cancelTransaction();

					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'jobs_id' => array(),
						'total_rows' => 0
					));
					exit;
				}

				$iRow = 0;
				$jobsId = array();
				$iter = $jobs['iterator'];

				while ($iRow < $offset && $iter->hasNext()) {
					$result = $iter->next();
					if (checkPermissions($result->getValue(), false))
						$iRow++;
				}

				while ((($limit !== null && $iRow < $offset + $limit) || $limit === null) && $iter->hasNext()) {
					$result = $iter->next();
					$jobId = $result->getValue();

					if (checkPermissions($jobId, false)) {
						$jobsId[] = $jobId;
						$iRow++;
					}
				}

				while ($iter->hasNext()) {
					$result = $iter->next();
					if (checkPermissions($result->getValue(), false))
						$iRow++;
				}

				$dbDriver->cancelTransaction();

				http_response_code(200);
				echo json_encode(array(
					'message' => 'Query successfull',
					'jobs_id' => $jobsId,
					'total_rows' => $iRow
				));
			}
		break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_DELETE | HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>