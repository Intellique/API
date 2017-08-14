<?php
/**
 * \addtogroup search
 * \page search
 * \subpage poolgroup 
 * \section Search_poolgroup Searching poolgroups
 * To search pollgroups and then to get poolgroups ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/poolgroup/search \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name      | string  | search a poolgroup specifying its name                                              |                                 |
 * | uuid      | string  | search a poolgroup specifying its uuid                                              |                                 |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'uuid', 'name' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first two parameters to make a specific search. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Poolgroups ids list is returned
{
   {
   "message":"Query succeeded","poolgroups" => [2]
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

			if (isset($_GET['name']))
				$params['name'] = $_GET['name'];

			if (isset($_GET['uuid']))
				$params['uuid'] = $_GET['uuid'];

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
				$limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT, array('min_range' => 1));
				if ($limit !== false)
					$params['limit'] = $limit;
				else
					$ok = false;
			}

			if (isset($_GET['offset'])) {
				$offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT, array('min_range' => 0));
				if ($offset !== false)
					$params['offset'] = $offset;
				else
					$ok = false;
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$result = $dbDriver->getPoolgroups($params);
			if ($result['query_prepared'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/poolgroup/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolgroup/search (%d) => getPoolgroups(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/oolgroup/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/oolgroup/search (%d) => getPoolgroups(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/oolgroup/search (%d) => User %s cannot get the ids list of poolgroups', __LINE__, $_SESSION['user']['login']), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}
			if ($result['total_rows'] == 0)
				httpResponse(404, array('message' => 'Poolgroups not found'));

			httpResponse(200, array(
				'message' => 'Query successful',
				'poolgroups' => $result['rows'],
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
