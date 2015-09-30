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
				if ($media === null)
					httpResponse(500, array(
						'message' => 'Query failure',
						'media' => array()
					));
				elseif ($media === false)
					httpResponse(404, array(
						'message' => 'Media not found',
						'media' => array()
					));

				httpResponse(200, array(
						'message' => 'Query succeeded',
						'media' => $media
				));
			}
			// Medias by pool
			elseif (isset($_GET['pool']) && is_numeric($_GET['pool'])) {
				$permission_granted = $dbDriver->checkPoolPermission($_GET['pool'], $_SESSION['user']['id']);
				if ($permission_granted === null)
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				elseif ($permission_granted === false)
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
				if ($result['query_executed'] == false)
					httpResponse(500, array(
						'message' => 'Query failure',
						'medias' => array(),
						'total_rows' => 0
					));
				else
					httpResponse(200, array(
						'message' => 'Query successfull',
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
				if ($result['query_executed'] == false)
					httpResponse(500, array(
						'message' => 'Query failure',
						'medias' => array(),
						'total_rows' => 0
					));
				else
					httpResponse(200, array(
						'message' => 'Query successfull',
						'medias' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}
			// Medias by poolgroup
			else {
				// getMediasByPoolgroup() tous les medias de tous les pools du groupe de pools             /!\ A FAIRE /!\
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
