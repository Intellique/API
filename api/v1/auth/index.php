<?php
/**
 * \addtogroup authentication
 * To authenticate a user with controls
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
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
				echo json_encode(array('message' => 'Authentication failed'));
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

			echo json_encode(array('message' => 'Authentication success'));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_PUT);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
