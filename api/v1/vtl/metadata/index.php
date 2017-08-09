<?php
/**
 * \addtogroup metadata
 * \section Metadatas_vtl Metadatas of vtls
 * To get metadatas from a vtl,
 * use \b GET method : <i>with a reference to a vtl id</i>
 * \verbatim path : /storiqone-backend/api/v1/vtl/metadata \endverbatim
 * \section Metadata_vtl Metadata key of vtls
 * To get a metadata key of a vtl file,
 * use \b GET method : <i>with a reference to a vtl id and a reference to a key</i>
 * \verbatim path : /storiqone-backend/api/v1/vtl/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 VTL not found / Metadata not found
 *   - \b 500 Query failure
 */

	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("dbArchive.php");
	require_once("dbMetadata.php");

	switch ($_SERVER['REQUEST_METHOD']) {

		case 'GET':
			checkConnected();

			$key = null;
			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id']))
					httpResponse(400, array('message' => 'id must be an integer'));

				$exists = $dbDriver->getVTL($_GET['id']);
				if ($exists === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/vtl/metadata => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getVTL(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
				if ($exists === false)
					httpResponse(404, array('message' => 'This vtl does not exist'));

				if (isset($_GET['key'])) {
					if (!is_string($_GET['key']))
						httpResponse(400, array('message' => 'key must be a string'));
					$key = $_GET['key'];

					$metadata = $dbDriver->getMetadata($_GET['id'], $key, 'vtl');
					if ($metadata['error'] === true) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/vtl/metadata => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMetadata(%s, vtl)', $_GET['id']), $_SESSION['user']['id']);
						httpResponse(500, array(
							'message' => 'Query failure',
							'metadata' => array()
						));
					}
					if ($metadata['found'] === false)
						httpResponse(404, array(
								'message' => 'No metadata found for this object',
								'metadata' => array()
							));

					httpResponse(200, array(
							'message' => 'Query succeeded',
							'metadata' => $metadata['value']
						));
				} else {

					$metadata = $dbDriver->getMetadatas($_GET['id'], 'vtl');
					if ($metadata === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/vtl/metadata => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMetadatas(%s, vtl)', $_GET['id']), $_SESSION['user']['id']);
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
