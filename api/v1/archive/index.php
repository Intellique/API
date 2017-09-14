<?php
/**
 * \addtogroup Archive
 * \page archive Archive
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
 *   - \b 410 Archive gone
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
   "message":"Query successful","archives":[2],"total_rows":1
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
 * \li \c deleted (string) : indicator to show either no deleted archives, all deleted archives, or only deleted archives
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
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('DELETE api/v1/archive (%d) => A non-admin user (%s) tried to delete an archive', __LINE__, $_SESSION['user']['login']), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (!isset($_GET['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('DELETE api/v1/archive (%d) => Trying to delete an archive without specifying an archive id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive ID required'));
			} elseif (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('DELETE api/v1/archive (%d) => id must be an integer and not "%s"', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('DELETE api/v1/archive (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$archive = $dbDriver->getArchive($_GET['id'], DB::DB_ROW_LOCK_UPDATE);
			if ($archive === null) {
				$dbDriver->cancelTransaction();

				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/archive (%d) => getArchive(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);

				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($archive === false) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'Archive not found'));
			}

			if ($archive['deleted']) {
				$dbDriver->cancelTransaction();
				httpResponse(304, array('message' => 'Archive already deleted'));
			}

			$archive['deleted'] = true;

			$result = $dbDriver->updateArchive($archive);
			if ($result === null) {
				$dbDriver->cancelTransaction();

				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/archive (%d) => updateArchive(%s)', __LINE__, $archive['uuid']), $_SESSION['user']['id']);

				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($result === false) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/archive (%d) => Archive gone', __LINE__), $_SESSION['user']['id']);
				httpResponse(410, array('message' => 'Archive gone'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			} else
				httpResponse(200, array('message' => 'Archive deleted'));

			break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
					httpResponse(400, array('message' => 'Archive id must be an integer'));

				if (!$_SESSION['user']['isadmin']) {
					$permission_granted = $dbDriver->checkArchivePermission($_GET['id'], $_SESSION['user']['id']);
					if ($permission_granted === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archive/?id=%d (%d) => Query failure', $_GET['id'], __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archive/?id=%d (%d) => checkArchivePermission(%s, %s)', $_GET['id'], __LINE__, $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);

						httpResponse(500, array(
							'message' => 'Query failure',
							'archive' => array()
						));
					} elseif ($permission_granted === false) {
						$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/archive/?id=%d (%d) => A user that cannot get archive informations tried to', $_GET['id'], __LINE__), $_SESSION['user']['id']);
						httpResponse(403, array('message' => 'Permission denied'));
					}
				}

				$archive = $dbDriver->getArchive($_GET['id']);
				if ($archive === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archive/?id=%d (%d) => Query failure', $_GET['id'], __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archive/?id=%d (%d) => getArchive(%s)', $_GET['id'], __LINE__, $_GET['id']), $_SESSION['user']['id']);

					httpResponse(500, array(
						'message' => 'Query failure',
						'archive' => array()
					));
				} elseif ($archive === false)
					httpResponse(404, array(
						'message' => 'Archive not found',
						'archive' => array()
					));

				if ($archive['deleted'] && !$_SESSION['user']['isadmin'])
					httpResponse(404, array(
						'message' => 'Archive not found',
						'archive' => array()
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'archive' => $archive
				));
			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['deleted'])) {
					if ($_SESSION['user']['isadmin']) {
						if (array_search($_GET['deleted'], array('yes', 'no', 'only')) !== false)
							$params['deleted'] = $_GET['deleted'];
						else
							$ok = false;
					} else
						httpResponse(403, array('message' => 'Permission denied'));
				} else
					$params['deleted'] = 'no';

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

				$result = $dbDriver->getArchives($_SESSION['user'], $params);
				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archive (%d) => getArchives(%s, %s)', __LINE__, $_SESSION['user']['id'], var_export($params, true)), $_SESSION['user']['id']);

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

			$ok = true;
			$failed = false;

			$job = array(
				'name' => null,
				'interval' => null,
				'archive' => null,
				'backup' => null,
				'media' => null,
				'login' => $_SESSION['user']['id'],
				'options' => array()
			);

			// name
			if (isset($infoJob['name']) && is_string($infoJob['name']))
				$job['name'] = $infoJob['name'];
			else {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => job\'s name should be a string', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive\'s name is required'));
			}

			// files (checking file access)
			$files = &$infoJob['files'];
			if (!isset($files)) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => files should be defined', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Incorrect input'));
			} elseif (!is_array($files)) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => files should be an array of string', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Incorrect input'));
			} elseif (count($files) == 0) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => files should contain at least one file', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Incorrect input'));
			} else {
				for ($i = 0; $i < count($files); $i++) {
					if (!is_string($files[$i])) {
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => file should be a string', __LINE__), $_SESSION['user']['id']);
						$ok = false;
					} elseif (!posix_access($files[$i], POSIX_F_OK)) {
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => cannot access to file(%s)', __LINE__, $files[$i]), $_SESSION['user']['id']);
						$ok = false;
					}
				}

				if (!$ok)
					httpResponse(400, array('message' => 'files should be an array of string'));
			}

			// pool id
			if (!isset($infoJob['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive (%d) => Pool id is required', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id is required'));
			}

			if (!is_integer($infoJob['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => id must be an integer and not "%s"', __LINE__, $infoJob['pool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id must be an integer'));
			}

			// metadata [optional]
			if (isset($infoJob['metadata'])) {
				if (is_array($infoJob['metadata']))
					$job['metadata'] = $infoJob['metadata'];
				else
					httpResponse(400, array('message' => 'Metadata should be an hashtable'));
			}

			// nextstart [optional]
			if (isset($infoJob['nextstart'])) {
				$parsedDataTime = dateTimeParse($infoJob['nextstart']);
				if ($parsedDataTime !== null)
					$job['nextstart'] = &$parsedDataTime;
				else
					httpResponse(400, array('message' => 'nextstart should be a string representing a date (supported format: ISO8601, RFC2822, RFC822)'));
			} else
				$job['nextstart'] = new DateTime();

			// options [optional]
			if (isset($infoJob['options']['quick_mode'])) {
				if (is_bool($infoJob['options']['quick_mode']))
					$job['options']['quick_mode'] = $infoJob['options']['quick_mode'];
				else
					httpResponse(400, array('message' => 'option \'quick_mode\' should be a boolean value'));
			} elseif (isset($infoJob['options']['thorough_mode'])) {
				if (is_bool($infoJob['options']['thorough_mode']))
					$job['options']['quick_mode'] = !$infoJob['options']['thorough_mode'];
				else
					httpResponse(400, array('message' => 'option \'quick_mode\' should be a boolean value'));
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));


			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/archive (%d) => Failed to finish transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			// pool id
			$checkPoolPermission = $dbDriver->checkPoolPermission($infoJob['pool'], $_SESSION['user']['id']);
			if ($checkPoolPermission === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => checkPoolPermission(%s, %s)', __LINE__, $infoJob['pool'], $_SESSION['user']['id']), $_SESSION['user']['id']);
				$failed = true;
			} else
				$job['pool'] = intval($infoJob['pool']);

			if (!$_SESSION['user']['canarchive'] || !$checkPoolPermission) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive (%d) => User %s cannot create an archive', __LINE__, $_SESSION['user']['login']), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$checkPool = $dbDriver->getPool($infoJob['pool'], DB::DB_ROW_LOCK_SHARE);
			if ($checkPool === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive (%d) => getPool(%d)', __LINE__, $checkPool['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($checkPool === false) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => getPool(%d)', __LINE__, $checkPool['id']), $_SESSION['user']['id']);
				httpResponse(404, array('message' => 'Pool not found'));
			} elseif ($checkPool['deleted']) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive (%d) => User (%s) try to archive into a deleted pool \'%s(%d)\'', __LINE__, $_SESSION['user']['login'], $checkPool['name'], $checkPool['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool deleted'));
			}

			// type
			$jobType = $dbDriver->getJobTypeId("create-archive");
			if ($jobType === null || $jobType === false) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => getJobTypeId(%s)', __LINE__, "create-archive"), $_SESSION['user']['id']);
				$failed = true;
			} else
				$job['type'] = $jobType;

			// host
			$host = $dbDriver->getHost(posix_uname()['nodename']);
			if ($host === null || $host === false) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => getHost(%s)', __LINE__, posix_uname()['nodename']), $_SESSION['user']['id']);
				$failed = true;
			} else
				$job['host'] = $host;

			// gestion des erreurs
			if ($failed) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive (%d) => Query failure', __LINE__, $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();

				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive (%d) => Query failure', __LINE__, $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => createJob(%s)', __LINE__, $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			foreach ($files as $file) {
				$selectedfileId = $dbDriver->getSelectedFile($file);

				if ($selectedfileId === null || !$dbDriver->linkJobToSelectedfile($jobId, $selectedfileId)) {
					$dbDriver->cancelTransaction();

					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive (%d) => getSelectedFile(%s)', __LINE__, $file), $_SESSION['user']['id']);
					$failed = true;
					break;
				}
			}

			if ($failed) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive (%d) => Query failure', __LINE__, $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

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

			if (!is_integer($archive['id']))
				httpResponse(400, array('message' => 'Archive id must be an integer'));

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('PUT api/v1/archive (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			// archive id
			if (!$_SESSION['user']['isadmin']) {
				$checkArchivePermission = $dbDriver->checkArchivePermission($archive['id'], $_SESSION['user']['id']);
				if ($checkArchivePermission === null) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => checkArchivePermission(%s)', __LINE__, $archive['id']), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}

				if (!$checkArchivePermission) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archive (%d) => A non-admin user tried to update an archive', __LINE__), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}
			}

			// check archive
			$check_archive = $dbDriver->getArchive($archive['id'], DB::DB_ROW_LOCK_UPDATE);

			if ($check_archive === null) {
				$dbDriver->cancelTransaction();

				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => getArchive(%s)', __LINE__, $archive['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_archive === false) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'Archive not found'));
			}

			// name [optional]
			if (isset($archive['name']) && $archive['name'] != $check_archive['name']) {
				if (!$_SESSION['user']['isadmin'] && $check_archive['owner'] != $_SESSION['user']['id']) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archive (%d) => A non-admin user tried to update an archive', __LINE__), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				} elseif (!is_string($archive['name'])) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archive (%d) => A non-admin user tried to update an archive', __LINE__), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Incorrect input'));
				} else
					$archive['name'] = $check_archive['name'];
			}

			// owner [optional]
			if (isset($archive['owner'])) {
				if ($archive['owner'] != $check_archive['owner'] && !$_SESSION['user']['isadmin']) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archive (%d) => A non-admin user tried to update an archive (%d)', __LINE__, $archive['id']), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				} elseif (!is_integer($archive['owner'])) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archive (%d) => archive\'s owner shoul be integer', __LINE__), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Incorrect input'));
				}

				$check_user = $dbDriver->getUserById($archive['owner'], DB::DB_ROW_LOCK_SHARE);
				if ($check_user === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => getUserById(%s)', __LINE__, $archive['owner']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($check_user === false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => User not found', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => getUserById(%s)', __LINE__, $archive['owner']), $_SESSION['user']['id']);
					httpResponse(404, array('message' => sprintf('User \'%d\' not found', $archive['owner'])));
				} else
					$archive['owner'] = $check_archive['owner'];
			}

			// metadata [optional]
			if (isset($archive['metadata'])) {
				if (!is_array($archive['metadata'])) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => invalid metadata', __LINE__), $_SESSION['user']['id']);
					httpResponse(404, array('message' => 'Invalid metadata'));
				} else
					$archive['metadata'] = &$check_archive['metadata'];
			}

			// canappend [optional]
			if (isset($archive['canappend'])) {
				if (is_bool($archive['canappend']))
					$archive['canappend'] = $check_archive['canappend'];
				else {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => canappend is not a boolean', __LINE__), $_SESSION['user']['id']);
					httpResponse(404, array('message' => 'canappend should be an boolean value'));
				}
			}

			// deleted
			if (isset($archive['deleted']) && $archive['deleted'] !== $check_archive['deleted']) {
				if (!$_SESSION['user']['isadmin']) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => non admin user is not allowed to change \'deleted\' attribute of archive', __LINE__), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				} elseif (!is_bool($archive['deleted'])) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archive (%d) => \'deleted\' attribute should be a boolean', __LINE__), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Incorrect input'));
				}

				if ($archive['deleted']) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archive (%d) => cannot mark archive as deleted because you should use DELETE instead of PUT', __LINE__), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'cannot mark archive as deleted because you should use DELETE instead of PUT'));
				} else foreach ($check_archive['volumes'] as &$volume)
					if ($volume['purged'] !== null) {
						$dbDriver->cancelTransaction();
						$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/archive (%d) => cannot mark archive as undeleted because, at least, one of its volume has been purged', __LINE__), $_SESSION['user']['id']);
						httpResponse(400, array('message' => 'cannot mark archive as undeleted because, at least, one of its volume has been purged'));
					}
			}

			$resultArchive = $dbDriver->updateArchive($archive);
			if (!$resultArchive) {
				$dbDriver->cancelTransaction();

				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => updateArchive(%s)', __LINE__, var_export($archive, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			// update, create and delete metadata
			foreach ($archive['metadata'] as $key => $value) {
				if (array_key_exists($key, $check_archive['metadata'])) {
					$resultMetadata = $dbDriver->updateMetadata($archive['id'], $key, $value, 'archive', $_SESSION['user']['id']);
					if (!$resultMetadata) {
						$dbDriver->cancelTransaction();
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => updateMetadata(%s, %s, %s, "archive", %s)', __LINE__, $archive['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query failure'));
					}
				} else {
					$resultMetadata = $dbDriver->createMetadata($archive['id'], $key, $value, 'archive', $_SESSION['user']['id']);
					if (!$resultMetadata) {
						$dbDriver->cancelTransaction();
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => createMetadata(%s, %s, %s, "archive", %s)', __LINE__, $archive['id'], $key, $value, $_SESSION['user']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query failure'));
					}
				}
			}

			if (isset($check_archive['metadata']) && $check_archive['metadata'] !== false) {
				foreach ($check_archive['metadata'] as $key => $value) {
					if (!array_key_exists($key, $archive['metadata'])) {
						$resultMetadata = $dbDriver->deleteMetadata($archive['id'], $key, 'archive', $_SESSION['user']['id']);
						if (!$resultMetadata) {
							$dbDriver->cancelTransaction();
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => deleteMetadata(%s, %s, "archive", %s)', __LINE__, $archive['id'], $key, $_SESSION['user']['id']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query failure'));
						}
					}
				}
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			}

			httpResponse(200, array('message' => 'Archive updated successfully'));

		case 'OPTIONS':
			httpOptionsMethod(HTTP_ALL_METHODS);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
