<?php
/**
 * \addtogroup archive
 * \section Archive_ID Archive ID
 * To get archive by its ID,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param id : archive's id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Archive information are returned \endverbatim
 *   - \b 401 Permission denied
 *   - \b 500 Query failure
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
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
					http_response_code(401);
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
			}

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
				if (is_integer($_GET['limit']) && $_GET['limit'] > 0)
					$params['limit'] = intval($_GET['limit']);
				else
					$ok = false;
			}
			if (isset($_GET['offset'])) {
				if (is_integer($_GET['offset']) && $_GET['offset'] > 0)
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
			if ($result['query executed'] == false) {
				http_response_code(500);
				echo json_encode(array(
					'message' => 'Query failure',
					'archives' => array(),
					'total rows' => 0
				));
			} else {
				echo json_encode(array(
					'message' => 'Query successfull',
					'archives' => $result['rows'],
					'total rows' => $result['total_rows']
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
