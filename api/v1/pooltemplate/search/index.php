<?php
/**
 * \addtogroup search
 * \page search
 * \subpage searchpooltemplate
 * \section Search_pooltemplate Searching pool templates
 * To search pool templates and then to get pool templates ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/pooltemplate/search \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name      | string  | search a pool template specifying its name                                          |                                 |
 * | autocheck | string  | search pool templates specifying their autocheck                                    |  't' for true, 'f' for false    |
 * | lockcheck | string  | search pool templates specifying their lockcheck                                    |  't' for true, 'f' for false    |
 * | rewritable | string | search pool templates specifying if they are rewritable                             |  't' for true, 'f' for false    |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'name' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first four parameters to make a specific search. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Pool templates ids list is returned
{
   {
   "message":"Query succeeded","pooltemplates" => [2]
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Pool templates not found
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

			if (isset($_GET['autocheck'])) {
				$autocheck = filter_var($_GET['autocheck'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($autocheck !== null)
					$params['autocheck'] = $autocheck;
				else
					$ok = false;
			}

			if (isset($_GET['lockcheck'])) {
				$lockcheck = filter_var($_GET['lockcheck'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($lockcheck !== null)
					$params['lockcheck'] = $lockcheck;
				else
					$ok = false;
			}

			if (isset($_GET['rewritable'])) {
				$rewritable = filter_var($_GET['rewritable'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($rewritable !== null)
					$params['rewritable'] = $rewritable;
				else
					$ok = false;
			}

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'name')) !== false)
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

			$result = $dbDriver->getPoolTemplates($params);
			if ($result['query_prepared'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pooltemplate/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pooltemplate/search (%d) => getPoolTemplates(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pooltemplate/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pooltemplate/search (%d) => getPoolTemplates(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if ($result['total_rows'] == 0)
				httpResponse(404, array('message' => 'Pool templates not found'));

			httpResponse(200, array(
				'message' => 'Query successful',
				'pooltemplates' => $result['rows']
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
