<?php
/**
 * \addtogroup jobtype
 * \section Jobtype_Info Jobtype name list
 * To get jobtype name list,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/jobtype/ \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query successfull
 *     \verbatim Jobtype name list is returned \endverbatim
 *   - \b 500 Query failure
 */
	require_once("../lib/http.php");
	require_once("../lib/dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			$jobtype = $dbDriver->getJobtype();

			if ($jobtype === null) {
				http_response_code(500);
				echo json_encode(array(
					'message' => 'Query failure',
					'jobtype' => array()
				));
			} else {
				http_response_code(200);
				echo json_encode(array(
					'message' => 'Query successfull',
					'jobtype' => $jobtype
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