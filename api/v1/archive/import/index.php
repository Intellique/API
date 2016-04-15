<?php
/**
 * \addtogroup ArchiveImport Import an archive
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
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['canarchive']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/import => A user that cannot archive tried to', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$formatInfo = httpParseInput();
			// media id
			if (!isset($formatInfo['media'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/import => Trying to import an archive without specifing media id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media id is required'));
			}

			if (!is_int($formatInfo['media'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/import => Media id must be an integer and not %s', $formatInfo['media']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media id must be an integer'));
			}

			// pool id
			if (!isset($formatInfo['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/import => Trying to import an archive without specifing pool id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id is required'));
			}

			if (!is_int($formatInfo['pool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/import => Pool id must be an integer and not %s', $formatInfo['pool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id must be an integer'));
			}

			$dbDriver->startTransaction();

			$ok = true;
			$failed = false;

			// check media
			$media = $dbDriver->getMedia($formatInfo['media']);

			if (!$media)
				$dbDriver->cancelTransaction();

			if ($media === null)
				httpResponse(500, array('message' => 'Query failure'));
			elseif ($media === false)
				httpResponse(400, array('message' => 'Media not found'));

			$pool = $dbDriver->getPool($formatInfo['pool']);

			if (!$pool)
				$dbDriver->cancelTransaction();

			if ($pool === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/import => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPool(%s)', $formatInfo['pool']), $_SESSION['user']['id']);
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

			if ($pool['deleted'] ) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Trying to import a media into a deleted pool'));
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



			// host
			if ($ok) {
				$host = $dbDriver->getHost(posix_uname()['nodename']);
				if ($host === null || $host === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getHost(%s)', posix_uname()['nodename']), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['host'] = $host;
			}

			// gestion des erreurs
			if ($failed || !$ok)
				$dbDriver->cancelTransaction();

			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/import => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/copy => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createJob(%s)', $job), $_SESSION['user']['id']);
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			}

			$dbDriver->finishTransaction();
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/archive/import => Job %s created', $jobId), $_SESSION['user']['id']);
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