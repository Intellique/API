<?php
/**
 * \addtogroup metadata
 * \page metadata
 * \subpage archivemeta Archive Metadata
 * \section Metadatas_archive Metadatas of archives
 * To get metadatas from an archive,
 * use \b GET method : <i>with a reference to an archive id</i>
 * \verbatim path : /storiqone-backend/api/v1/archive/metadata \endverbatim
 * \section Metadata_archive Metadata key of archives
 * To get a metadata key of an archive,
 * use \b GET method : <i>with a reference to an archive id and a reference to a key</i>
 * \verbatim path : /storiqone-backend/api/v1/archive/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archive not found / Metadata not found
 *   - \b 500 Query failure
 * \subpage archivemeta Archive Metadata
 * \section Metadata_archive Metadata key of archives
 * To update metadatas of an archive,
 * use \b PUT method : <i>with a reference to an archive id and a reference to a key and a reference to a value</i>
 * \verbatim path : /storiqone-backend/api/v1/archive/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archive not found / Metadata not found
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

				$exists = $dbDriver->getArchive($_GET['id']);
				if ($exists === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive/metadata => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchive(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
				if ($exists === false)
					httpResponse(404, array('message' => 'This archive does not exist'));

				if (isset($_GET['key'])) {
					if (!is_string($_GET['key']))
						httpResponse(400, array('message' => 'key must be a string'));
					$key = $_GET['key'];

					$metadata = $dbDriver->getMetadata($_GET['id'], $key, 'archive');
					if ($metadata['error'] === true) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive/metadata => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMetadata(%s, archive)', $_GET['id']), $_SESSION['user']['id']);
						httpResponse(500, array(
							'message' => 'Query failure',
							'metadata' => array()
						));
					}
					if ($metadata['founded'] === false)
						httpResponse(404, array(
								'message' => 'No metadata found for this object',
								'metadata' => array()
							));

					httpResponse(200, array(
							'message' => 'Query succeeded',
							'metadata' => $metadata['value']
						));
				} else {

					$metadata = $dbDriver->getMetadatas($_GET['id'], 'archive');
					if ($metadata === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive/metadata => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMetadatas(%s, archive)', $_GET['id']), $_SESSION['user']['id']);
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

		case 'PUT':
			checkConnected();

			$inputData = httpParseInput();

			$key = null;
			if (isset($inputData['id'])) {
				if (!is_numeric($inputData['id']))
					httpResponse(400, array('message' => 'id must be an integer'));

				$archive = $dbDriver->getArchive($inputData['id']);
				if ($archive === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive/metadata => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchive(%s)', $inputData['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
				if ($archive === false)
					httpResponse(404, array('message' => 'This archive does not exist'));

				// update metadata
				foreach ($inputData['metadata'] as $key => $value) {
					if (!is_string($key))
						httpResponse(400, array('message' => 'key must be a string'));
					if (array_key_exists($key, $archive['metadata'])) {
						$resultMetadata = $dbDriver->updateMetadata($archive['id'], $key, $value, 'archive', $_SESSION['user']['id']);
						if (!$resultMetadata) {
							$dbDriver->cancelTransaction();
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive/metadata => Query failure', $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updateMetadata(%s, %s, %s, "archive", %s)', $inputData['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query failure'));
						}
					} else {
						httpResponse(500, array('message' => 'Query failure'));
					}
				}

				$dbDriver->finishTransaction();

				httpResponse(200, array('message' => 'Metadata updated successfully'));
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
