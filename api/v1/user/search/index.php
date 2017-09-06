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
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			$params = array();
			$ok = true;

			if (isset($_GET['login']))
				$params['login'] = $_GET['login'];

			if (isset($_GET['poolgroup'])) {
				$poolgroup = filter_var($_GET['poolgroup'], FILTER_VALIDATE_INT);
				if ($poolgroup !== false)
					$params['poolgroup'] = $poolgroup;
				else
					$ok = false;
			}

			if (isset($_GET['isadmin'])) {
				$isadmin = filter_var($_GET['isadmin'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($isadmin !== null)
					$params['isadmin'] = $isadmin;
				else
					$ok = false;
			}

			if (isset($_GET['canarchive'])) {
				$canarchive = filter_var($_GET['canarchive'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($canarchive !== null)
					$params['canarchive'] = $canarchive;
				else
					$ok = false;
			}

			if (isset($_GET['canrestore'])) {
				$canrestore = filter_var($_GET['canrestore'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($canrestore !== null)
					$params['canrestore'] = $canrestore;
				else
					$ok = false;
			}

			if (isset($_GET['disabled'])) {
				$disabled = filter_var($_GET['disabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($disabled !== null)
					$params['disabled'] = $disabled;
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

			$result = $dbDriver->getUsers($params);

			if ($result['query_prepared'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/user/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/user/search (%d) => getUsers(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/user/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/user/search (%d) => getUsers(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/user/search (%d) => User %s cannot get the ids list of users', __LINE__, $_SESSION['user']['login']), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if ($result['total_rows'] == 0)
				httpResponse(404, array('message' => 'Users not found'));

			httpResponse(200, array(
				'message' => 'Query successful',
				'users' => $result['rows'],
				'total_rows' => $result['total_rows']
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
