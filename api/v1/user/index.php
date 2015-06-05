<?php
/**
 * \addtogroup user
 * \section Users_ID Users ID
 * To get users id,
 * use \b GET method <i>without parameters</i>
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Users id are returned \endverbatim
 *   - \b 401 Permission denied
 *   - \b 500 Query failure
 *
 * \section User_Info User informations
 * To get a user informations,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \param id : user id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim User informations are returned \endverbatim
 *   - \b 401 Permission denied
 *   - \b 500 Query failure
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbSession.php");

	function getUserById($id) {
		global $dbDriver;

		$user = $dbDriver->getUser($_GET['id'], null);
		if ($user === false) {
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
			'user' => $user
		));
		$_SESSION['user'] = $user;
		exit;
	}

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (isset($_GET['id'])) {

				if ($_GET['id'] == $_SESSION['user']['id']) {
					getUserById($_GET['id']);
				} elseif ($_SESSION['user']['isadmin']) {
					getUserById($_GET['id']);
				} else {
					http_response_code(401);
					echo json_encode(array('message' => 'Permission denied'));
					exit;
				}

			} elseif ($_SESSION['user']['isadmin']) {

				$users = $dbDriver->getUsers();

				if ($users === false) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'users_id' => array()
					));
					exit;
				}

				http_response_code(200);
				echo json_encode(array(
					'message' => 'Query succeeded',
					'users_id' => $users
				));
				exit;

			} else {

				http_response_code(401);
				echo json_encode(array('message' => 'Permission denied'));
				exit;

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