<?php
/**
 * To delete all metadata of an archive
 * To delete metadata by the key : "name", "format", "observation", "conserver jusqu'au" of an archives
 * use \b DELETE method : <i>with a reference to an archive id and or the key(s) of the metadatas.
 * \verbatim path : /storiqone-backend/api/v1/archive/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archive not found / Metadata not found
 *   - \b 500 Query failure
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
 * \section Metadatas_archives Update metadatas of archives
 * To update metadatas of an archive,
 * use \b PUT method : <i>with a reference to an archive id and a reference to a key and a reference to a value</i>
 * \verbatim path : /storiqone-backend/api/v1/archive/metadata/ \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archive not found / Metadata not found
 *   - \b 500 Query failure
 * \section Metadatas_archives Create metadatas of archives
 * To create metadatas of an archive,
 * use \b POST method : <i>with a reference to an archive id and a reference to a key and a reference to a value</i>
 * \verbatim path : /storiqone-backend/api/v1/archive/metadata/ \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archive not found / Metadata not found
 *   - \b 500 Query failure
 */

require_once "../../lib/env.php";

require_once "http.php";
require_once "session.php";
require_once "db.php";

switch ($_SERVER['REQUEST_METHOD']) {

case 'DELETE':

	checkConnected();

	if (!isset($_GET['id'])) {
		httpResponse(400, array('message' => 'id is required'));
	}

	if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false) {
		httpResponse(400, array('message' => 'Script ID must be an integer'));
	}

	$permission = $dbDriver->checkArchivePermission($_GET['id'], $_SESSION['user']['id']);
	if ($permission === null) {
		$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
		$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archive (%d) => checkArchivePermission(%s, %s)', __LINE__, $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
		httpResponse(500, array(
			'message' => 'Query failure',
			'archivefile' => array(),
		));
	} elseif ($permission === false) {
		$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/archive (%d) => A user that cannot get archive informations tried to', __LINE__), $_SESSION['user']['id']);
		httpResponse(403, array('message' => 'Permission denied'));
	}

	if (!$dbDriver->startTransaction()) {
		$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('DELETE api/v1/archive/metadata (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
		httpResponse(500, array('message' => 'Transaction failure'));
	}

	if (isset($_GET['key'])) {
		$key = explode(',', $_GET['key']);

		$delete_metadata = $dbDriver->deleteMetadata($_GET['id'], $key, 'archive', $_SESSION['user']['id']);

		if ($delete_metadata === null) {
			$dbDriver->cancelTransaction();
			httpResponse(500, array(
				'message' => 'Query failure',
				'metadata' => null,
			));
		} else if ($delete_metadata === false) {
			$dbDriver->cancelTransaction();
			httpResponse(404, array(
				'message' => 'No metadata found for this object',
				'metadata' => null,
			));
		} elseif (!$dbDriver->finishTransaction()) {
			$dbDriver->cancelTransaction();
			httpResponse(500, array('message' => 'Transaction failure'));
		}

		$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/archive/metadata => metadata %s deleted', $_GET['id']), $_SESSION['user']['id']);
		httpResponse(200, array(
			'message' => 'Query succeeded',
			'key' => $_GET['key'],
			'value' => $delete_metadata['value'],
		));

	} else {
		$delete_metadata = $dbDriver->deleteMetadatas($_GET['id'], 'archive', $_SESSION['user']['id']);

		if ($delete_metadata === null) {
			$dbDriver->cancelTransaction();
			httpResponse(500, array('message' => 'query failure'));
		} elseif ($delete_metadata === false) {
			$dbDriver->cancelTransaction();
			httpResponse(404, array('message' => 'metadatas are not found'));
		} elseif (!$dbDriver->finishTransaction()) {
			$dbDriver->cancelTransaction();
			httpResponse(500, array('message' => 'Transaction failure'));
		}

		$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/archive/metadata => metadata %s deleted', $_GET['id']), $_SESSION['user']['id']);
		httpResponse(200, array('message' => 'Deletion successful'));
	}

	break;

case 'GET':
	checkConnected();

	if (!isset($_GET['id'])) {
		$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/archive/metadata (%d) => Trying to get archive\'s metadata without specifying an archive id', __LINE__), $_SESSION['user']['id']);
		httpResponse(400, array('message' => 'Archive ID required'));
	}

	$archiveId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
	if ($archiveId === false) {
		httpResponse(400, array('message' => 'id must be an integer'));
	}

	$checkPermission = $dbDriver->checkArchivePermission($archiveId, $_SESSION['user']['id']);
	if ($checkPermission === null) {
		$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
		$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archive/metadata (%d) => checkArchivePermission(%s, %d)', __LINE__, $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
		httpResponse(500, array('message' => 'Query failure'));
	} elseif ($checkPermission === false) {
		httpResponse(403, array('message' => 'Permission denied'));
	}

	if (isset($_GET['key'])) {
		$metadata = $dbDriver->getMetadata($_GET['id'], $_GET['key'], 'archive');

		if ($metadata['error'] === true) {
			$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
			$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archive/metadata (%d) => getMetadata(%s, \'archive\')', __LINE__, $_GET['id']), $_SESSION['user']['id']);
			httpResponse(500, array(
				'message' => 'Query failure',
				'metadata' => null,
			));
		}

		if ($metadata['found'] === false) {
			httpResponse(404, array(
				'message' => 'No metadata found for this object',
				'metadata' => null,
			));
		}

		httpResponse(200, array(
			'message' => 'Query succeeded',
			'key' => $_GET['key'],
			'value' => $metadata['value'],
		));
	} else {
		$metadata = $dbDriver->getMetadatas($_GET['id'], 'archive');

		if ($metadata === null) {
			$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
			$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archive/metadata (%d) => getMetadatas(%s, \'archive\')', __LINE__, $_GET['id']), $_SESSION['user']['id']);
			httpResponse(500, array(
				'message' => 'Query failure',
				'metadata' => array(),
			));
		}

		if ($metadata === false) {
			httpResponse(404, array(
				'message' => 'No metadata found for this object',
				'metadata' => array(),
			));
		}

		if (count($metadata) === 0) {
			httpResponse(404, array(
				'message' => 'No metadata found for this object',
				'metadata' => $metadata,
			));
		}

		httpResponse(200, array(
			'message' => 'Query succeeded',
			'metadata' => $metadata,
		));
	}
	break;

case 'POST':
	checkConnected();

	$inputData = httpParseInput();

	if (!isset($inputData['id'])) {
		$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/metadata (%d) => Trying to create new archive\'s metadata without specifying an archive id', __LINE__), $_SESSION['user']['id']);
		httpResponse(400, array('message' => 'Archive ID required'));
	} elseif (!is_integer($inputData['id'])) {
		httpResponse(400, array('message' => 'id must be an integer'));
	}

	if (!isset($inputData['metadata'])) {
		httpResponse(400, array('message' => 'Metadata is required'));
	} elseif (!is_array($inputData['metadata'])) {
		httpResponse(400, array('message' => 'Metadata should be an associative array'));
	}

	foreach ($inputData['metadata'] as $key => $value) {
		if (!is_string($key)) {
			httpResponse(400, array('message' => 'key must be a string'));
		}
	}

	if (!$dbDriver->startTransaction()) {
		$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/archive/metadata (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
		httpResponse(500, array('message' => 'Transaction failure'));
	}

	$checkPermission = $dbDriver->checkArchivePermission($inputData['id'], $_SESSION['user']['id']);
	if ($checkPermission === null) {
		$dbDriver->cancelTransaction();

		$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
		$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/metadata (%d) => checkArchivePermission(%s, %d)', __LINE__, $inputData['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
		httpResponse(500, array('message' => 'Query failure'));
	} elseif ($checkPermission === false) {
		$dbDriver->cancelTransaction();
		httpResponse(403, array('message' => 'Permission denied'));
	}

	$archive = $dbDriver->getArchive($inputData['id'], DB::DB_ROW_LOCK_SHARE);
	if ($archive === null) {
		$dbDriver->cancelTransaction();

		$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
		$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/metadata (%d) => getArchive(%s)', __LINE__, $inputData['id']), $_SESSION['user']['id']);
		httpResponse(500, array('message' => 'Query failure'));
	} elseif ($archive === false) {
		$dbDriver->cancelTransaction();
		httpResponse(404, array('message' => 'Archive not found'));
	}

	foreach ($inputData['metadata'] as $key => $value) {
		if (array_key_exists($key, $archive['metadata'])) {
			$dbDriver->cancelTransaction();
			httpResponse(400, array('message' => sprintf("Metadata \"%s\" of archive \"%s\" already exists, use PUT method in order to update metadata", $key, $archive['name'])));
		}
	}

	// create metadata
	foreach ($inputData['metadata'] as $key => $value) {
		$resultMetadata = $dbDriver->createMetadata($archive['id'], $key, $value, 'archive', $_SESSION['user']['id']);
		if (!$resultMetadata) {
			$dbDriver->cancelTransaction();
			$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/metadata => Query failure', $_SESSION['user']['id']);
			$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createMetadata(%s, %s, %s, "archive", %s)', $inputData['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
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
		$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/metadata (%d) => Trying to create new archive\'s metadata without specifying an archive id', __LINE__), $_SESSION['user']['id']);
		httpResponse(400, array('message' => 'Archive ID required'));
	} elseif (!is_integer($inputData['id'])) {
		httpResponse(400, array('message' => 'id must be an integer'));
	}

	if (!isset($inputData['metadata'])) {
		httpResponse(400, array('message' => 'Metadata is required'));
	} elseif (!is_array($inputData['metadata'])) {
		httpResponse(400, array('message' => 'Metadata should be an associative array'));
	}

	foreach ($inputData['metadata'] as $key => $value) {
		if (!is_string($key)) {
			httpResponse(400, array('message' => 'key must be a string'));
		}
	}

	if (!$dbDriver->startTransaction()) {
		$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/archive/metadata (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
		httpResponse(500, array('message' => 'Transaction failure'));
	}

	$checkPermission = $dbDriver->checkArchivePermission($inputData['id'], $_SESSION['user']['id']);
	if ($checkPermission === null) {
		$dbDriver->cancelTransaction();

		$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
		$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/metadata (%d) => checkArchivePermission(%s, %d)', __LINE__, $inputData['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
		httpResponse(500, array('message' => 'Query failure'));
	} elseif ($checkPermission === false) {
		$dbDriver->cancelTransaction();
		httpResponse(403, array('message' => 'Permission denied'));
	}

	$archive = $dbDriver->getArchive($inputData['id'], DB::DB_ROW_LOCK_SHARE);
	if ($archive === null) {
		$dbDriver->cancelTransaction();

		$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
		$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/metadata (%d) => getArchive(%s)', __LINE__, $inputData['id']), $_SESSION['user']['id']);
		httpResponse(500, array('message' => 'Query failure'));
	} elseif ($archive === false) {
		$dbDriver->cancelTransaction();
		httpResponse(404, array('message' => 'Archive not found'));
	}

	foreach ($inputData['metadata'] as $key => $value) {
		if (!array_key_exists($key, $archive['metadata'])) {
			$dbDriver->cancelTransaction();
			httpResponse(400, array('message' => sprintf("Metadata \"%s\" of archive \"%s\" does not exist, use POST method in order to create metadata", $key, $archive['name'])));
		}
	}

	// update metadata
	foreach ($inputData['metadata'] as $key => $value) {
		$resultMetadata = $dbDriver->updateMetadata($archive['id'], $key, $value, 'archive', $_SESSION['user']['id']);
		if (!$resultMetadata) {
			$dbDriver->cancelTransaction();
			$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive/metadata => Query failure', $_SESSION['user']['id']);
			$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updateMetadata(%s, %s, %s, "archive", %s)', $inputData['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
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
	httpOptionsMethod(HTTP_GET | HTTP_POST | HTTP_PUT | HTTP_DELETE);
	break;

default:
	httpUnsupportedMethod();
	break;
}
?>
