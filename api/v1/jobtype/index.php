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
	require_once("../lib/env.php");

	require_once("http.php");
	require_once("dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			$jobtype = $dbDriver->getJobtype();

			if ($jobtype === null)
				httpResponse(500, array(
					'message' => 'Query failure',
					'jobtype' => array()
				));
			else
				httpResponse(200, array(
					'message' => 'Query successfull',
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
