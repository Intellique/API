<?php
/**
 * \addtogroup authentication
 * \section Authentication
 * To authenticate a user,
 * use \b POST method
 * \verbatim
 * path : /storiqone-backend/api/v1/auth/
 * \endverbatim
 * \param login : User login
 * \param password : User password
 * \return HTTP status codes :
 *   - \b 200 Logged in
 *     \verbatim User ID is returned \endverbatim
 *   - \b 400 Missing parameters (login and/or password missing)
 *   - \b 401 Log in failed
 *
 * \section Connection_status Connection status
 * To check user's connection status,
 * use \b GET method
 * \verbatim
 * path : /storiqone-backend/api/v1/auth/
 * \endverbatim
 * \return HTTP status codes :
 * - \b 200 Logged in
 * - \b 401 Not logged in
 *
 * \section Disconnection
 * To log out,
 * use \b DELETE method
 * \verbatim
 * path : /storiqone-backend/api/v1/auth/
 * \endverbatim
 * \return HTTP status codes :
 * - \b 200 Logged out
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			session_destroy();
			header("Content-Type: application/json; charset=utf-8");
			http_response_code(200);
			echo json_encode(array('message' => 'Logged out'));
			break;

		case 'GET':
			if (isset($_SESSION['user'])) {
				header("Content-Type: application/json; charset=utf-8");
				http_response_code(200);
				echo json_encode(array(
					'message' => 'Logged in',
					'user_id' => $_SESSION['user']['id']
				));
			} else {
				header("Content-Type: application/json; charset=utf-8");
				http_response_code(401);
				echo json_encode(array('message' => 'Not logged in'));
			}
			break;

		case 'POST':
			header("Content-Type: application/json; charset=utf-8");

			if (!isset($_POST['login']) || !isset($_POST['password'])) {
				http_response_code(400);
				echo json_encode(array('message' => '"login" and "password" are required'));
				exit;
			}

			$user = $dbDriver->getUser(null, $_POST['login']);
			if ($user === false || $user['disabled']) {
				http_response_code(401);
				echo json_encode(array('message' => 'Log in failed'));
				exit;
			}

			$password = $_POST['password'];
			$half_length = strlen($password) >> 1;
			$password = sha1(substr($password, 0, $half_length) . $user['salt'] . substr($password, $half_length));

			if ($password != $user['password']) {
				http_response_code(401);
				echo json_encode(array('message' => 'Authentication failed'));
				exit;
			}

			$_SESSION['user'] = $user;

			echo json_encode(array(
				'message' => 'Logged in',
				'user_id' => $user['id']
			));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_DELETE | HTTP_GET | HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
