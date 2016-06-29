<?php
/**
 * \addtogroup Archive
 * \section Create_copy_archive_task Copy archive task creation
 * To create a copy archive task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/archive/copy/ \endverbatim
 * \param job : hash table
 * \li \c archive id (integer) : archive id
 * \li \c pool id (integer) : pool id
 * \li \c name [optional] (string) : copy archive task name, <em>default value : "copy_of_" + archive name + "_in_pool_" + pool name</em>
 * \li \c nextstart [optional] (string) : copy task nextstart date, <em>default value : now</em>
 * \li \c options [optional] (hash table) : copy archive options (quick_mode or thorough_mode), <em>default value : thorough_mode</em>
 * \return HTTP status codes :
 *   - \b 201 Job created successfully
 *     \verbatim New job id is returned \endverbatim
 *   - \b 400 Bad request - Either ; archive id is required or archive id must be an integer or archive not found or pool id is required or pool id must be an integer or pool id not found or incorrect input
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
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/copy => Trying to copy an archive without specifying archive id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id is required'));
			}

			if (!is_int($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/copy => Archive id must be an integer and not %s', $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id must be an integer'));
			}

			// pool id
			if (!isset($infoJob['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/copy => Trying to copy an archive without specifying pool id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id is required'));
			}

			if (!is_int($infoJob['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/copy => Pool id must be an integer and not %s', $infoJob['pool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id must be an integer'));
			}

			$dbDriver->startTransaction();

			$ok = true;
			$failed = false;

			$job = array(
				'interval' => null,
				'backup' => null,
				'media' => null,
				'login' => $_SESSION['user']['id'],
				'metadata' => array(),
				'options' => array()
			);

			// check archive
			$check_archive = $dbDriver->getArchive($infoJob['archive']);

			if (!$check_archive)
				$dbDriver->cancelTransaction();

			if ($check_archive === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/copy => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchive(%s)', $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_archive === false)
				httpResponse(400, array('message' => 'Archive not found'));

			// archive id
			$checkArchivePermission = $dbDriver->checkArchivePermission($infoJob['archive'], $_SESSION['user']['id']);
			if ($checkArchivePermission === null)
				$failed = true;
			else
				$job['archive'] = intval($infoJob['archive']);

			if (!$checkArchivePermission) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/copy => A user that cannot check tried to', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			// check pool
			$check_pool = $dbDriver->getPool($infoJob['pool']);

			if (!$check_pool)
				$dbDriver->cancelTransaction();

			if ($check_pool === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/copy => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPool(%s)', $infoJob['pool']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_pool === false)
				httpResponse(400, array('message' => 'Pool not found'));

			// pool id
			$checkPoolPermission = $dbDriver->checkPoolPermission($infoJob['pool'], $_SESSION['user']['id']);
			if ($checkPoolPermission === null)
				$failed = true;
			else
				$job['pool'] = intval($infoJob['pool']);

			if (!$_SESSION['user']['canarchive'] || !$checkPoolPermission) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/copy => A user that cannot archive tried to', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			// check if archive is in pool
			$check_media = $dbDriver->getMedia($check_archive['volumes'][0]['media']);

			if (!$check_media)
				$dbDriver->cancelTransaction();

			if ($check_media === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/copy => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMedia(%s)', $check_archive['volumes'][0]['media']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_media === false)
				httpResponse(400, array('message' => 'Media not found'));

			if ($check_media['pool']['id'] != $check_pool['id'])
				$ok = false;

			// name [optional]
			if (isset($infoJob['name'])) {
				$ok = is_string($infoJob['name']);
				if ($ok)
					$job['name'] = $infoJob['name'];
			} else
				$job['name'] = "copy_of_" . $check_archive['name'] . "_in_pool_" . $check_pool['name'];

			// type
			if ($ok) {
				$jobType = $dbDriver->getJobTypeId("copy-archive");
				if ($jobType === null || $jobType === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobTypeId(%s)', "copy-archive"), $_SESSION['user']['id']);
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
			if ($failed || !$ok)
				$dbDriver->cancelTransaction();

			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/copy => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));

			} if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/copy => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createJob(%s)', $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			$dbDriver->finishTransaction();

			httpAddLocation('/job/?id=' . $jobId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/archive/copy => Job %s created', $jobId), $_SESSION['user']['id']);
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
