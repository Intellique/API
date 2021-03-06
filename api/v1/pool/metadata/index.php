<?php
/**
 * \addtogroup metadata
 * \page metadata
 * \subpage poolmetadata
 * \section Metadatas_pool Metadatas of pools
 * To get metadatas from a pool,
 * use \b GET method : <i>with a reference to a pool id</i>
 * \verbatim path : /storiqone-backend/api/v1/pool/metadata \endverbatim
 * \section Metadata_pool Metadata key of pools
 * To get a metadata key of a pool,
 * use \b GET method : <i>with a reference to a pool id and a reference to a key</i>
 * \verbatim path : /storiqone-backend/api/v1/pool/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Pool not found / Metadata not found
 *   - \b 500 Query failure
 */

	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("dbArchive.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
					httpResponse(400, array('message' => 'id must be an integer'));

				$exists = $dbDriver->getPool($_GET['id']);
				if ($exists === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pool/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pool/metadata (%d) => getPool(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($exists === false)
					httpResponse(404, array('message' => 'This pool does not exist'));

				$key = isset($_GET['key']) ? $_GET['key'] : null;
				$metadata = $dbDriver->getPoolMetadatas($_GET['id'], $key);
				if ($metadata === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pool/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pool/metadata (%d) => getPoolMetadatas(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);

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
