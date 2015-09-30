<?php
/**
 * \addtogroup pool
 * \section Pool_ID Pool information
 * To get pool by its id
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/pool/ \endverbatim
 * \param id : pool id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Pool information is returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Pool not found
 *   - \b 500 Query failure
 *
 * \section Pools Pools ids (multiple list)
 * To get pools ids list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/pool/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple pools ids list do not pass an id or ids as parameter</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Pools ids list is returned \endverbatim
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

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id']))
					httpResponse(400, array('message' => 'Pool id must be an integer'));

				$pool = $dbDriver->getPool($_GET['id']);
				if ($pool === null)
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				elseif ($pool === false)
					httpResponse(404, array(
						'message' => 'Pool not found',
						'pool' => array()
					));

				$permission_granted = $dbDriver->checkPoolPermission($_GET['id'], $_SESSION['user']['id']);
				if ($permission_granted === null)
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				elseif ($permission_granted === false)
					httpResponse(403, array('message' => 'Permission denied'));

				httpResponse(200, array(
						'message' => 'Query succeeded',
						'pool' => $pool
				));
			} else {
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

				$result = $dbDriver->getPoolsByPoolgroup($_SESSION['user']['poolgroup'], $params);
				if ($result['query_executed'] == false)
					httpResponse(500, array(
						'message' => 'Query failure',
						'pools' => array(),
						'total_rows' => 0
					));
				else
					httpResponse(200, array(
						'message' => 'Query successfull',
						'pools' => $result['rows'],
						'total_rows' => $result['total_rows']
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