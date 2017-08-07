<?php
/**
 * \addtogroup Archive
 * \page archive
 * \subpage check
 * \section Create_check_archive_task Check archive task creation
 * To create a check archive task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/archive/check/ \endverbatim
 * \param job : hash table
 * \li \c archive id (integer) : archive id
 * \li \c name [optional] (string) : check archive task name, <em>default value : "check_" + archive name</em>
 * \li \c nextstart [optional] (string) : check task nextstart date, <em>default value : now</em>
 * \li \c options [optional] (hash table) : check archive options (quick_mode or thorough_mode), <em>default value : thorough_mode</em>
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

			$infoJob = httpParseInput();

			$ok = true;
			$failed = false;

			$job = array(
				'interval' => null,
				'backup' => null,
				'media' => null,
				'pool' => null,
				'login' => $_SESSION['user']['id'],
				'metadata' => array(),
				'options' => array()
			);

			// archive id
			if (!isset($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/check (%d) => Trying to check an archive without specifying archive id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id is required'));
			}

			if (!is_integer($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/check (%d) => Archive id must be an integer and not %s', __LINE__, $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id must be an integer'));
			}


			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/archive/check (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			// archive id
			if (!$_SESSION['user']['isadmin']) {
				$checkArchivePermission = $dbDriver->checkArchivePermission($infoJob['archive'], $_SESSION['user']['id']);
				if ($checkArchivePermission === null) {
					$dbDriver->cancelTransaction();
					httpResponse(500, array('message' => 'Query failure'));
				} elseif (!$checkArchivePermission) {
					$dbDriver->cancelTransaction();

					$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/check (%d) => A user that cannot check tried to', __LINE__), $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}
			}

			// check archive
			$check_archive = $dbDriver->getArchive($infoJob['archive'], DB::DB_ROW_LOCK_SHARE);
			if (!$check_archive)
				$dbDriver->cancelTransaction();
			if ($check_archive === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/check (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/check (%d) => getArchive(%s)', __LINE__, $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_archive === false)
				httpResponse(400, array('message' => 'Archive not found'));

			if ($check_archive['deleted']) {
				$dbDriver->cancelTransaction();

				if ($_SESSION['user']['isadmin'])
					httpResponse(400, array('message' => 'Archive is deleted'));
				else
					httpResponse(404, array('message' => 'Archive not found'));
			}

			$job['archive'] = $infoJob['archive'];

			// name [optional]
			if (isset($infoJob['name'])) {
				$ok = is_string($infoJob['name']);
				if ($ok)
					$job['name'] = $infoJob['name'];
			} else
				$job['name'] = "check_" . $check_archive['name'];

			// type
			if ($ok) {
				$jobType = $dbDriver->getJobTypeId("check-archive");
				if ($jobType === null || $jobType === false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/check (%d) => getJobTypeId(%s)', __LINE__, "check-archive"), $_SESSION['user']['id']);
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

			// host
			if ($ok) {
				$host = $dbDriver->getHost(posix_uname()['nodename']);
				if ($host === null || $host === false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/check (%d) => getHost(%s)', __LINE__, posix_uname()['nodename']), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['host'] = $host;
			}

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
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/check (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Incorrect input'));
			}

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/check => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/check => Query createJob(%s)', $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->finishTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			httpAddLocation('/job/?id=' . $jobId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/archive/check (%d) => Job %s created', __LINE__, $jobId), $_SESSION['user']['id']);
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
