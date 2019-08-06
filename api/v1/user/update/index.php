<?php
/**
 * \addtogroup update
 * \page update
 * \subpage user
 * \To activate or deactivate a user by it's key. An activated user have a key 'Null' while a deactivated user have a key until he is activated.
 * To update users and then to get users ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/user/update \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | login     | string  | update a user specifying its login                                                  |                                 |
 * | isadmin   | string  | update a user specifying if the user is admin or not                                | 't' for true, 'f' for false     |
 * | user      | string  | update a user which is a admin                                                      |                                 |
 * | key       | string  | update a user with his key                                                          | 'NULL' for user activated, 'key' for an not activated user|
 *
 *
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Users ids list is returned
 {
 {
 "message":"Query succeeded","users" => [2]
 }
 }\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Users not found
 *   - \b 500 Query failure
 */
require_once("../../lib/env.php");

require_once("http.php");
require_once("session.php");
require_once("db.php");
require_once("plugins.php");

switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':

		$ok = true;

		if (!isset($_GET['action']))
			$ok = false;

		if ($ok) {
			switch ($_GET['action']) {
				case 'activate':
					if (!isset($_GET['key']))
						httpResponse(400, array('message' => 'key is required'));
					if (!is_string($_GET['key']))
						httpResponse(400, array('message' => 'key must be a string'));

					if (!$dbDriver->startTransaction()) {
						$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('GET api/v1/user/update (%d) => Failed to start transaction', __LINE__));
						httpResponse(500, array('message' => 'Transaction failure'));
					}

					$user = $dbDriver->activateUser($_GET['key']);
					if ($user === null) {
						$dbDriver->cancelTransaction();
						httpResponse(500, array('message' => 'query failure'));
					}
					elseif ($user === false) {
						$dbDriver->cancelTransaction();
						httpResponse(404, array('message' => 'user not found'));
					}
					$returns = triggerEvent('activate User', $user['login']);
					if (!checkEventValues($returns)) {
						$dbDriver->cancelTransaction();
						httpResponse(403, array('message' => 'update abord due by script failure'));
					} elseif (!$dbDriver->finishTransaction()) {
						$dbDriver->cancelTransaction();
						httpResponse(500, array('message' => 'Transaction failure'));
					}
					$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('GET api/v1/user/update (%d) => User %s activated', __LINE__, $user['login']));
					httpResponse(200, array('message' => 'user activated'));

				case 'deactivate':
					checkConnected();

					if (!$_SESSION['user']['isadmin']) {
						$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to delete a user', $_SESSION['user']['id']);
						httpResponse(403, array('message' => 'Permission denied'));
					}

					if (!isset($_GET['login']))
						httpResponse(400, array('message' => 'User login is required'));
					if (!is_string($_GET['login']))
						httpResponse(400, array('message' => 'Login must be a string'));


					if (!$dbDriver->startTransaction()) {
						$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('GET api/v1/user/update (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Transaction failure'));
					}

					$user = $dbDriver->deactivateUser($_GET['login']);
					if ($user === null) {
						$dbDriver->cancelTransaction();
						httpResponse(500, array('message' => 'query failure'));
					}
					elseif ($user === false) {
						$dbDriver->cancelTransaction();
						httpResponse(404, array('message' => 'user not found'));
					}

					$returns = triggerEvent('deactivate User', $user['login']);
					if (!checkEventValues($returns)) {
						$dbDriver->cancelTransaction();
						httpResponse(403, array('message' => 'update abord due by script failure'));
					} elseif (!$dbDriver->finishTransaction()) {
						$dbDriver->cancelTransaction();
						httpResponse(500, array('message' => 'Transaction failure'));
					}
					$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('GET api/v1/user/update (%d) => User %s deactivated', __LINE__, $user['id']), $_SESSION['user']['id']);
					httpResponse(200, array('message' => 'user deactivated'));


				case 'key':
					checkConnected();

					if (!$_SESSION['user']['isadmin']) {
						$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to delete a user', $_SESSION['user']['id']);
						httpResponse(403, array('message' => 'Permission denied'));
					}

					if (!isset($_GET['key']))
						httpResponse(400, array('message' => 'key is required'));
					if (!is_string($_GET['key']))
						httpResponse(400, array('message' => 'key must be a string'));
					if (!isset($_GET['login']))
						httpResponse(400, array('message' => 'login is required'));
					if (!is_string($_GET['login']))
						httpResponse(400, array('message' => 'login must be a string'));

					if (!$dbDriver->startTransaction()) {
						$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('GET api/v1/user/update (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Transaction failure'));
					}

					$user = $dbDriver->addKey($_GET['key'], $_GET['login']);
					if ($user === null) {
						$dbDriver->cancelTransaction();
						httpResponse(500, array('message' => 'query failure'));
					}
					else if ($user === false) {
						$dbDriver->cancelTransaction();
						httpResponse(404, array('message' => 'user not found'));
					}
					$returns = triggerEvent('User key', $user['login'],$user['key']);
					if (!checkEventValues($returns)) {
						$dbDriver->cancelTransaction();
						httpResponse(403, array('message' => 'update abord due by script failure'));
					}
					elseif (!$dbDriver->finishTransaction()) {
						$dbDriver->cancelTransaction();
						httpResponse(500, array('message' => 'Transaction failure'));
					}

					$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('GET api/v1/user/update (%d) => User %s activated', __LINE__, $user['id']), $_SESSION['user']['id']);
					httpResponse(200, array('message' => 'key added'));
				default:
					$ok = false;
					break;
			}
		}
		if (!$ok)
			httpResponse(400, array('message' => 'Incorrect input'));

	case 'OPTIONS':
		httpOptionsMethod(HTTP_GET);
		break;

	default:
		httpUnsupportedMethod();
		break;

}
?>
