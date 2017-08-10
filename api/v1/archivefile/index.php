<?php
/**
 * \addtogroup archiveFile
 * \page archivefile
 * \page archivefile ArchiveFile
 * \section Archive_File_ID Archive File information
 * To get archive file by its id,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/archivefile/ \endverbatim
 * \param id : archive file id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 403 Permission denied
 *   - \b 404 Archive file not found
 *   - \b 500 Query failure
 *
 * \section Archive_Files Archive files ids (multiple list)
 * To get archive files ids list,
 * use \b GET method : <i>Specify the archive id that you want to list</i>
 * \verbatim path : /storiqone-backend/api/v1/archivefile/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'name', 'size' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get the id list of archive files, specify the id of the archive you want to list</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 *
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
					httpResponse(400, array('message' => 'Archivefile id must be an integer'));

				$permission_granted = $dbDriver->checkArchiveFilePermission($_GET['id'], $_SESSION['user']['id']);
				if ($permission_granted === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archivefile (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archivefile (%d) => checkArchiveFilePermission(%s, %s)', __LINE__, $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archivefile' => array()
					));
				} elseif ($permission_granted === false) {
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/archivefile (%d) => A user that cannot get archivefile informations tried to', __LINE__), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}

				$archivefile = $dbDriver->getArchiveFile($_GET['id']);
				if ($archivefile === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archivefile (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchiveFile(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archivefile' => array()
					));
				} elseif ($archivefile === false)
					httpResponse(404, array(
						'message' => 'Archivefile not found',
						'archivefile' => array()
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'archivefile' => $archivefile
				));
			} elseif (isset($_GET['archive'])) {
				$params = array();
				$ok = true;

				if (filter_var($_GET['archive'], FILTER_VALIDATE_INT) === false)
					httpResponse(400, array('message' => 'Archive id must be an integer'));

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'name', 'size')) !== false)
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

				$checkArchivePermission = $dbDriver->checkArchivePermission($_GET['archive'], $_SESSION['user']['id']);
				if (!$checkArchivePermission) {
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/archivefile (%d) => A non-admin user tried to get informations from archive files', __LINE__), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}

				$result = $dbDriver->getFilesFromArchive($_GET['archive'], $params);

				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archivefile (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archivefile (%d) => getFilesFromArchive(%s, %s)', __LINE__, $_GET['id'], $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archivefiles' => array(),
						'total_rows' => 0
					));
				} else {
					$iter = $result['iterator'];
					$filesFound = array();
					$total_rows = $result['total_rows'];

					while ($iter->hasNext()) {
						$row = $iter->next();
						$fileId = $row->getValue('id');
						array_push($filesFound, $fileId);
					}

					httpResponse(200, array(
						'message' => 'Query successful',
						'archivefiles' => $filesFound,
						'total_rows' => $total_rows
					));
				}
			} else
				httpResponse(400, array('message' => '"id" or "archive" are required'));
			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}

?>
