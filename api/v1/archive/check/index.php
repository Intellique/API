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

			// archive id
			if (!isset($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/check => Trying to check an archive without specifying archive id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id is required'));
			}

			if (!is_int($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/check => Archive id must be an integer and not %s', $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id must be an integer'));
			}

			if (!$dbDriver->startTransaction())
				httpResponse(500, array('message' => 'Query failure'));

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
			if (!$_SESSION['user']) {
				$checkArchivePermission = $dbDriver->checkArchivePermission($infoJob['archive'], $_SESSION['user']['id']);
				if ($checkArchivePermission === null) {
					$dbDriver->cancelTransaction();
					httpResponse(500, array('message' => 'Query failure'));
				} elseif (!$checkArchivePermission) {
					$dbDriver->cancelTransaction();

					$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/check => A user that cannot check tried to', $_SESSION['user']['id']);
					httpResponse(403, array('message' => 'Permission denied'));
				}
			}

			// check archive
			$check_archive = $dbDriver->getArchive($infoJob['archive']);

			if (!$check_archive)
				$dbDriver->cancelTransaction();
			if ($check_archive === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/check => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchive(%s)', $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_archive === false)
				httpResponse(400, array('message' => 'Archive not found'));

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
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobTypeId(%s)', "check-archive"), $_SESSION['user']['id']);
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
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getHost(%s)', posix_uname()['nodename']), $_SESSION['user']['id']);
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
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/check => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Incorrect input'));
			}

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/check => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createJob(%s)', $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->finishTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			}

			httpAddLocation('/job/?id=' . $jobId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/archive/check => Job %s created', $jobId), $_SESSION['user']['id']);
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
