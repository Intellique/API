<?php
/**
 * \addtogroup Archive
 * \page archive
 * \subpage import
 * \section Import_archive_task Import archive task creation
 * To create an Import archive task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/archive/import/ \endverbatim
 * \param job : hash table
 * \li \c media id (integer) : media id
 * \li \c pool id (integer) : pool id
 * \li \c name [optional] (string) : import archive task name, <em>default value : "importArchive_" + archive name</em>
 * \li \c nextstart [optional] (string) : import archive task nextstart date, <em>default value : now</em>
 * \li \c options [optional] (hash table) : check archive options
 * \param files : archive files array
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
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'POST':
			checkConnected();

			$ok = true;
			$failed = false;

			$job = array(
				'interval' => null,
				'backup' => null,
				'media' => $formatInfo['media'],
				'archive' => null,
				'pool' => $formatInfo['pool'],
				'login' => $_SESSION['user']['id'],
				'metadata' => array(),
				'options' => array()
			);

			loadDbDriver('archive');

			if (!$_SESSION['user']['canarchive']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/import (%d) => A user that cannot archive tried to', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$formatInfo = httpParseInput();

			// media id
			if (!isset($formatInfo['media'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/import (%d) => Trying to import an archive without specifying media id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media id is required'));
			}

			if (filter_var($formatInfo['media'], FILTER_VALIDATE_INT) === false) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/import (%d) => Media id must be an integer and not %s', __LINE__, $formatInfo['media']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media id must be an integer'));
			}

			// pool id
			if (!isset($formatInfo['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/import (%d) => Trying to import an archive without specifying pool id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id is required'));
			}

			if (filter_var($formatInfo['pool'], FILTER_VALIDATE_INT) === false) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/import (%d) => Pool id must be an integer and not %s', __LINE__, $formatInfo['pool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/archive/import (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			// check media
			$media = $dbDriver->getMedia($formatInfo['media'], DB::DB_ROW_LOCK_SHARE);
			if (!$media)
				$dbDriver->cancelTransaction();
			if ($media === null)
				httpResponse(500, array('message' => 'Query failure'));
			elseif ($media === false)
				httpResponse(400, array('message' => 'Media not found'));

			$pool = $dbDriver->getPool($formatInfo['pool'], DB::DB_ROW_LOCK_SHARE);
			if (!$pool)
				$dbDriver->cancelTransaction();
			if ($pool === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/import (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/import (%d) => getPool(%s)', __LINE__, $formatInfo['pool']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($pool === false)
				httpResponse(400, array('message' => 'Pool not found'));

			if ($media['mediaformat']['id'] != $pool['mediaformat']['id']) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'mediaformat of pool and media should match'));
			}

			if ($media['archiveformat']['id'] != $pool['archiveformat']['id']) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'archiveformat of pool and media should match'));
			}

			if ($media['type'] == 'cleaning') {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Cleaning media is not allowed to import archive'));
			}

			if ($media['status'] != 'foreign') {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Selected media is not foreign'));
			}

			if ($media['pool'] != NULL) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Media sould not be a member of pool ("' . $pool['name'] . '")'));
			}

			if ($media['archiveformat'] == NULL) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Archive format should be known'));
			}

			if ($pool['deleted']) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Trying to import a media into a deleted pool'));
			}

			// name [optional]
			if (isset($formatInfo['name'])) {
				$ok = is_string($formatInfo['name']);
				if ($ok)
					$job['name'] = $formatInfo['name'];
			} elseif (isset($media['name']))
				$job['name'] = "formatMedia_" . $media['name'];
			 elseif (isset($media['label']))
				$job['name'] = "formatMedia_" . $media['label'];
			 elseif (isset($media['mediumserialnumber']))
				$job['name'] = "formatMedia_" . $media['mediumserialnumber'];

			// type
			if ($ok) {
				$jobType = $dbDriver->getJobTypeId("format-media");
				if ($jobType === null || $jobType === false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/import (%d) => getJobTypeId(%s)', __LINE__, "format-media"), $_SESSION['user']['id']);
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
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/import (%d) => getHost(%s)', __LINE__, posix_uname()['nodename']), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['host'] = $host;
			}

			// gestion des erreurs
			if (!$ok)
				$dbDriver->cancelTransaction();
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/import (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/copy (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/copy (%d) => createJob(%s)', __LINE__, $job), $_SESSION['user']['id']);
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->finishTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/archive/import (%d) => Job %s created', __LINE__, $jobId), $_SESSION['user']['id']);
			httpAddLocation('/job/?id=' . $jobId);
			httpResponse(201, array(
				'message' => 'Job created successfully',
				'job_id' => $jobId,
			));

		case 'OPTIONS':
			httpOptionsMethod(HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
