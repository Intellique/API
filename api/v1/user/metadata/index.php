<?php
/**
 * \addtogroup metadata
 * \page metadata
 * \subpage user
 * \section Metadatas_user Metadatas of users
 * To get metadatas from a user,
 * use \b GET method : <i>with a reference to a user id</i>
 * \verbatim path : /storiqone-backend/api/v1/user/metadata \endverbatim
 * \section Metadata_user Metadata key of users
 * To get a metadata key of a user,
 * use \b GET method : <i>with a reference to a user id and a reference to a key</i>
 * \verbatim path : /storiqone-backend/api/v1/user/metadata \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 User not found / Metadata not found
 *   - \b 500 Query failure
 */

	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (!isset($_GET['id']))
				httpResponse(400, array('message' => 'User id is required'));
			else if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
				httpResponse(400, array('message' => 'id must be an integer'));

			$key = null;
			if (isset($_GET['key']))
				$key = $_GET['key'];

			$exists = $dbDriver->getUserById($_GET['id']);
			if ($exists === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/user/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/user/metadata (%d) => getUser(%s, null)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($exists === false)
				httpResponse(404, array('message' => 'This user does not exist'));

			$metadata = $dbDriver->getUserMetadatas($_GET['id'], $key);
			if ($metadata === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/user/metadata (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/user/metadata (%d) => getUserMetadatas(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'metadata' => array()
				));
			}

			if ($metadata === false)
				httpResponse(404, array(
					'message' => 'No metadata found for this object',
					'metadata' => array($key => null)
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

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
