<?php
/**
 * \addtogroup metadata
 * \page metadata
 * \subpage jobmetadata
 * \section Metadatas_job Metadatas of jobs
 * To get metadatas from a job,
 * use \b GET method : <i>with a reference to a job id</i>
 * \verbatim path : /storiqone-backend/api/v1/job/metadata \endverbatim
 * \section Metadata_job Metadata key of jobs
 * To get a metadata key of a job,
 * use \b GET method : <i>with a reference to a job id and a reference to a key</i>
 * \verbatim path : /storiqone-backend/api/v1/job/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Job not found / Metadata not found
 *   - \b 500 Query failure
 */

	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			$key = null;
			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id']))
					httpResponse(400, array('message' => 'id must be an integer'));

				if (isset($_GET['key'])) {
					if (!is_string($_GET['key']))
						httpResponse(400, array('message' => 'key must be a string'));
					$key = $_GET['key'];
				}

				$exists = $dbDriver->getJob($_GET['id']);
				if ($exists === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/job/metadata => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJob(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
				if ($exists === false)
					httpResponse(404, array('message' => 'This job does not exist'));

				$metadata = $dbDriver->getJobMetadatas($_GET['id'], $key);
				if ($metadata === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/job/metadata => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobMetadatas(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'metadata' => array()
					));
				}
				if ($metadata === false)
					httpResponse(404, array(
							'message' => 'No metadata found for this object',
							'metadata' => array()
						));
				if (count($metadata) === 0)
					httpResponse(404, array(
							'message' => 'No metadata found for this object',
							'metadata' => $metadata
						));

				httpResponse(200, array(
						'message' => 'Query succeeded',
						'metadata' => $metadata
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
