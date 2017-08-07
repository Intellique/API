<?php
/**
 * \addtogroup Archive
 * \page archive
 * \subpage add Add Files to Archive
 * \section Create_add_task Add Files to Archive task creation
 * To create an add task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/archive/add/ \endverbatim
 * \param job : hash table
 * \li \c archive id (integer) : archive id
 * \li \c name [optional] (string) : add task name, <em>default value : "add_" + archive name</em>
 * \li \c nextstart [optional] (string) : add task nextstart date, <em>default value : now</em>
 * \li \c options [optional] (hash table) : check archive options (quick_mode or thorough_mode), <em>default value : thorough_mode</em>
 * \param files : archive files array
 * \li \c files (string array) : files to be added
 * \return HTTP status codes :
 *   - \b 201 Job created successfully
 *     \verbatim New job id is returned
 {
    "message":"Job created successfully",
    "job_id":6
 }
      \endverbatim
 *   - \b 400 Bad request - Either ; archive id is required or archive id must be an integer or archive not found or incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 409 Request conflict
 *   - \b 500 Query failure
 */
	require_once("../../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'POST':
			checkConnected();

			$ok = true;
			$failed = false;

			$job = array(
				'name' => null,
				'interval' => null,
				'backup' => null,
				'media' => null,
				'pool' => null,
				'login' => $_SESSION['user']['id'],
				'metadata' => array(),
				'options' => array()
			);

			$infoJob = httpParseInput();

			// archive id
			if (!isset($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/add (%d) => Archive id is required', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id is required'));
			}

			if (!is_integer($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/add (%d) => Archive id must be an integer and not %s', __LINE__, $infoJob['archive']) , $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id must be an integer'));
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

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/archive/add (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			// archive id
			$checkArchivePermission = $dbDriver->checkArchivePermission($infoJob['archive'], $_SESSION['user']['id']);
			if ($checkArchivePermission === null) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			} elseif (!$_SESSION['user']['canarchive'] || !$checkArchivePermission) {
				$dbDriver->cancelTransaction();

				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/add (%d) => A user that cannot archive tried to archive', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			// check archive
			$check_archive = $dbDriver->getArchive($infoJob['archive'], DB::DB_ROW_LOCK_SHARE);
			if (!$check_archive)
				$dbDriver->cancelTransaction();
			if ($check_archive === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/add (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/add (%d) => getArchive(%s)', __LINE__, $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_archive === false)
				httpResponse(400, array('message' => 'Archive not found'));

			$job['archive'] = $infoJob['archive'];

			$archiveSynchronized = $dbDriver->isArchiveSynchronized($infoJob['archive']);
			if (!$archiveSynchronized['query_executed']) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/add (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/add (%d) => isArchiveSynchronized(%s)', __LINE__, $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$archiveSynchronized['synchronized']) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/add (%d) => cannot add files to archive not synchronized with archive mirror', __LINE__), $_SESSION['user']['id']);
				httpResponse(409, array('message' => 'Request conflict'));
			}

			// name [optional]
			if (isset($infoJob['name'])) {
				$ok = is_string($infoJob['name']);
				if ($ok)
					$job['name'] = $infoJob['name'];
			} else
				$job['name'] = "add_" . $check_archive['name'];

			// type
			if ($ok) {
				$jobType = $dbDriver->getJobTypeId("create-archive");
				if ($jobType === null || $jobType === false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/add (%d) => getJobTypeId(%s)', __LINE__), "create-archive"), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['type'] = $jobType;
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

			// host
			if ($ok) {
				$host = $dbDriver->getHost(posix_uname()['nodename']);
				if ($host === null || $host === false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/add (%d) => getHost(%s)', __LINE__, posix_uname()['nodename']), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['host'] = $host;
			}

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/add (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Incorrect input'));
			}

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/add (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/add (%d) => createJob(%s)', __LINE__, $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			foreach ($files as $file) {
				$selectedfileId = $dbDriver->getSelectedFile($file);

				if ($selectedfileId === null || !$dbDriver->linkJobToSelectedfile($jobId, $selectedfileId)) {
					$dbDriver->cancelTransaction();
					httpResponse(500, array('message' => 'Query failure'));
				}
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->finishTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			httpAddLocation('/job/?id=' . $jobId);
			$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/add => Job %s created', $jobId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Job created successfully',
				'job_id' => $jobId
			));

		case 'OPTIONS':
			httpOptionsMethod(HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
