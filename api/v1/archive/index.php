<?php
/**
 * \addtogroup archive
 * \section Delete_Archive Archive deletion
 * To mark archive as deleted,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param id : archive's id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Archive information are returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Archive not found
 *   - \b 500 Query failure
 *
 * \section Archive_ID Archive information
 * To get archive by its ID,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param id : archive's id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Archive information are returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 *
 * \section Archives Archives id,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'uuid', 'name' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning To get archives ID list do not pass an id as parameter
 * \return HTTP status codes :
 *   - \b 200 Query successfull
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				http_response_code(403);
				echo json_encode(array('message' => 'Permission denied'));
				exit;
			}

			if (isset($_GET['id'])) {
				$archive = $dbDriver->getArchive($_GET['id']);
				if ($archive === null) {
					http_response_code(500);
					echo json_encode(array('message' => 'Query failure'));
					exit;
				} elseif ($archive === false) {
					http_response_code(404);
					echo json_encode(array('message' => 'Archive not found'));
					exit;
				}

				$archive['deleted'] = true;

				$result = $dbDriver->updateArchive($archive);
				if ($result === null) {
					http_response_code(500);
					echo json_encode(array('message' => 'Query failure'));
				} elseif ($result === false) {
					http_response_code(404);
					echo json_encode(array('message' => 'Archive not found'));
				} else {
					http_response_code(200);
					echo json_encode(array('message' => 'Archive deleted'));
				}
			}

			break;

		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (isset($_GET['id'])) {
				$permission_granted = $dbDriver->checkArchivePermission($_GET['id'], $_SESSION['user']['id']);
				if ($permission_granted === null) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'archive' => array()
					));
					exit;
				} elseif ($permission_granted === false) {
					http_response_code(403);
					echo json_encode(array('message' => 'Permission denied'));
					exit;
				}

				$archive = $dbDriver->getArchive($_GET['id']);
				if ($archive === null) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'archive' => array()
					));
					exit;
				}

				http_response_code(200);
				echo json_encode(array(
					'message' => 'Query succeeded',
					'archive' => $archive
				));
			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'uuid', 'name')))
						$params['order_by'] = $_GET['order_by'];
					else
						$ok = false;

					if (isset($_GET['order_asc'])) {
						$is_asc = filter_var($_GET['order_asc'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
						if ($is_asc !== null)
							$params['order_asc'] = $is_asc;
						else
							$ok = false;
					}
				}
				if (isset($_GET['limit'])) {
					if (ctype_digit($_GET['limit']) && $_GET['limit'] > 0)
						$params['limit'] = intval($_GET['limit']);
					else
						$ok = false;
				}
				if (isset($_GET['offset'])) {
					if (ctype_digit($_GET['offset']) && $_GET['offset'] >= 0)
						$params['offset'] = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok) {
					http_response_code(400);
					echo json_encode(array('message' => 'Incorrect input'));
					exit;
				}

				$result = $dbDriver->getArchives($_SESSION['user']['id'], $params);
				if ($result['query_executed'] == false) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'archives' => array(),
						'total_rows' => 0
					));
				} else {
					echo json_encode(array(
						'message' => 'Query successfull',
						'archives' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
				}
			}

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_DELETE | HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
