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
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (isset($_GET['id'])) {
				$job = $dbDriver->getJob($_GET['id']);
				if ($job === null) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'job' => array()
					));
					exit;
				} elseif ($job === false) {
					http_response_code(404);
					echo json_encode(array(
						'message' => 'Job not found',
						'job' => array()
					));
					exit;
				}

				$ok = $_SESSION['user']['isadmin'];
				$failed = false;

				if (!$ok && isset($job['archive'])) {
					$checkArchivePermission = $dbDriver->checkArchivePermission($job['archive'], $_SESSION['user']['id']);
					if ($checkArchivePermission === null)
						$failed = true;
					elseif ($checkArchivePermission === true)
						$ok = true;
				}

				if (!$ok && isset($job['pool'])) {
					$checkPoolPermission = $dbDriver->checkPoolPermission($job['pool'], $_SESSION['user']['id']);
					if ($checkPoolPermission === null)
						$failed = true;
					elseif ($checkPoolPermission === true)
						$ok = true;
				}

				if ($failed) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'job' => null
					));
					exit;
				} elseif ($ok) {
					http_response_code(200);
					echo json_encode(array(
						'message' => 'Query successfull',
						'job' => $job
					));
					exit;
				} else {
					http_response_code(401);
					echo json_encode(array('message' => 'Permission denied'));
				}
			}
		break;
	}
?>