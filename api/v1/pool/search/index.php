<?php
/**
 * \addtogroup search
 * \page search
 * \subpage poolsearch
 * \section Search_pool Searching pools
 * To search pools and then to get pools ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/pool/search \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name      | string  | search a pool specifying its name                                                   |                                 |
 * | poolgroup | integer | search a pool specifying its poolgroup                                              |                                 |
 * | mediaformat | integer | search a pool specifying its mediaformat                                          |                                 |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'uuid', 'name' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first three parameters. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Pools ids list is returned
{
   {
   "message":"Query succeeded","pools" => [2]
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Pools not found
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

			if (!$_SESSION['user']['isadmin'])
				$params['poolgroup'] = $_SESSION['user']['poolgroup'];
			elseif (isset($_GET['poolgroup'])) {
				$poolgroup = filter_var($_GET['poolgroup'], FILTER_VALIDATE_INT);
				if ($poolgroup === false)
					$ok = false;
				else
					$params['poolgroup'] = $poolgroup;
			}

			if (isset($_GET['mediaformat'])) {
				$mediaformat = filter_var($_GET['mediaformat'], FILTER_VALIDATE_INT);
				if ($mediaformat === false)
					$ok = false;
				else
					$params['mediaformat'] = $mediaformat;
			}

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'uuid', 'name')) !== false)
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

			$pools = $dbDriver->getPoolsByParams($params);
			if ($pools['query executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pool/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pool/search (%d) => getPoolByParams(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($pools['total rows'] === 0)
				httpResponse(404, array(
					'message' => 'Pools not found',
					'pools' => array(),
					"debug" => &$params
				));

			httpResponse(200, array(
				'message' => 'Query succeeded',
				'pools' => $pools['rows'],
				'total_rows' => $pools['total rows']
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
