<?php
/**
 * \addtogroup jobtype
 * \page jobtype
 * \section Jobtype_Info Jobtypes names list
 * To get jobtypes names list,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/jobtype/ \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query successful
 *     \verbatim Jobtypes names list is returned \endverbatim
 *   - \b 500 Query failure
 */
	require_once("../lib/env.php");

	require_once("http.php");
	require_once("dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			$jobtype = $dbDriver->getJobTypes();

			if ($jobtype === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/jobtype => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobTypes()'), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'jobtype' => array()
				));
			} elseif ($jobtype === false)
				httpResponse(404, array(
					'message' => 'Job types not found',
					'archive' => array()
				));
			else
				httpResponse(200, array(
					'message' => 'Query successful',
					'jobtype' => $jobtype
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
