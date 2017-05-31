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

			if (isset($_GET['pool'])) {
				if (is_numeric($_GET['pool']))
					$params['pool'] = $_GET['pool'];
				else
					$ok = false;
			}

			if (isset($_GET['nbfiles'])) {
				if (is_numeric($_GET['nbfiles']))
					$params['nbfiles'] = $_GET['nbfiles'];
				else
					$ok = false;
			}

			if (isset($_GET['archiveformat'])) {
				if (is_numeric($_GET['archiveformat']))
					$params['archiveformat'] = $_GET['archiveformat'];
				else
					$ok = false;
			}

			if (isset($_GET['mediaformat'])) {
				if (is_numeric($_GET['mediaformat']))
					$params['mediaformat'] = $_GET['mediaformat'];
				else
					$ok = false;
			}

			if (isset($_GET['type'])) {
				if (is_string($_GET['type']))
					$params['type'] = $_GET['type'];
				else
					$ok = false;
			}

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

			$medias = $dbDriver->getMediasByParams($params);

			if ($medias === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media/search => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediasByParams(%s)', var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($medias === false)
				httpResponse(404, array('message' => 'Medias not found',
							'medias' => array()
				));

			$result = array();
			$permission = $_SESSION['user']['isadmin'] || $_SESSION['user']['canarchive'];
			foreach ($medias['rows'] as $media) {
				if ($media['pool'] ==! null) {
					$permission_pool = $dbDriver->checkPoolPermission($media['pool'], $_SESSION['user']['id']);
					if ($permission_pool === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media/serach => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkPoolPermission(%s, %s)', $media['pool'], $_SESSION['user']['id']), $_SESSION['user']['id']);
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
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'GET api/v1/media/search => A user that cannot get media informations tried to', $_SESSION['user']['id']);
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
