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
	require_once("db.php");
	require_once("plugins.php");

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

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('DELETE api/v1/user (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$check_user = $dbDriver->getUserById($_GET['id'], DB::DB_ROW_LOCK_UPDATE);
			if (!$check_user)
				$dbDriver->cancelTransaction();
			if ($check_user === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/user (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/user (%d) => getUser(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_user === false)
				httpResponse(404, array('message' => 'User not found'));

			$params = array("creator" => (int) $_GET['id'], "owner" => (int) $_GET['id']);
			$result = $dbDriver->getArchives($_SESSION['user'], $params);
			if ($result === null) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($result['total_rows'] > 0)
				httpResponse(403, array('message' => 'Deletion denied because it is forbidden to delete an user who had created an archive'));

			$returns = triggerEvent('pre DELETE User', $check_user);
			if (!checkEventValues($returns)) {
				$dbDriver->cancelTransaction();
				httpResponse(403, array('message' => 'Deletion abord by script request'));
			}

			$delete_status = $dbDriver->deleteUser($_GET['id']);
			if ($delete_status === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/user (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/user (%d) => deleteUser(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($delete_status === false) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'User not found'));
			}

			$returns = triggerEvent('post DELETE User', $check_user);
			if (!checkEventValues($returns)) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'script failed to delete user'));
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/user (%d) => Query User %s deleted', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Deletion successful'));
			}

			break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
					httpResponse(400, array('message' => 'User id must be an integer'));

				$user = $dbDriver->getUser($_GET['id'], null, $_GET['id'] == $_SESSION['user']['id'] || $_SESSION['user']['isadmin']);
				if ($user === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/user (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/user (%d) => getUser(%s, %s)', __LINE__, $_GET['id'], 'null'), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'user' => array()
					));
				} elseif ($user === false)
					httpResponse(404, array(
						'message' => 'User not found',
						'user' => array()
					));

				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('GET api/v1/user (%d) => Getting informations from user %s', __LINE__, $_GET['id']), $_SESSION['user']['id']);
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
					$limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 1)));
					if ($limit !== false)
						$params['limit'] = $limit;
					else
						$ok = false;
				}

				if (isset($_GET['offset'])) {
					$offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 0)));
					if ($offset !== false)
						$params['offset'] = $offset;
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$users = $dbDriver->getUsers($params);
				if ($users['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/user (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/user (%d) => getUsers(%s)', __LINE__, $params), $_SESSION['user']['id']);

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

			$returns = triggerEvent('pre POST User', $user);
			if (!checkEventValues($returns))
				httpResponse(403, array('message' => 'creation abord by script request'));

			// login
			if ($ok)
				$ok = isset($user['login']) && is_string($user['login']);
			if ($ok) {
				$check_user = $dbDriver->getUser(null, $user['login'], true);
				if ($check_user === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/user (%d) => getUser(%s, %s)', __LINE__, 'null', $user['login']), $_SESSION['user']['id']);
					$failed = true;
				} elseif ($check_user !== false)
					$ok = false;
			}

			// password
			$password = null;
			if ($ok)
				$ok = isset($user['password']) && is_string($user['password']);
			if ($ok) {
				if (strlen($user['password']) < 6)
					$ok = false;

				$password = $user['password'];
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
				$check_poolgroup = $dbDriver->getPoolGroup($user['poolgroup']);
				if ($check_poolgroup === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/user (%d) => getPoolGroup(%s)', __LINE__, $user['poolgroup']), $_SESSION['user']['id']);
					$failed = true;
				} elseif ($check_poolgroup === false)
					$ok = false;
			}

			// disabled
			if ($ok)
				$ok = isset($user['disabled']) && is_bool($user['disabled']);

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/user (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/user (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$result = $dbDriver->createUser($user);
			if ($result) {
				$returns = triggerEvent('post POST User', $user, $password);
				if (!checkEventValues($returns)) {
					$dbDriver->cancelTransaction();
					httpResponse(400, array('message' => 'creation abord by script failure'));
				} elseif (!$dbDriver->finishTransaction()) {
					$dbDriver->cancelTransaction();
					httpResponse(500, array('message' => 'Transaction failure'));
				}

				httpAddLocation('/user/?id=' . $result);
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/user (%d) => User %s created', __LINE__, $result), $_SESSION['user']['id']);
				httpResponse(201, array(
					'message' => 'User created successfully',
					'user_id' => $result
				));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/user (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
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
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/user (%d) => A non-admin user tried to update user informations', __LINE__), $_SESSION['user']['id']);
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
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/user (%d) => getUser(%s, %s)', __LINE__, $user['id'], 'null'), $_SESSION['user']['id']);
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
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/user (%d) => getUser(null, %s)', __LINE__, $user['login']), $_SESSION['user']['id']);
					$failed = true;
				} elseif ($check_user !== false && $check_user['id'] != $user['id'])
					$ok = false;
			}

			// password
			$new_password = null;
			if (isset($user['password'])) {
				$ok = is_string($user['password']);
				if ($ok) {
					$check_user = $dbDriver->getUser($user['id'], null, true);
					if ($check_user === null) {
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/user (%d) => getUser(%s, %s)', __LINE__, $user['login'], 'null'), $_SESSION['user']['id']);
						$failed = true;
					} elseif ($check_user === false)
						$ok = false;
				}

				if ($ok && !$failed && $user['password'] != $check_user['password']) {
					if (strlen($user['password']) < 6)
						$ok = false;

					$new_password = $user['password'];

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
				$check_poolgroup = $dbDriver->getPoolGroup($user['poolgroup']);
				if ($check_poolgroup === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/user (%d) => getPoolGroup(%s)', __LINE__, $user['poolgroup']), $_SESSION['user']['id']);
					$failed = true;
				} elseif ($check_poolgroup === false)
					$ok = false;
			}

			// disabled
			if ($ok)
				$ok = isset($user['disabled']) && is_bool($user['disabled']);

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive (%d) => Query failure', __LINE__, $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$returns = triggerEvent('pre PUT User', $check_user, $user, $new_password);
			if (!checkEventValues($returns))
				httpResponse(403, array('message' => 'update abord by script request'));

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('PUT api/v1/user (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$result = $dbDriver->updateUser($user);
			if ($result) {
				$returns = triggerEvent('post PUT User', $check_user, $user, $new_password);
				if (!checkEventValues($returns)) {
					$dbDriver->cancelTransaction();
					httpResponse(403, array('message' => 'update abord due by script failure'));
				} elseif (!$dbDriver->finishTransaction()) {
					$dbDriver->cancelTransaction();
					httpResponse(500, array('message' => 'Transaction failure'));
				}

				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('PUT api/v1/archive (%d) => User %s updated', __LINE__, $user['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'User updated successfully'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/user (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/user (%d) => updateUser(%s)', __LINE__, var_export($user, true)), $_SESSION['user']['id']);
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
