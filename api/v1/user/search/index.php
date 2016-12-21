<?php
/**
 * \addtogroup search
 * \page search
 * \subpage user 
 * \section Search_user Searching users
 * To search users and then to get users ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/user/search \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | login     | string  | search a user specifying its login                                                  |                                 |
 * | poolgroup | integer | search a user specifying its poolgroup                                              |                                 |
 * | isadmin   | string  | search a user specifying if the user is admin or not                                | 't' for true, 'f' for false     |
 * | canarchive | string | search a user specifying if the user can archive or not                             | 't' for true, 'f' for false     |
 * | canrestore | string | search a user specifying if the user can restore or not                             | 't' for true, 'f' for false     |
 * | disabled  | string  | search a user specifying if the user is disabled or not                             | 't' for true, 'f' for false     |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'uuid', 'name' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first six parameters to make a specific search. Otherwise, do not pass them to get the complete list.</b>
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
	require_once("dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			$params = array();
			$ok = true;
			if (isset($_GET['login'])) {
				if (is_string($_GET['login']))
					$params['login'] = $_GET['login'];
				else
					$ok = false;
			}

			if (isset($_GET['poolgroup'])) {
				if (is_numeric($_GET['poolgroup']))
					$params['poolgroup'] = $_GET['poolgroup'];
				else
					$ok = false;
			}

			if (isset($_GET['isadmin'])) {
				if (is_string($_GET['isadmin']) && ($_GET['isadmin'] === 't' || $_GET['isadmin'] === 'f'))
					$params['isadmin'] = $_GET['isadmin'];
				else
					$ok = false;
			}

			if (isset($_GET['canarchive'])) {
				if (is_string($_GET['canarchive']) && ($_GET['canarchive'] === 't' || $_GET['canarchive'] === 'f'))
					$params['canarchive'] = $_GET['canarchive'];
				else
					$ok = false;
			}

			if (isset($_GET['canrestore'])) {
				if (is_string($_GET['canrestore']) && ($_GET['canrestore'] === 't' || $_GET['canrestore'] === 'f'))
					$params['canrestore'] = $_GET['canrestore'];
				else
					$ok = false;
			}

			if (isset($_GET['disabled'])) {
				if (is_string($_GET['disabled']) && ($_GET['disabled'] === 't' || $_GET['disabled'] === 'f'))
					$params['disabled'] = $_GET['disabled'];
				else
					$ok = false;
			}

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'login', 'fullname', 'poolgroup')) !== false)
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

			$result = $dbDriver->getUsers($params);

			if ($result['query_prepared'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/user/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUsers(%s)', var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/user/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUsers(%s)', var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('User %s cannot get the ids list of users', $_SESSION['user']['login']), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}
			if ($result['total_rows'] == 0)
				httpResponse(404, array('message' => 'Users not found'));

			httpResponse(200, array(
				'message' => 'Query successful',
				'users' => $result['rows']
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
