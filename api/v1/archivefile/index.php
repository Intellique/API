<?php
/**
 * \addtogroup archiveFile
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
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id']))
					httpResponse(400, array('message' => 'Archivefile id must be an integer'));

				$archivefile = $dbDriver->getArchiveFile($_GET['id']);
				if ($archivefile === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archivefile => Query failure', $_SESSION['user']['id']);
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

				$permission_granted = $dbDriver->checkArchiveFilePermission($_GET['id'], $_SESSION['user']['id']);
				if ($permission_granted === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archivefile => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkArchiveFilePermission(%s, %s)', $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archivefile' => array()
					));
				} elseif ($permission_granted === false) {
					$dbDriver->writeLog(DB::DB_LOG_WARNING, 'GET api/v1/archivefile => A user that cannot get archivefile informations tried to', $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}

				httpResponse(200, array(
						'message' => 'Query succeeded',
						'archivefile' => $archivefile
				));
			} elseif (isset($_GET['archive'])) {
				$params = array();
				$ok = true;

				if (!is_numeric($_GET['archive']))
					httpResponse(400, array('message' => 'Archive id must be an integer'));

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'name', 'size')))
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

				$checkArchivePermission = $dbDriver->checkArchivePermission($_GET['archive'], $_SESSION['user']['id']);
				if (!$checkArchivePermission) {
					$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to get informations from archive files', $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}

				$result = $dbDriver->getFilesFromArchive($_GET['archive'], $params);

				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archivefile => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getFilesFromArchive(%s, %s)', $_GET['id'], $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archivesfiles' => array(),
						'total_rows' => 0
					));
				} else {

					$iter = $result['iterator'];
					$filesFound = array();
					$total_rows = 0;

					while ($iter->hasNext()) {
						$row = $iter->next();
						$fileId = $row->getValue('id');
						array_push($filesFound, $fileId);
						$total_rows ++;
					}

					httpResponse(200, array(
						'message' => 'Query successful',
						'archivesfiles_ids' => $filesFound,
						'total_rows' => $total_rows
					));
				}
			}
			else 
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