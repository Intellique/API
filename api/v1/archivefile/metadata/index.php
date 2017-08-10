<?php
/**
 * \addtogroup metadata
 * \page archivefile
 * \subpage metaarchivefile ArchiveFile Metadata
 * \section Metadatas_archivefile Metadatas of archive files
 * To get metadatas from an archive file,
 * use \b GET method : <i>with a reference to an archive file id</i>
 * \verbatim path : /storiqone-backend/api/v1/archivefile/metadata \endverbatim
 * \section Metadata_archivefile Metadata key of archive files
 * To get a metadata key of an archive file,
 * use \b GET method : <i>with a reference to an archive file id and a reference to a key</i>
 * \verbatim path : /storiqone-backend/api/v1/archivefile/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archivefile not found / Metadata not found
 *   - \b 500 Query failure
 * \section Metadatas_archivefiles Update metadatas of archive files
 * To update metadatas of an archive file,
 * use \b PUT method : <i>with a reference to an archive file id and a reference to a key and a reference to a value</i>
 * \verbatim path : /storiqone-backend/api/v1/archivefile/metadata/ \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archivefile not found / Metadata not found
 *   - \b 500 Query failure
 * \section Metadatas_archivefiles Create metadatas of archive files
 * To create metadatas of an archive file,
 * use \b POST method : <i>with a reference to an archive file id and a reference to a key and a reference to a value</i>
 * \verbatim path : /storiqone-backend/api/v1/archivefile/metadata/ \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archivefile not found / Metadata not found
 *   - \b 500 Query failure
 */
	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
					httpResponse(400, array('message' => 'id must be an integer'));

				$permission_granted = $dbDriver->checkArchiveFilePermission($_GET['id'], $_SESSION['user']['id']);
				if ($permission_granted === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archivefile (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archivefile (%d) => checkArchiveFilePermission(%s, %s)', __LINE__, $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archivefile' => array()
					));
				} elseif ($permission_granted === false) {
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/archivefile (%d) => A user that cannot get archivefile informations tried to', __LINE__), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}

				if (isset($_GET['key'])) {
					$metadata = $dbDriver->getMetadata($_GET['id'], $_GET['key'], 'archivefile');
					if ($metadata['error'] === true) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archivefile/metadata => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMetadata(%s, archivefile)', $_GET['id']), $_SESSION['user']['id']);
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
					$metadata = $dbDriver->getMetadatas($_GET['id'], 'archivefile');
					if ($metadata === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archivefile/metadata => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMetadatas(%s, archivefile)', $_GET['id']), $_SESSION['user']['id']);
						httpResponse(500, array(
							'message' => 'Query failure',
							'metadata' => null
						));
					} elseif ($metadata === false)
						httpResponse(404, array(
							'message' => 'No metadata found for this object',
							'metadata' => null
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

		case 'POST':
			checkConnected();

			$inputData = httpParseInput();

			if (!isset($inputData['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archivefile/metadata (%d) => Trying to create new archive file\'s metadata without specifying an archive file\'s id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive file ID required'));
			} elseif (!is_integer($inputData['id']))
				httpResponse(400, array('message' => 'id must be an integer'));

			if (!isset($inputData['metadata']))
				httpResponse(400, array('message' => 'Metadata is required'));
			elseif (!is_array($inputData['metadata']))
				httpResponse(400, array('message' => 'Metadata should be an associative array'));
			foreach ($inputData['metadata'] as $key => $value)
				if (!is_string($key))
					httpResponse(400, array('message' => 'key must be a string'));

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/archivefile/metadata (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$checkPermission = $dbDriver->checkArchiveFilePermission($inputData['id'], $_SESSION['user']['id']);
			if ($checkPermission === null) {
				$dbDriver->cancelTransaction();

				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/metadata (%d) => checkArchivePermission(%s, %d)', __LINE__, $inputData['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($checkPermission === false) {
				$dbDriver->cancelTransaction();
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$archivefile = $dbDriver->getArchiveFile($inputData['id'], DB::DB_ROW_LOCK_SHARE);
			if ($archivefile === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archivefile/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archivefile/metadata (%d) => getArchiveFile(%s)', __LINE__, $inputData['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($archivefile === false) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'Archive file not found'));
			}

			$metadata = $dbDriver->getMetadatas($inputData['id'], 'archivefile');
			if ($metadata === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archivefile/metadata => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMetadatas(%s, archivefile)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'metadata' => array()
				));
			} elseif ($metadata !== false)
				httpResponse(404, array(
					'message' => 'There are metadatas found for this object',
					'metadata' => array()
				));

			foreach ($inputData['metadata'] as $key => $value)
				if (array_key_exists($key, $metadata)) {
					$dbDriver->cancelTransaction();
					httpResponse(400, array('message' => sprintf("Metadata \"%s\" of archive file \"%s\" already exists, use PUT method in order to update metadata", $key, $archive['name'])));
				}

			// create metadata
			foreach ($inputData['metadata'] as $key => $value) {
				$resultMetadata = $dbDriver->createMetadata($archive['id'], $key, $value, 'archivefile', $_SESSION['user']['id']);
				if (!$resultMetadata) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archivefile/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archivefile/metadata (%d) => createMetadata(%s, %s, %s, "archive", %s)', __LINE__, $inputData['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			httpResponse(200, array('message' => 'Metadata created successfully'));

			break;

		case 'PUT':
			checkConnected();

			$inputData = httpParseInput();

			if (!isset($inputData['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archivefile/metadata (%d) => Trying to update archive file\'s metadata without specifying an archive file\'s id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive file ID required'));
			} elseif (!is_integer($inputData['id']))
				httpResponse(400, array('message' => 'id must be an integer'));

			if (!isset($inputData['metadata']))
				httpResponse(400, array('message' => 'Metadata is required'));
			elseif (!is_array($inputData['metadata']))
				httpResponse(400, array('message' => 'Metadata should be an associative array'));
			foreach ($inputData['metadata'] as $key => $value)
				if (!is_string($key))
					httpResponse(400, array('message' => 'key must be a string'));

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('PUT api/v1/archivefile/metadata (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$checkPermission = $dbDriver->checkArchiveFilePermission($inputData['id'], $_SESSION['user']['id']);
			if ($checkPermission === null) {
				$dbDriver->cancelTransaction();

				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive/metadata (%d) => checkArchivePermission(%s, %d)', __LINE__, $inputData['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($checkPermission === false) {
				$dbDriver->cancelTransaction();
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$archivefile = $dbDriver->getArchiveFile($inputData['id'], DB::DB_ROW_LOCK_SHARE);
			if ($archivefile === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archivefile/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archivefile/metadata (%d) => getArchiveFile(%s)', __LINE__, $inputData['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($archivefile === false) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'Archive file not found'));
			}

			$metadata = $dbDriver->getMetadatas($inputData['id'], 'archivefile');
			if ($metadata === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archivefile/metadata => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMetadatas(%s, archivefile)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'metadata' => array()
				));
			} elseif ($metadata !== false)
				httpResponse(404, array(
					'message' => 'There are metadatas found for this object',
					'metadata' => array()
				));

			foreach ($inputData['metadata'] as $key => $value)
				if (!array_key_exists($key, $metadata)) {
					$dbDriver->cancelTransaction();
					httpResponse(400, array('message' => sprintf("Metadata \"%s\" of archive file \"%s\" should exist, use POST method in order to create new metadata", $key, $archive['name'])));
				}

			// update metadata
			foreach ($inputData['metadata'] as $key => $value) {
				$resultMetadata = $dbDriver->updateMetadata($archive['id'], $key, $value, 'archivefile', $_SESSION['user']['id']);
				if (!$resultMetadata) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archivefile/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archivefile/metadata (%d) => updateMetadata(%s, %s, %s, "archive", %s)', __LINE__, $inputData['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			httpResponse(200, array('message' => 'Metadata updated successfully'));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET | HTTP_POST | HTTP_PUT);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
