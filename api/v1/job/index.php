<?php
/**
 * \addtogroup job
 * \section Job_Info Job information
 * To get job information,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/job/ \endverbatim
 * \param id : job ID
 * \return HTTP status codes :
 *   - \b 200 Query successfull
 *     \verbatim Job information is returned \endverbatim
 *   - \b 401 Permission denied
 *   - \b 404 Job not found
 *   - \b 500 Query failure
 *
 * \section Jobs_ID Jobs ID
 * To get jobs ID list,
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
 * \warning To get jobs ID list do not pass an id or ids as parameter
 * \return HTTP status codes :
 *   - \b 200 Query successfull
 *     \verbatim Jobs ID list is returned \endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Permission denied
 *   - \b 500 Query failure
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbSession.php");

	function checkPermissions($jobId, $returnJob) {
		global $dbDriver;

		$job = $dbDriver->getJob($jobId);
		if ($job === null || $job === false)
			return $returnJob ? array('failure' => $job === null, 'job' => null, 'permission' => false) : $job;

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
			return $returnJob ? array('failure' => true, 'job' => null, 'permission' => false) : null;

		if ($returnJob)
			return $ok ? array('failure' => false, 'job' => $job, 'permission' => true) : array('failure' => false, 'job' => $job, 'permission' => false);

		return $ok;
	}

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (isset($_GET['id'])) {
				$job = checkPermissions($_GET['id'], true);
				if ($job['failure']) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'job' => array()
					));
				} elseif ($job['permission'] == false) {
					http_response_code(401);
					echo json_encode(array(
						'message' => 'Permission denied',
						'job' => array()
					));
				} elseif ($job['job'] !== null) {
					http_response_code(200);
					echo json_encode(array(
						'message' => 'Query successfull',
						'job' => $job['job']
					));
				} else {
					http_response_code(404);
					echo json_encode(array(
						'message' => 'Job not found',
						'job' => array()
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

				$jobs = $dbDriver->getJobs($params);

				if ($jobs['query executed'] == false) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'jobs id' => array(),
						'total rows' => 0
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

				http_response_code(200);
				echo json_encode(array(
					'message' => 'Query successfull',
					'jobs id' => $jobsId,
					'total rows' => $iRow
				));
			}
		break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>