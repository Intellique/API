<?php
/**
 * \addtogroup media
 * \page media
 * \subpage format Media Format
 * \section Format_media_task Format media task creation
 * To create a Format media task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/media/format/ \endverbatim
 * \param job : hash table
 * \li \c media id (integer) : media id
 * \li \c name [optional] (string) : format media task name, <em>default value : "formatMedia_" + media name</em>
 * \li \c nextstart [optional] (string) : format media task nextstart date, <em>default value : now</em>
 * \li \c options [optional] (hash table) : check media options
 * \param files : media files array
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

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/media/format (%d) => A non admin user tried to format a media', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$formatInfo = httpParseInput();
			// media id
			if (!isset($formatInfo['media'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/media/format (%d) => Trying to format a media without specifying media id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media id is required'));
			} elseif (!is_integer($formatInfo['media'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/format (%d) => Media id must be an integer and not %s', __LINE__, $formatInfo['media']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media id must be an integer'));
			}

			// pool id
			if (!isset($formatInfo['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/media/format (%d) => Trying to format a media without specifying pool id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id is required'));
			} elseif (!is_integer($formatInfo['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/format (%d) => Pool id must be an integer and not %s', __LINE__, $formatInfo['pool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/media/format (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$ok = true;
			$failed = false;

			// check media
			$media = $dbDriver->getMedia($formatInfo['media'], DB::DB_ROW_LOCK_SHARE);
			if (!$media)
				$dbDriver->cancelTransaction();
			if ($media === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/media/format (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/format (%d) => getMedia(%s)', __LINE__, $formatInfo['media']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($media === false)
				httpResponse(400, array('message' => 'Media not found'));

			$pool = $dbDriver->getPool($formatInfo['pool'], DB::DB_ROW_LOCK_SHARE);
			if (!$pool)
				$dbDriver->cancelTransaction();
			if ($pool === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/media/format (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/format (%d) => getPool(%s)', __LINE__, $formatInfo['pool']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($pool === false)
				httpResponse(400, array('message' => 'Pool not found'));

			if ($media['mediaformat']['id'] != $pool['mediaformat']['id']) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'mediaformat of pool and media should match'));
			}

			if ($media['type'] == 'cleaning') {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'mediaformat of pool and media should match'));
			}

			if ($media['type'] == 'worm' && $media['nbfiles'] > 0) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Impossible to format a worm media containing data'));
			}

			if ($media['pool'] != NULL) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Trying to format a media which is member of a pool'));
			}

			if ($pool['deleted'] ) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Trying to format a media to a deleted pool'));
			}

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
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobTypeId(%s)', "format-media"), $_SESSION['user']['id']);
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
			//option block size ne prend d'un entier ou une chaine de caractères (auto, default...)
			if ($ok && isset($formatInfo['options']['block size'])) {
				if (is_integer($formatInfo['options']['block size']) and $formatInfo['options']['block size'] >= 0)
					$job['options']['block size'] = $formatInfo['options']['block size'];
				elseif (is_string($formatInfo['options']['block size'])) {
					if (array_search($formatInfo['options']['block size'], array('auto', 'default') !== false))
						$job['options']['block size'] = $formatInfo['options']['block size'];
					else
						$ok = false;
				} else
					$ok = false;
			}

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

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/media/format => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Incorrect input'));
			}

			$jobId = $dbDriver->createJob($job);
			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/media/format (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/format (%d) => createJob(%s)', __LINE__, $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			httpAddLocation('/job/?id=' . $jobId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/media/format (%d) => Job %s created', __LINE__, $jobId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Job created successfully',
				'job_id' => $jobId,
			));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
