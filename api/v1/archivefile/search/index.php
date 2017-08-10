<?php
/**
 * \addtogroup search
 * \page search
 * \subpage searcharchivefile Search Archive File
 * \section Search_archivefile Searching archive files
 * To search archive files and then to get archive files ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/archivefile/search \endverbatim

 * <b>Optional parameters</b>
 * |     Name     |  Type   |                                  Description                                        |           Constraint            |
 * | :----------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name         | string  | search an archive file specifying its name                                          |                                 |
 * | archive      | integer | search an archive file given the archive id                                         |                                 |
 * | archive_name | string  | search an archive file given the archive name                                       |                                 |
 * | mimetype     | string  | search an archive file specifying its mime type                                     |                                 |
 * | order_by     | enum    | order by column                                                                     | value in : 'id', 'size', 'name', 'type', 'owner', 'groups' |
 * | order_asc    | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit        | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset       | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>Make sure to pass at least one of the first four parameters. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Archive files ids list is returned
{
   {
   "message":"Query successful","archivefiles":[2]
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archivefiles not found
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

			if (isset($_GET['archive'])) {
				$archive = filter_var($_GET['archive'], FILTER_VALIDATE_INT);
				if ($archive !== false)
					$params['archive'] = $archive;
				else
					$ok = false;
			}

			if (isset($_GET['type'])) {
				if (is_string($_GET['type']))
					$params['type'] = $_GET['type'];
				else
					$ok = false;
			}

			if (isset($_GET['mimetype'])) {
				$params['mimetype'] = $_GET['mimetype'];
			}

			if (isset($_GET['archive_name'])) {
				$params['archive_name'] = $_GET['archive_name'];
			}

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'size', 'name', 'type', 'owner', 'groups')) !== false)
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
				httpResponse(400, array('message' => 'Incorrect input', 'limit' => $limit, 'GET' => &$_GET));

			$archivefile = $dbDriver->getArchiveFilesByParams($params);
			if (!$archivefile['query_executed']) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archivefile/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archivefile/search (%d) => getArchiveFilesByParams(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'archivefiles' => array(),
					'total_rows' => 0,
					'debug' => $archivefile
				));
			} elseif ($archivefile['total_rows'] === 0)
				httpResponse(404, array(
					'message' => 'Archivefiles not found',
					'archivefiles' => array(),
					'total_rows' => 0
				));

			$result = array();
			foreach ($archivefile['rows'] as $id) {
				$permission_granted = $dbDriver->checkArchiveFilePermission($id, $_SESSION['user']['id']);
				if ($permission_granted === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archivefile/serach (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archivefile/serach (%d) => checkArchiveFilePermission(%s, %s)', __LINE__, $id, $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archivefiles' => array(),
						'total_rows' => 0,
						'debug' => $archivefile
					));
				} elseif ($permission_granted === true)
					$result[] = $id;
			}

			if (count($result) == 0) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/archivefile/search (%d) => A user that cannot get archivefile informations tried to', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			httpResponse(200, array(
				'message' => 'Query succeeded',
				'archivefiles' => $result,
				'total_rows' => $archivefile['total_rows']
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
