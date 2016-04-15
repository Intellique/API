<?php
/**
 * \addtogroup archive
 * \section Delete_Archive Archive deletion
 * To mark archive as deleted,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param id : archive id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Archive information are returned
 {
   "message":"Archive deleted"
 }
       \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Archive not found
 *   - \b 500 Query failure
 *
 * \section Archive_ID Archive information
 * To get archive by its id,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param id : archive id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Archive information is returned

{
   "message":"Query successful","archives":[2],"total_rows":1
}

       \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 *
 * \section Archives Archives ids (multiple list)
 * To get archives ids list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'uuid', 'name' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple archives ids list do not pass an id or ids as parameter</b>
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
 *   - \b 500 Query failure
 *
 * \section Create_archival_task Archival task creation
 * To create an archival task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param job : hash table
 * \li \c pool id (integer) : pool id
 * \li \c files (string array) : files to be archived
 * \li \c name (string) : archive name
 * \li \c metadata [optional] (object) : archive metadata, <em>default value : empty object</em>
 * \li \c nextstart [optional] (string) : archival task nextstart date, <em>default value : now</em>
 * \li \c options [optional] (hash table) : check archive options (quick_mode or thorough_mode), <em>default value : thorough_mode</em>
 * \return HTTP status codes :
 *   - \b 201 Job created successfully
 *     \verbatim New job id is returned
{
   'message': 'Job created successfully', 'job_id': 12
}\endverbatim
 *   - \b 400 Bad request - Either ; pool id is required or pool id must be an integer or incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 *
 * \note \ref Date "Date time formats supported"
 *
 * \section Update_archive Archive update
 * To update an archive,
 * use \b PUT method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param archive : JSON encoded object
 * \li \c id (integer) : archive id
 * \li \c name [optional] (string) : archive name
 * \li \c owner [optional] (integer) : archive owner id
 * \li \c canappend [optional] (boolean) : archive extend rights
 * \return HTTP status codes :
 *   - \b 200 Archive updated successfully
   * \verbatim 'message': 'Archive updated successfully' \endverbatim
 *   - \b 400 Bad request - Either ; archive id is required or archive id must be an integer or archive not found or incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to delete an archive', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/archive => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Archive id must be an integer'));
				}

				$archive = $dbDriver->getArchive($_GET['id']);

				if ($archive === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/archive => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchive(%s)', $_GET['id']), $_SESSION['user']['id']);

					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($archive === false)
					httpResponse(404, array('message' => 'Archive not found'));

				$archive['deleted'] = true;

				$result = $dbDriver->updateArchive($archive);
				if ($result === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/archive => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updateArchive(%s)', $archive['uuid']), $_SESSION['user']['id']);

					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($result === false)
					httpResponse(404, array('message' => 'Archive not found'));
				else
					httpResponse(200, array('message' => 'Archive deleted'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'Trying to delete an archive without specifing an archive id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive ID required'));
			}

			break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id']))
					httpResponse(400, array('message' => 'Archive id must be an integer'));

				$archive = $dbDriver->getArchive($_GET['id']);
				if ($archive === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchive(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archive' => array()
					));
				} elseif ($archive === false)
					httpResponse(404, array(
						'message' => 'Archive not found',
						'archive' => array()
					));

				$permission_granted = $dbDriver->checkArchivePermission($_GET['id'], $_SESSION['user']['id']);
				if ($permission_granted === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkArchivePermission(%s, %s)', $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archive' => array()
					));
				} elseif ($permission_granted === false) {
					$dbDriver->writeLog(DB::DB_LOG_WARNING, 'GET api/v1/archive => A user that cannot get archive informations tried to', $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}

				httpResponse(200, array(
						'message' => 'Query succeeded',
						'archive' => $archive
				));
			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'uuid', 'name')))
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
				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/archive => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchives(%s, %s)', $_SESSION['user']['id'], $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'archives' => array(),
						'total_rows' => 0
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'archives' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}

			break;

		case 'POST':
			checkConnected();

			$infoJob = httpParseInput();

			// pool id
			if (!isset($infoJob['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive => Pool id is required', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id is required'));
			}

			if (!is_int($infoJob['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive => id must be an integer and not "%s"', $infoJob['pool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id must be an integer'));
			}

			$dbDriver->startTransaction();

			$ok = true;
			$failed = false;

			$job = array(
				'interval' => null,
				'archive' => null,
				'backup' => null,
				'media' => null,
				'options' => array()
			);

			// pool id
			$checkPoolPermission = $dbDriver->checkPoolPermission($infoJob['pool'], $_SESSION['user']['id']);
			if ($checkPoolPermission === null) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkPoolPermission(%s, %s)', $infoJob['pool'], $_SESSION['user']['id']), $_SESSION['user']['id']);
				$failed = true;
			} else
				$job['pool'] = intval($infoJob['pool']);

			if (!$_SESSION['user']['canarchive'] || !$checkPoolPermission) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('User %s cannot create an archive', $_SESSION['user']['id']), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			// files (checking file access)
			$files = $infoJob['files'];
			$ok = isset($files) && is_array($files) && count($files) > 0;
			if ($ok)
				for ($i = 0, $n = count($files); $ok && $i < $n; $i++)
					$ok = is_string($files[$i]) && posix_access($files[$i], POSIX_F_OK);

			// name
			if ($ok)
				$ok = isset($infoJob['name']) && is_string($infoJob['name']);
			if ($ok)
				$job['name'] = $infoJob['name'];

			// type
			if ($ok) {
				$jobType = $dbDriver->getJobTypeId("create-archive");
				if ($jobType === null || $jobType === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobTypeId(%s)', "create-archive"), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['type'] = $jobType;
			}

			// host
			if ($ok) {
				$host = $dbDriver->getHost(posix_uname()['nodename']);
				if ($host === null || $host === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getHost(%s)', posix_uname()['nodename']), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['host'] = $host;
			}

			// login
			if ($ok)
				$job['login'] = $_SESSION['user']['id'];

			// metadata [optional]
			if ($ok && isset($infoJob['metadata'])) {
				$metadata = $infoJob['metadata'];
				$ok = is_array($metadata);
				if ($ok)
					$job['metadata'] = $metadata;
			}

			// nextstart [optional]
			if ($ok && isset($infoJob['nextstart'])) {
				$job['nextstart'] = dateTimeParse($infoJob['nextstart']);
				if ($job['nextstart'] === null)
					$ok = false;
			} elseif ($ok)
				$job['nextstart'] = new DateTime();

			// options [optional]
			if ($ok && isset($infoJob['options']['quick_mode'])) {
				if (is_bool($infoJob['options']['quick_mode']))
					$job['options']['quick_mode'] = $infoJob['options']['quick_mode'];
				else
					$ok = false;
			} elseif ($ok && isset($infoJob['options']['thorough_mode'])) {
				if (is_bool($infoJob['options']['thorough_mode']))
					$job['options']['quick_mode'] = !$infoJob['options']['thorough_mode'];
				else
					$ok = false;
			}

			// gestion des erreurs
			if ($failed || !$ok)
				$dbDriver->cancelTransaction();

			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createJob(%s)', $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			foreach ($files as $file) {
				$selectedfileId = $dbDriver->getSelectedFile($file);

				if ($selectedfileId === null || !$dbDriver->linkJobToSelectedfile($jobId, $selectedfileId)) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getSelectedFile(%s)', $file), $_SESSION['user']['id']);
					$failed = true;
					break;
				}
			}

			if ($failed) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			$dbDriver->finishTransaction();

			httpAddLocation('/job/?id=' . $jobId);
			httpResponse(201, array(
				'message' => 'Job created successfully',
				'job_id' => $jobId
			));

			break;

		case 'PUT':
			checkConnected();

			$archive = httpParseInput();

			// archive id
			if (!isset($archive['id']))
				httpResponse(400, array('message' => 'Archive id is required'));

			if (!is_int($archive['id']))
				httpResponse(400, array('message' => 'Archive id must be an integer'));

			$dbDriver->startTransaction();

			$ok = true;
			$failed = false;

			// check archive
			$check_archive = $dbDriver->getArchive($archive['id']);

			if (!$check_archive)
				$dbDriver->cancelTransaction();

			if ($check_archive === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchive(%s)', $archive['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_archive === false)
				httpResponse(400, array('message' => 'Archive not found'));

			// archive id
			$checkArchivePermission = $dbDriver->checkArchivePermission($archive['id'], $_SESSION['user']['id']);
			if ($checkArchivePermission === null) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkArchivePermission(%s)', $archive['id']), $_SESSION['user']['id']);
				$failed = true;
			}
			if (!$_SESSION['user']['isadmin'] || !$checkArchivePermission) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to update an archive', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			// name [optional]
			if ($ok && isset($archive['name'])) {
				$ok = is_string($archive['name']);
				if ($ok && $archive['name'] != $check_archive['name'] && $check_archive['owner'] != $_SESSION['user']['id']) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to update an archive', $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}
			} elseif ($ok)
				$archive['name'] = $check_archive['name'];

			// owner [optional]
			if ($ok && isset($archive['owner'])) {
				$ok = is_int($archive['owner']);
				if ($ok && $archive['owner'] != $check_archive['owner'] && !$_SESSION['user']['isadmin']) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to update an archive', $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}
			} elseif ($ok)
				$archive['owner'] = $check_archive['owner'];

			// metadata [optional]
			if ($ok && isset($archive['metadata']))
				$ok = is_array($archive['metadata']);
			elseif ($ok)
				$archive['metadata'] = &$check_archive['metadata'];

			// canappend [optional]
			if ($ok && isset($archive['canappend']))
				$ok = is_bool($archive['canappend']);
			elseif ($ok)
				$archive['canappend'] = $check_archive['canappend'];

			// deleted
			if ($ok && $archive['deleted'] != $check_archive['deleted'] && isset($archive['deleted']))
				$ok = false;

			// gestion des erreurs
			if ($failed || !$ok)
				$dbDriver->cancelTransaction();

			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$resultArchive = $dbDriver->updateArchive($archive);

			if (!$resultArchive) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updateArchive(%s)', $archive['uuid']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			// update, create and delete metadata
			foreach ($archive['metadata'] as $key => $value) {
				if (array_key_exists($key, $check_archive['metadata'])) {
					$resultMetadata = $dbDriver->updateMetadata($archive['id'], $key, $value, 'archive', $_SESSION['user']['id']);
					if (!$resultMetadata) {
						$dbDriver->cancelTransaction();
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updateMetadata(%s, %s, %s, "archive", %s)', $archive['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query failure'));
					}
				} else {
					$resultMetadata = $dbDriver->createMetadata($archive['id'], $key, $value, 'archive', $_SESSION['user']['id']);
					if (!$resultMetadata) {
						$dbDriver->cancelTransaction();
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createMetadata(%s, %s, %s, "archive", %s)', $archive['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query failure'));
					}
				}
			}

			foreach ($check_archive['metadata'] as $key => $value) {
				if (!array_key_exists($key, $archive['metadata'])) {
					$resultMetadata = $dbDriver->deleteMetadata($archive['id'], $key, 'archive', $_SESSION['user']['id']);
					if (!$resultMetadata) {
						$dbDriver->cancelTransaction();
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/archive => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('deleteMetadata(%s, %s, "archive", %s)', $archive['id'], $key, $_SESSION['user']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query failure'));
					}
				}
			}

			$dbDriver->finishTransaction();

			httpResponse(200, array('message' => 'Archive updated successfully'));

		case 'OPTIONS':
			httpOptionsMethod(HTTP_ALL_METHODS);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>