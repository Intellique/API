<?php
/**
 * \addtogroup search
 * \page search
 * \subpage searcharchive Search Archive
 * \section Search_archive Searching archives
 * To search archives and then to get archives ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/archive/search \endverbatim

 * <b>Optional parameters</b>
 * |    Name     |  Type             |                                  Description                                        |           Constraint            |
 * | :---------: | :---------------: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name        | string            | search an archive specifying its name                                               |                                 |
 * | uuid        | string            | search an archive specifying its uuid                                               |                                 |
 * | owner       | string or integer | search an archive specifying its owner                                              |                                 |
 * | creator     | string or integer | search an archive specifying its creator                                            |                                 |
 * | archivefile | string or integer | search an archive specifying its file                                               |                                 |
 * | media       | integer           | search an archive specifying its media                                              |                                 |
 * | pool        | string or integer | search an archive specifying its pool                                               |                                 |
 * | poolgroup   | integer           | search an archive specifying its poolgroup                                          |                                 |
 * | order_by    | enum              | order by column                                                                     | value in : 'id', 'uuid', 'name', 'creator', 'owner' |
 * | order_asc   | boolean           | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit       | integer           | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset      | integer           | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 * | status      | string            | search archive files given the status                                               | status = checked || status = not_checked || status = not_ok |
 *
 * \warning <b>Make sure to pass at least one of the first four parameters. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Archives ids list is returned
{
   {
   "message":"Query successful","archives":[2],"total_rows":1
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
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			$params = array();
			$ok = true;

			if (isset($_GET['archivefile'])) {
				$archivefile = filter_var($_GET['archivefile'], FILTER_VALIDATE_INT);
				if ($archivefile !== false)
					$params['archivefile'] = $archivefile;
				else
					$params['archivefile'] = $_GET['archivefile'];
			}

			if (isset($_GET['creator'])) {
				$creator = filter_var($_GET['creator'], FILTER_VALIDATE_INT);
				if ($creator !== false)
					$params['creator'] = $creator;
				else
					$params['creator'] = $_GET['creator'];
			}

			if (isset($_GET['deleted'])) {
				if ($_SESSION['user']['isadmin']) {
					if (false !== array_search($_GET['deleted'], array('yes', 'no', 'only')))
						$params['deleted'] = $_GET['deleted'];
					else
						$ok = false;
				} else
					httpResponse(403, array('message' => 'Permission denied'));
			} else
				$params['deleted'] = 'no';

			if (isset($_GET['media'])) {
				$media = filter_var($_GET['media'], FILTER_VALIDATE_INT);
				if ($media !== false)
					$params['media'] = $media;
				else
					$ok = false;
			}

			if (isset($_GET['name']))
				$params['name'] = $_GET['name'];

			if (isset($_GET['owner'])) {
				$owner = filter_var($_GET['owner'], FILTER_VALIDATE_INT);
				if ($owner !== false)
					$params['owner'] = $owner;
				else
					$params['owner'] = $_GET['owner'];
			}

			if (isset($_GET['pool'])) {
				$pool = filter_var($_GET['pool'], FILTER_VALIDATE_INT);
				if ($pool !== false)
					$params['pool'] = $pool;
				else
					$params['pool'] = $_GET['pool'];
			}

			if (isset($_GET['poolgroup'])) {
				if ($_SESSION['user']['isadmin']) {
					$poolgroup = filter_var($_GET['poolgroup'], FILTER_VALIDATE_INT);
					if ($poolgroup !== false)
						$params['poolgroup'] = $poolgroup;
					else
						$ok = false;
				} else
					httpResponse(403, array('message' => 'Permission denied'));
			} else
				$params['poolgroup'] = $_SESSION['user']['poolgroup'];

			if (isset($_GET['uuid']))
				$params['uuid'] = $_GET['uuid'];

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

			/* ETAT DE VERIFICATION */

			if (isset($_GET['status'])) {
				switch ($_GET['status']) {
					case 'checked':
					case 'not_checked':
					case 'not_ok':
						$params['status'] = $_GET['status'];
						break;
					default:
						$ok = false;
						break;
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

			$result = $dbDriver->getArchives($_SESSION['user'], $params);
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
					'total_rows' => 0
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
