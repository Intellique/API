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
 *
 * \section Create_user User creation
 * To create a user,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \param user : JSON encoded object
 * \li \c login (string) user login
 * \li \c password (string) user password
 * \li \c fullname (string) user fullname
 * \li \c email (string) user email
 * \li \c homedirectory (string) user homedirectory
 * \li \c isadmin (boolean) administration rights
 * \li \c canarchive (boolean) archive rights
 * \li \c canrestore (boolean) restoration rights
 * \li \c meta (object) user metadatas
 * \li \c poolgroup (integer) user poolgroup
 * \li \c disabled (boolean) login rights
 * \return HTTP status codes :
 *   - \b 200 User created successfully
 *     \verbatim New user id is returned \endverbatim
 *   - \b 401 Permission denied
 *   - \b 400 User informations required or bad entries
 *   - \b 500 Query failure
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (isset($_GET['id'])) {

				if ($_GET['id'] == $_SESSION['user']['id'] || $_SESSION['user']['isadmin']) {
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

		case 'POST':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				http_response_code(401);
				echo json_encode(array('message' => 'Permission denied'));
				exit;
			}

			if (!isset($_POST['user'])) {
				http_response_code(400);
				echo json_encode(array('message' => 'User informations required'));
				exit;
			}

			$user = json_decode($_POST['user'], true);
			$ok = (bool) $user;

			// login
			if ($ok)
				$ok = isset($user['login']) && is_string($user['login']);
			if ($ok) {
				$check_user = $dbDriver->getUser(null, $user['login']);
				if ($check_user !== false)
					$ok = false;
			}

			// password
			if ($ok)
				$ok = isset($user['password']) && is_string($user['password']);
			if ($ok) {
				if (strlen($user['password']) < 6 && strlen($user['password']) > 255)
					$ok = false;

				$half_length = strlen($user['password']) >> 1;
				$handle = fopen("/dev/urandom", "r");
				$data = str_split(fread($handle, 8));
				fclose($handle);

				$user['salt'] = "";
				foreach ($data as $char) {
					$user['salt'] .= sprintf("%02x", ord($char));
				}

				$user['password'] = sha1(substr($user['password'], 0, $half_length) . $user['salt'] . substr($user['password'], $half_length));
			}

			// fullname
			if ($ok)
				$ok = isset($user['fullname']) && is_string($user['fullname']);
			if ($ok) {
				if (strlen($user['fullname']) > 255)
					$ok = false;
			}

			// email
			if ($ok)
				$ok = isset($user['email']) && is_string($user['email']);
			if ($ok) {
				if (strlen($user['email']) > 255 || !filter_var($user['email'], FILTER_VALIDATE_EMAIL))
					$ok = false;
			}

			// homedirectory
			if ($ok)
				$ok = isset($user['homedirectory']) && is_string($user['homedirectory']);

			// isadmin
			if ($ok)
				$ok = isset($user['isadmin']) && is_bool($user['isadmin']);

			// canarchive
			if ($ok)
				$ok = isset($user['canarchive']) && is_bool($user['canarchive']);

			// canrestore
			if ($ok)
				$ok = isset($user['canrestore']) && is_bool($user['canrestore']);

			// meta
			if ($ok)
				$ok = isset($user['meta']) && is_array($user['meta']);
			if ($ok) {
				$user['meta']['step'] = '5';
				$user['meta']['showHelp'] = '1';
			}

			// poolgroup
			if ($ok)
				$ok = isset($user['poolgroup']) && (is_int($user['poolgroup']) || is_null($user['poolgroup']));
			if ($ok && is_int($user['poolgroup'])) {
				$check_poolgroup = $dbDriver->getPoolgroup($user['poolgroup']);
				if (!$check_poolgroup)
					$ok = false;
			}

			// disabled
			if ($ok)
				$ok = isset($user['disabled']) && is_bool($user['disabled']);

			if (!$ok) {
				http_response_code(400);
				echo json_encode(array('message' => 'Bad entries'));
				exit;
			}

			$result = $dbDriver->createUser($user);

			if ($result) {
				http_response_code(200);
				echo json_encode(array(
					'message' => 'User created successfully',
					'user_id' => $result['id']
				));
			} else {
				http_response_code(500);
				echo json_encode(array('message' => 'Query failure'));
			}

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET | HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
