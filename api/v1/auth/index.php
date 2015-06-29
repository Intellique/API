<?php
/**
 * \addtogroup authentication
 * \section Authentication
 * To authenticate a user,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/auth/ \endverbatim
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
 * \verbatim path : /storiqone-backend/api/v1/auth/ \endverbatim
 * \return HTTP status codes :
 * - \b 200 Logged in
 * - \b 401 Not logged in
 *
 * \section Disconnection
 * To log out,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/auth/ \endverbatim
 * \return HTTP status codes :
 * - \b 200 Logged out
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			session_destroy();
			httpResponse(200, array('message' => 'Logged out'));
			break;

		case 'GET':
			if (isset($_SESSION['user']))
				httpResponse(200, array(
					'message' => 'Logged in',
					'user_id' => $_SESSION['user']['id']
				));
			else
				httpResponse(401, array('message' => 'Not logged in'));
			break;

		case 'POST':
			if (!isset($_POST['login']) || !isset($_POST['password']))
				httpResponse(400, array('message' => '"login" and "password" are required'));

			$user = $dbDriver->getUser(null, $_POST['login']);

			if ($user === false || $user['disabled'])
				httpResponse(401, array('message' => 'Log in failed'));

			$password = $_POST['password'];
			$half_length = strlen($password) >> 1;
			$password = sha1(substr($password, 0, $half_length) . $user['salt'] . substr($password, $half_length));

			if ($password != $user['password'])
				httpResponse(401, array('message' => 'Authentication failed'));

			$_SESSION['user'] = $user;

			httpResponse(200, array(
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
