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

			if (!isset($_GET['id'])) {
				http_response_code(400);
				echo json_encode(array('message' => '"id" of archive required'));
				exit;
			}

			$permission_granted = $dbDriver->checkArchivePermission($_GET['id'], $_SESSION['user']['id']);
			if ($permission_granted === null) {
				http_response_code(500);
				echo json_encode(array(
					'message' => 'Query failure',
					'user' => array()
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
					'user' => array()
				));
				exit;
			}

			http_response_code(200);
			echo json_encode(array(
				'message' => 'Query succeeded',
				'archive' => $archive
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
