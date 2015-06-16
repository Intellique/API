<?php
/**
 * \addtogroup user
 * \section Delete_user User deletion
 * To delete a user,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \param id : user ID
 * \return HTTP status codes :
 *   - \b 200 Deletion successfull
 *   - \b 400 User ID required
 *   - \b 403 Permission denied
 *   - \b 404 User not found
 *   - \b 500 Query failure
 *
 * \section User_Info User information
 * To get user information,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \param id : user ID
 * \return HTTP status codes :
 *   - \b 200 Query successfull
 *     \verbatim User information is returned \endverbatim
 *   - \b 401 Permission denied
 *   - \b 404 User not found
 *   - \b 500 Query failure
 *
 * \section Users_ID Users ID
 * To get users ID list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name   |  Type   |                                  Description                                        |                               Constraint                               |
 * | :------: | :-----: | :---------------------------------------------------------------------------------: | :--------------------------------------------------------------------: |
 * | order_by | enum    |order by column                                                                      | single value from : 'id', 'login', 'fullname', 'email', 'homedirectory'|
 * | order_asc| boolean |\b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing|                        |
 * | limit    | integer |specifies the maximum number of rows to return                                       | limit > 0                                                              |
 * | offset   | integer |specifies the number of rows to skip before starting to return rows                  | offset >= 0                                                            |
 *
 * \warning To get users ID list do not pass an id or ids as parameter
 * \return HTTP status codes :
 *   - \b 200 Query successfull
 *     \verbatim Users ID list is returned \endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Permission denied
 *   - \b 500 Query failure
 *
 * \section Create_user User creation
 * To create a user,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \param user : JSON encoded object
 * \li \c login (string) : user login
 * \li \c password (string) : user password
 * \li \c fullname (string) : user fullname
 * \li \c email (string) : user email
 * \li \c homedirectory (string) : user homedirectory
 * \li \c isadmin (boolean) : administration rights
 * \li \c canarchive (boolean) : archive rights
 * \li \c canrestore (boolean) : restoration rights
 * \li \c meta (object) : user metadata
 * \li \c poolgroup (integer) : user poolgroup
 * \li \c disabled (boolean) : login rights
 * \return HTTP status codes :
 *   - \b 200 User created successfully
 *     \verbatim New user ID is returned \endverbatim
 *   - \b 400 User information required or incorrect input
 *   - \b 401 Permission denied
 *   - \b 500 Query failure
 *
 * \section Update_user User update
 * To update a user,
 * use \b PUT method
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \param user : JSON encoded object
 * \li \c id (integer) : user id
 * \li \c login (string) : user login
 * \li \c password (string) : user password
 * \li \c fullname (string) : user fullname
 * \li \c email (string) : user email
 * \li \c homedirectory (string) : user homedirectory
 * \li \c isadmin (boolean) : administration rights
 * \li \c canarchive (boolean) : archive rights
 * \li \c canrestore (boolean) : restoration rights
 * \li \c meta (object) : user metadata
 * \li \c poolgroup (integer) : user poolgroup
 * \li \c disabled (boolean) : login rights
 * \return HTTP status codes :
 *   - \b 200 User updated successfully
 *   - \b 400 User information required or incorrect input
 *   - \b 401 Permission denied
 *   - \b 500 Query failure
 */
	require_once("../lib/http.php");
	require_once("../lib/session.php");
	require_once("../lib/dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				http_response_code(403);
				echo json_encode(array('message' => 'Permission denied'));
				exit;
			}

			if (!isset($_GET['id'])) {
				http_response_code(400);
				echo json_encode(array('message' => 'User id required'));
				exit;
			}

			if ($_GET['id'] == $_SESSION['user']['id']) {
				http_response_code(400);
				echo json_encode(array('message' => 'Suicide forbidden'));
				exit;
			}

			$check_user = $dbDriver->getUser($_GET['id'], null);
			if ($check_user === null) {
				http_response_code(500);
				echo json_encode(array('message' => 'Query failure'));
				exit;
			} elseif ($check_user === false) {
				http_response_code(404);
				echo json_encode(array('message' => 'User not found'));
				exit;
			}

			$delete_status = $dbDriver->deleteUser($_GET['id']);
			if ($delete_status === null) {
				http_response_code(500);
				echo json_encode(array('message' => 'Query failure'));
				exit;
			} elseif ($delete_status === false) {
				http_response_code(404);
				echo json_encode(array('message' => 'User not found'));
				exit;
			}
			http_response_code(200);
			echo json_encode(array('message' => 'Deletion successfull'));

			break;

		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (isset($_GET['id'])) {
				if ($_GET['id'] == $_SESSION['user']['id'] || $_SESSION['user']['isadmin']) {
					$user = $dbDriver->getUser($_GET['id'], null);
					if ($user === null) {
						http_response_code(500);
						echo json_encode(array(
							'message' => 'Query failure',
							'user' => array()
						));
						exit;
					} elseif ($user === false) {
						http_response_code(404);
						echo json_encode(array(
							'message' => 'User not found',
							'user' => array()
						));
						exit;
					}

					http_response_code(200);
					echo json_encode(array(
						'message' => 'Query successfull',
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
				$params = array();
				$ok = true;

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'login', 'fullname', 'email', 'homedirectory')))
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
					if (is_integer($_GET['offset']) && $_GET['offset'] >= 0)
						$params['offset'] = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok) {
					http_response_code(400);
					echo json_encode(array('message' => 'Incorrect input'));
					exit;
				}

				$users = $dbDriver->getUsers($params);

				if ($users['query executed'] == false) {
					http_response_code(500);
					echo json_encode(array(
						'message' => 'Query failure',
						'users_id' => array(),
						'total rows' => 0
					));
					exit;
				}

				http_response_code(200);
				echo json_encode(array(
					'message' => 'Query successfull',
					'users_id' => $users['rows'],
					'total rows' => $users['total_rows']
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
				echo json_encode(array('message' => 'User information is required'));
				exit;
			}

			$user = json_decode($_POST['user'], true);
			$ok = (bool) $user;
			$failed = false;

			// login
			if ($ok)
				$ok = isset($user['login']) && is_string($user['login']);
			if ($ok) {
				$check_user = $dbDriver->getUser(null, $user['login']);
				if ($check_user === null)
					$failed = true;
				elseif ($check_user !== false)
					$ok = false;
			}

			// password
			if ($ok)
				$ok = isset($user['password']) && is_string($user['password']);
			if ($ok) {
				if (strlen($user['password']) < 6)
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

			// metadata
			if ($ok)
				$ok = isset($user['meta']) && is_array($user['meta']);
			if ($ok) {
				$user['meta']['step'] = '5';
				$user['meta']['showHelp'] = '1';
			}

			// poolgroup
			if ($ok)
				$ok = array_key_exists('poolgroup', $user) && (is_int($user['poolgroup']) || is_null($user['poolgroup']));
			if ($ok && is_int($user['poolgroup'])) {
				$check_poolgroup = $dbDriver->getPoolgroup($user['poolgroup']);
				if ($check_poolgroup === null)
					$failed = true;
				elseif ($check_poolgroup === false)
					$ok = false;
			}

			// disabled
			if ($ok)
				$ok = isset($user['disabled']) && is_bool($user['disabled']);

			// gestion des erreurs
			if ($failed) {
				http_response_code(500);
				echo json_encode(array('message' => 'Query failure'));
				exit;
			}
			if (!$ok) {
				http_response_code(400);
				echo json_encode(array('message' => 'Incorrect input'));
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

		case 'PUT':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			$json = file_get_contents("php://input");
			$user = json_decode($json, true);

			if (!isset($user) && $user !== null) {
				http_response_code(400);
				echo json_encode(array('message' => 'User information is required'));
				exit;
			}

			if (!$_SESSION['user']['isadmin'] && ($_SESSION['user']['id'] != $user['id'])) {
				http_response_code(401);
				echo json_encode(array('message' => 'Permission denied'));
				exit;
			}

			$ok = (bool) $user;
			$failed = false;

			// id
			if ($ok)
				$ok = isset($user['id']) && is_int($user['id']);
			if ($ok) {
				$check_user = $dbDriver->getUser($user['id'], null);
				if ($check_user === null)
					$failed = true;
				elseif ($check_user === false)
					$ok = false;
			}

			// login
			if ($ok)
				$ok = isset($user['login']) && is_string($user['login']);
			if ($ok) {
				$check_user = $dbDriver->getUser(null, $user['login']);
				if ($check_user === null)
					$failed = true;
				elseif ($check_user !== false && $check_user['id'] != $user['id'])
					$ok = false;
			}

			// password
			if ($ok)
				$ok = isset($user['password']) && is_string($user['password']);
			if ($ok) {
				$check_user = $dbDriver->getUser($user['id'], null);
				if ($check_user === null)
					$failed = true;
				elseif ($check_user === false)
					$ok = false;

				if ($ok && !$failed && $user['password'] != $check_user['password']) {
					if (strlen($user['password']) < 6)
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

			// metadata
			if ($ok)
				$ok = isset($user['meta']) && is_array($user['meta']);

			// poolgroup
			if ($ok)
				$ok = array_key_exists('poolgroup', $user) && (is_int($user['poolgroup']) || is_null($user['poolgroup']));
			if ($ok && is_int($user['poolgroup'])) {
				$check_poolgroup = $dbDriver->getPoolgroup($user['poolgroup']);
				if ($check_poolgroup === null)
					$failed = true;
				elseif ($check_poolgroup === false)
					$ok = false;
			}

			// disabled
			if ($ok)
				$ok = isset($user['disabled']) && is_bool($user['disabled']);

			// gestion des erreurs
			if ($failed) {
				http_response_code(500);
				echo json_encode(array('message' => 'Query failure'));
				exit;
			}
			if (!$ok) {
				http_response_code(400);
				echo json_encode(array('message' => 'Incorrect input'));
				exit;
			}

			$result = $dbDriver->updateUser($user);

			if ($result) {
				http_response_code(200);
				echo json_encode(array('message' => 'User updated successfully'));
			} else {
				http_response_code(500);
				echo json_encode(array('message' => 'Query failure'));
			}

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_ALL_METHODS);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
