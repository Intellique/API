<?php
/**
 * \addtogroup metadata
 * \page metadata
 * \subpage mediametadata Media Metadata
 * \section Metadatas_media Metadatas of medias
 * To get metadatas from a media,
 * use \b GET method : <i>with a reference to a media id</i>
 * \verbatim path : /storiqone-backend/api/v1/media/metadata \endverbatim
 * \section Metadata_media Metadata key of medias
 * To get a metadata key of a media file,
 * use \b GET method : <i>with a reference to a media id and a reference to a key</i>
 * \verbatim path : /storiqone-backend/api/v1/media/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Media not found / Metadata not found
 *   - \b 500 Query failure
 */

	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (!isset($_GET['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/media/metadata (%d) => Trying to get an media\'s metadata without media\'s id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'media ID required'));
			} elseif (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
				httpResponse(400, array('message' => 'id must be an integer'));

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('GET api/v1/media/metadata (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$exists = $dbDriver->getMedia($_GET['id']);
			if (!$exists)
				$dbDriver->cancelTransaction();
			if ($exists === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/media/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/media/metadata (%d) => getMedia(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($exists === false)
				httpResponse(404, array('message' => 'This media does not exist'));

			if (isset($_GET['key'])) {
				$metadata = $dbDriver->getMetadata($_GET['id'], $_GET['key'], 'media');
				$dbDriver->cancelTransaction();

				if ($metadata['error'] === true) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/media/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/media/metadata (%d) => getMetadata(%s, media)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'metadata' => array()
					));
				} elseif ($metadata['found'] === false)
					httpResponse(404, array(
						'message' => 'No metadata found for this object',
						'metadata' => array()
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'metadata' => $metadata['value']
				));
			} else {
				$metadata = $dbDriver->getMetadatas($_GET['id'], 'media');
				$dbDriver->cancelTransaction();

				if ($metadata === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/media/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/media/metadata (%d) => getMetadatas(%s, media)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'metadata' => array()
					));
				} elseif ($metadata === false)
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
