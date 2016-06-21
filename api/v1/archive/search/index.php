<?php
/**
 * \addtogroup search
 * \section Search_archive Searching archives
 * To search archives and then to get archives ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/archive/search \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name      | string  | search an archive specifying its name                                               |                                 |
 * | owner     | string or integer  | search an archive specifying its owner                                   |                                 |
 * | creator   | string  | search an archive specifying its creator                                            |                                 |
 * | pool      | integer | search an archive specifying its pool                                               |                                 |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'uuid', 'name', 'creator', 'owner' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first four parameters. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Archives ids list is returned
{
   {
   "message":"Query successfull","archives":[2],"total_rows":1
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archives not found
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
				if (!is_string($_GET['name']))
					$ok = false;
				$params['name'] = $_GET['name'];
			}

			if (isset($_GET['owner'])) {
				if (is_numeric($_GET['owner']))
					$params['owner'] = intval($_GET['owner']);
				else if (is_string($_GET['owner']))
					$params['owner'] = $_GET['owner'];
				else
					$ok = false;
			}

			if (isset($_GET['creator'])) {
				if (!is_numeric($_GET['creator']))
					$ok = false;
				$params['creator'] = $_GET['creator'];
			}

			/*if (isset($_GET['pool'])) {
				if(!is_numeric($_GET['pool']))
					$ok = false;
				$params['pool'] = $_GET['pool'];
			}*/

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'uuid', 'name', 'creator', 'owner')) !== false)
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

			$result = $dbDriver->getArchives($_SESSION['user']['id'], $params);
			if ($result['query_prepared'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchives(%s, %s)', $_SESSION['user']['id'], var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'archives' => array(),
					'total_rows' => 0
				));
			}
			if ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchives(%s, %s)', $_SESSION['user']['id'], var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'archives' => array(),
					'total_rows' => 0
				));
			}
			if ($result['total_rows'] == 0)
				httpResponse(404, array(
					'message' => 'Archives not found',
					'archives' => array(),
					'total_rows' => 0,
				));

			httpResponse(200, array(
				'message' => 'Query successful',
				'archives' => $result['rows'],
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