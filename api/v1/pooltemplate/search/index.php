<?php
/**
 * \addtogroup search
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
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {

		case 'GET':
			checkConnected();

			$params = array();
			$ok = true;
			if (isset($_GET['name'])) {
				if (is_string($_GET['name']))
					$params['name'] = $_GET['name'];
				else
					$ok = false;
			}

			if (isset($_GET['autocheck'])) {
				if (is_string($_GET['autocheck']) && ($_GET['autocheck'] === 't' || $_GET['autocheck'] === 'f'))
					$params['autocheck'] = $_GET['autocheck'];
				else
					$ok = false;
			}

			if (isset($_GET['lockcheck'])) {
				if (is_string($_GET['lockcheck']) && ($_GET['lockcheck'] === 't' || $_GET['lockcheck'] === 'f'))
					$params['lockcheck'] = $_GET['lockcheck'];
				else
					$ok = false;
			}

			if (isset($_GET['rewritable'])) {
				if (is_string($_GET['rewritable']) && ($_GET['rewritable'] === 't' || $_GET['rewritable'] === 'f'))
					$params['rewritable'] = $_GET['rewritable'];
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

			$result = $dbDriver->getPoolTemplates($params);

			if ($result['query_prepared'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pooltemplate/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolTemplates(%s)', var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pooltemplate/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolTemplates(%s)', var_export($params, true)), $_SESSION['user']['id']);
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