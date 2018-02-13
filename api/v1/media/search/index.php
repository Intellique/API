<?php
/**
 * \addtogroup search
 * \page search
 * \subpage searchmedia Search Media
 * \section Search_medias Searching medias
 * To search medias and then to get medias ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/media/search \endverbatim

 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name      | string  | search a media specifying its name                                                   |                                 |
 * | pool      | integer | search a media specifying its pool                                                   |                                 |
 * | nbfiles   | integer | search a media specifying the number of files                                        |                                 |
 * | archiveformat | integer | search a media specifying its archiveformat                                      |                                 |
 * | mediaformat | integer | search a media specifying its mediaformat                                          |                                 |
 * | type      | string | search a media specifying its type                                                    |                                 |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'pool', 'nbfiles', 'poolgroup' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first six parameters. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Medias ids list is returned
{
   {
   "message":"Query succeeded","medias" => [2]
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Pools not found
 *   - \b 500 Query failure
 */
	require_once("../../lib/env.php");

	require_once("dateTime.php");
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

			if (isset($_GET['pool'])) {
				if (filter_var($_GET['pool'], FILTER_VALIDATE_INT) !== false)
					$params['pool'] = $_GET['pool'];
				else
					$ok = false;
			}

			if (isset($_GET['archiveformat'])) {
				if (filter_var($_GET['archiveformat'], FILTER_VALIDATE_INT) !== false)
					$params['archiveformat'] = $_GET['archiveformat'];
				else
					$ok = false;
			}

			if (isset($_GET['mediaformat'])) {
				if (filter_var($_GET['mediaformat'], FILTER_VALIDATE_INT) !== false)
					$params['mediaformat'] = $_GET['mediaformat'];
				else
					$ok = false;
			}

			if (isset($_GET['status']))
				$params['status'] = $_GET['status'];

			if (isset($_GET['type']))
				$params['type'] = $_GET['type'];

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'pool', 'nbfiles', 'poolgroup')) !== false)
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

			$medias = $dbDriver->getMediasByParams($params);
			if (!$medias['query_executed']) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/media/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/media/search (%d) => getMediasByParams(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure'
				));
			} elseif ($medias['total_rows'] == 0)
				httpResponse(404, array(
					'message' => 'Medias not found',
					'medias' => array()
				));

			$result = array();
			$permission = $_SESSION['user']['isadmin'] || $_SESSION['user']['canarchive'];
			foreach ($medias['rows'] as $media) {
				if ($media['pool'] ==! null) {
					$permission_pool = $dbDriver->checkPoolPermission($media['pool'], $_SESSION['user']['id']);
					if ($permission_pool === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/media/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/media/search (%d) => checkPoolPermission(%s, %s)', __LINE__, $media['pool'], $_SESSION['user']['id']), $_SESSION['user']['id']);
						httpResponse(500, array(
							'message' => 'Query failure',
							'medias' => array()
						));
					}

					$permission_granted = ($permission_pool || $permission);
					if ($permission_granted === true)
						$result[] = $media['id'];
				} else if ($permission)
					$result[] = $media['id'];
			}

			if (count($result) == 0) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/media/search (%d) => A user that cannot get media informations tried to', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			httpResponse(200, array(
				'message' => 'Query succeeded',
				'medias' => &$result,
				'total_rows' => $medias['total_rows']
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
