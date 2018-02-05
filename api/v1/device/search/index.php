<?php
/**
 * \addtogroup search
 * \page search
 * \subpage devicesearch
 * \section Search_device Searching devices
 * To search devices and then to get devices ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/device/search \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | isonline  | string  | search a device specifying if it is online                                          |  't' for true, 'f' for false    |
 * | enable    | string  | search a device specifying if it is enabled                                         |  't' for true, 'f' for false    |
 * | model     | string  | search a device specifying its model                                                |                                 |
 * | vendor    | string  | search a device specifying its vendor                                               |                                 |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'model', 'vendor' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first four parameters. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim devices ids list is returned
{
   {
   "message":"Query successful","devices":[2],"total_rows":1
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Devices not found
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

			if (isset($_GET['isonline'])) {
				$isonline = filter_var($_GET['isonline'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($isonline !== null)
					$params['isonline'] = $isonline;
				else
					$ok = false;
			}

			if (isset($_GET['enable'])) {
				$enable = filter_var($_GET['enable'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				if ($enable !== null)
					$params['enable'] = $enable;
				else
					$ok = false;
			}

			if (isset($_GET['model']))
				$params['model'] = $_GET['model'];

			if (isset($_GET['vendor']))
				$params['vendor'] = $_GET['vendor'];

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'model', 'vendor')) !== false)
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

			$result = $dbDriver->getDevicesByParams($params);
			if ($result['query_prepared'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/device/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/device/search (%d) => getDevicesByParams(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);

				httpResponse(500, array(
					'message' => 'Query failure',
					'devices' => array(),
					'total_rows' => 0
				));
			} elseif ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/device/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/device/search (%d) => getDevicesByParams(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);

				httpResponse(500, array(
					'message' => 'Query failure',
					'devices' => array(),
					'total_rows' => 0
				));
			}

			if ($result['total_rows'] == 0)
				httpResponse(404, array(
					'message' => 'Devices not found',
					'devices' => array(),
					'total_rows' => 0
				));

			httpResponse(200, array(
				'message' => 'Query successful',
				'devices' => $result['rows'],
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
