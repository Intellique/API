<?php
/**
 * \addtogroup user
 * \page user
 * \section Delete_user User deletion
 * To delete a user,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \param id : user id
 * \return HTTP status codes :
 *   - \b 200 Deletion successful
 *   - \b 400 User id is required
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 User not found
 *   - \b 500 Query failure
 *
 * \section User_Info User information
 * To get user information,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/user/ \endverbatim
 * \param id : user id
 * \return HTTP status codes :
 *   - \b 200 Query successful
 *     \verbatim User information is returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 User not found
 *   - \b 500 Query failure
 *
 * \section Users_id Users ids
 * To get users ids list,
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
 * \warning <b>To get users ids list do not pass an id or ids as parameter</b>
 * \return HTTP status codes :
 *   - \b 200 Query successful
 *     \verbatim Users ids list is returned \endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
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
 * \li \c meta (JSON) : user metadata
 * \li \c poolgroup (integer) : user poolgroup
 * \li \c disabled (boolean) : login rights
 * \return HTTP status codes :
 *   - \b 201 User created successfully
 *     \verbatim New user id is returned \endverbatim
 *   - \b 400 Bad request - Either ; user information is required or incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
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
 * \li \c meta (JSON) : user metadata
 * \li \c poolgroup (integer) : user poolgroup
 * \li \c disabled (boolean) : login rights
 * \return HTTP status codes :
 *   - \b 200 User updated successfully
 *   - \b 400 Bad request - Either ; user information is required or incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 */
	require_once("../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to delete a user', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (!isset($_GET['id']))
				httpResponse(400, array('message' => 'User id is required'));

			if ($_GET['id'] == $_SESSION['user']['id'])
				httpResponse(400, array('message' => 'Suicide forbidden'));

			$check_user = $dbDriver->getUser($_GET['id'], null, true);
			if ($check_user === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/user => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUser(%s, %s)', $_GET['id'], 'null'), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_user === false)
				httpResponse(404, array('message' => 'User not found'));

			$delete_status = $dbDriver->deleteUser($_GET['id']);
			if ($delete_status === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/user => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('deleteUser(%s)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($delete_status === false)
				httpResponse(404, array('message' => 'User not found'));
			else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('User %s deleted', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Deletion successful'));
			}

			break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				$user = $dbDriver->getUser($_GET['id'], null, $_GET['id'] == $_SESSION['user']['id'] || $_SESSION['user']['isadmin']);
				if ($user === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/user => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUser(%s, %s)', $_GET['id'], 'null'), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'user' => array()
					));
				} elseif ($user === false)
					httpResponse(404, array(
						'message' => 'User not found',
						'user' => array()
					));

				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('Getting informations from user %s', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array(
					'message' => 'Query successful',
					'user' => $user
				));
			} elseif ($_SESSION['user']['isadmin']) {
				$params = array();
				$ok = true;

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'login', 'fullname', 'email', 'homedirectory')) !== false)
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

				$users = $dbDriver->getUsers($params);

				if ($users['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/user => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUsers(%s)', $params), $_SESSION['user']['id']);

					httpResponse(500, array(
						'message' => 'Query failure',
						'users' => array(),
						'total_rows' => 0
					));
				}
				$dbDriver->writeLog(DB::DB_LOG_INFO, 'Getting list of users', $_SESSION['user']['id']);
				httpResponse(200, array(
					'message' => 'Query successful',
					'users' => $users['rows'],
					'total_rows' => $users['total_rows']
				));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried get the user id list', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}
			break;

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to create a user', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$user = httpParseInput();
			if ($user === null)
				httpResponse(400, array('message' => 'User information is required'));

			$ok = (bool) $user;
			$failed = false;

			// login
			if ($ok)
				$ok = isset($user['login']) && is_string($user['login']);
			if ($ok) {
				$check_user = $dbDriver->getUser(null, $user['login'], true);
				if ($check_user === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUser(%s, %s)', 'null', $user['login']), $_SESSION['user']['id']);
					$failed = true;
				} elseif ($check_user !== false)
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
			if ($ok && isset($user['meta']))
				$ok = is_array($user['meta']);
			
			elseif ($ok) {
				$user['meta']['step'] = 5;
				$user['meta']['showHelp'] = true;
				$ok = is_array($user['meta']);
			}

			// poolgroup
			if ($ok)
				$ok = array_key_exists('poolgroup', $user) && (is_int($user['poolgroup']) || is_null($user['poolgroup']));
			if ($ok && is_int($user['poolgroup'])) {
				$check_poolgroup = $dbDriver->getPoolgroup($user['poolgroup']);
				if ($check_poolgroup === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolgroup(%s)', $user['poolgroup']), $_SESSION['user']['id']);
					$failed = true;
				}
				elseif ($check_poolgroup === false)
					$ok = false;
			}

			// disabled
			if ($ok)
				$ok = isset($user['disabled']) && is_bool($user['disabled']);

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/user => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$result = $dbDriver->createUser($user);

			if ($result) {
				httpAddLocation('/user/?id=' . $result);
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('User %s created', $result), $_SESSION['user']['id']);
				httpResponse(201, array(
					'message' => 'User created successfully',
					'user_id' => $result
				));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/user => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createUser(%s)', $user), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			break;

		case 'PUT':
			checkConnected();

			$user = httpParseInput();

			if (!isset($user) && $user !== null)
				httpResponse(400, array('message' => 'User information is required'));

			if (!$_SESSION['user']['isadmin'] && ($_SESSION['user']['id'] != $user['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/user => A non-admin user tried to update user informations', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$ok = (bool) $user;
			$failed = false;

			// id
			if ($ok)
				$ok = isset($user['id']) && is_int($user['id']);
			if ($ok) {
				$check_user = $dbDriver->getUser($user['id'], null, true);
				if ($check_user === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUser(%s, %s)', $user['id'], 'null'), $_SESSION['user']['id']);
					$failed = true;
				} elseif ($check_user === false)
					$ok = false;
			}

			// login
			if ($ok)
				$ok = isset($user['login']) && is_string($user['login']);
			if ($ok) {
				$check_user = $dbDriver->getUser(null, $user['login'], true);
				if ($check_user === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUser(%s, %s)', 'null', $user['login']), $_SESSION['user']['id']);
					$failed = true;
				}
				elseif ($check_user !== false && $check_user['id'] != $user['id'])
					$ok = false;
			}

			// password
			if (isset($user['password']))
			{
				$ok = is_string($user['password']);
				if ($ok) {
					$check_user = $dbDriver->getUser($user['id'], null, true);
					if ($check_user === null) {
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUser(%s, %s)', $user['login'], 'null'), $_SESSION['user']['id']);
						$failed = true;
					} elseif ($check_user === false)
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
				if ($check_poolgroup === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolgroup(%s)', $user['poolgroup']), $_SESSION['user']['id']);
					$failed = true;
				}
				elseif ($check_poolgroup === false)
					$ok = false;
			}

			// disabled
			if ($ok)
				$ok = isset($user['disabled']) && is_bool($user['disabled']);

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$result = $dbDriver->updateUser($user);

			if ($result) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('User %s updated', $user['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'User updated successfully'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/user => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updateUser(%s)', var_export($user, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
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
