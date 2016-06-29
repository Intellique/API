<?php
/**
 * \addtogroup media
 * \section Media_ID Media information
 * To get media by its id
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/media/ \endverbatim
 * \param id : media id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Media information is returned \endverbatim
 *   - \b 400 Media id must be an integer
 *   - \b 401 Not logged in
 *   - \b 404 Media not found
 *   - \b 500 Query failure
 *
 *
 * \section Medias_by_pool Medias by pool (multiple list)
 * To get medias ids list by pool,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/media/ \endverbatim
 * \param pool : pool id
 *
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Medias ids list by pool is returned \endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 *
 *
 * \section Medias_by_poolgroup Medias by poolgroup (multiple list)
 * To get medias ids list by poolgroup,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/media/ \endverbatim
 *
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Medias ids list by poolgroup is returned \endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 *
 *
 * \section Medias_without_pool Medias without pool (multiple list)
 * To get medias ids list without pool,
 * use \b GET method : <i>without reference to specific id or pool</i>
 * \verbatim path : /storiqone-backend/api/v1/media/ \endverbatim
 * \param mediaformat : filter by mediaformat id [optional]
 *
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple medias ids list without pool do not pass an id or pool as parameter. If mediaformat id is defined, filter will be enable.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Medias ids list without pool is returned \endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			// Media information
			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id']))
					httpResponse(400, array('message' => 'Media id must be an integer'));

				$media = $dbDriver->getMedia($_GET['id']);
				if ($media === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMedia(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'media' => null
					));
				} elseif ($media === false)
					httpResponse(404, array(
						'message' => 'Media not found',
						'media' => null
					));

				httpResponse(200, array(
						'message' => 'Query succeeded',
						'media' => $media
				));
			}
			// Medias by pool
			elseif (isset($_GET['pool']) && is_numeric($_GET['pool'])) {
				$permission_granted = $dbDriver->checkPoolPermission($_GET['pool'], $_SESSION['user']['id']);
				if ($permission_granted === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkPoolPermission(%s, %s)', $_GET['pool'], $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				} elseif ($permission_granted === false)
					httpResponse(403, array('message' => 'Permission denied'));

				$params = array();
				$ok = true;

				if (isset($_GET['limit'])) {
					if (is_numeric($_GET['limit']) && $_GET['limit'] > 0)
						$params['limit'] = intval($_GET['limit']);
					else
						$ok = false;
				}
				if (isset($_GET['offset'])) {
					if (is_numeric($_GET['offset']) && $_GET['offset'] >= 0)
						$params['offset'] = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$result = $dbDriver->getMediasByPool($_GET['pool'], $params);
				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediasByPool(%s, %s)', $_GET['pool'], $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'medias' => array(),
						'total_rows' => 0
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'medias' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}
			// Medias without pool
			elseif (isset($_GET['pool']) && strcasecmp($_GET['pool'], 'null') == 0) {
				$params = array();
				$ok = true;

				if (isset($_GET['limit'])) {
					if (is_numeric($_GET['limit']) && $_GET['limit'] > 0)
						$params['limit'] = intval($_GET['limit']);
					else
						$ok = false;
				}
				if (isset($_GET['offset'])) {
					if (is_numeric($_GET['offset']) && $_GET['offset'] >= 0)
						$params['offset'] = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$mediaformat = null;
				if (isset($_GET['mediaformat']))
					$mediaformat = $_GET['mediaformat'];
				$result = $dbDriver->getMediasWithoutPool($mediaformat, $params);
				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediasWithoutPool(%s, %s)', $mediaformat, $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'medias' => array(),
						'total_rows' => 0
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'medias' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}
			// Medias by poolgroup
			else {
				$params = array();
				$ok = true;

				if (!isset($_SESSION['user']['poolgroup']))
					$ok = false;

				if (isset($_GET['limit'])) {
					if (is_numeric($_GET['limit']) && $_GET['limit'] > 0)
						$params['limit'] = intval($_GET['limit']);
					else
						$ok = false;
				}
				if (isset($_GET['offset'])) {
					if (is_numeric($_GET['offset']) && $_GET['offset'] >= 0)
						$params['offset'] = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$result = $dbDriver->getMediasByPoolgroup($_SESSION['user']['poolgroup'], $params);
				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediasByPoolgroup(%s, %s)', $_SESSION['user']['poolgroup'], $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'medias' => array(),
						'total_rows' => 0
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'medias' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}

			break;

		case 'PUT' :
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				httpResponse(403, array('message' => 'Permission denied'));
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to update a media', $_SESSION['user']['id']);
			}

			$media = httpParseInput();

			if (isset($media['id'])) {
				if (!is_numeric($media['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/media => Media id must be an integer and not %s', $media['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Media id must be an integer'));
				}

				$check_media = $dbDriver->getMedia($media['id']);
				if ($check_media === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/media => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMedia(%s)', $media['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'media' => array()
					));
				} elseif ($check_media === false)
					httpResponse(404, array(
						'message' => 'Media not found',
						'media' => array()
					));

				$check_media['name'] = $media['name'];
				$check_media['label'] = $media['label'];

				$result = $dbDriver->updateMedia($check_media);
				if ($result === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/media => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updateMedia(%s)', $check_media), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($result === false)
					httpResponse(404, array('message' => 'Media not found'));
				else {
					$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('Media %s updated', $media['id']), $_SESSION['user']['id']);
					httpResponse(200, array('message' => 'Media updated'));
				}
			} else {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/media => Trying to update a media without specifying media id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media ID required'));
			}



		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET | HTTP_PUT);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
