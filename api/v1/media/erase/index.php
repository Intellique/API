<?php
/**
 * \addtogroup media
 * \page media
 * \subpage erase Media Erase
 * \section Erase_media_task Erase media task creation
 * To create an Erase media task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/media/erase/ \endverbatim
 * \param job : hash table
 * \li \c media id (integer) : media id
 * \li \c name [optional] (string) : Erase media task name, <em>default value : "eraseMedia_" + media name</em>
 * \li \c host [optional] (string) : hostname to run the task, <em>default value : hostname owned by current api</em>
 * \li \c nextstart [optional] (string) : Erase media task nextstart date, <em>default value : now</em>
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
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to erase a media', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$eraseInfo = httpParseInput();
			// media id
			if (!isset($eraseInfo['media'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/media/erase (%d) => Trying to erase a media without specifying media id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media id is required'));
			} elseif (!is_integer($eraseInfo['media'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/erase (%d) => Media id must be an integer and not %s', __LINE__, $eraseInfo['media']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media id must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/media/erase (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$ok = true;
			$failed = false;

			// check media
			$media = $dbDriver->getMedia($eraseInfo['media'], DB::DB_ROW_LOCK_SHARE);

			if (!$media)
				$dbDriver->cancelTransaction();
			if ($media === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/media/erase => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/erase => Query getMedia(%s)', __LINE__, $eraseInfo['media']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($media === false)
				httpResponse(400, array('message' => 'Media not found'));

			if ($media['type'] == 'cleaning') {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/media/erase (%d) => Trying to delete a cleaning media', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Trying to delete a cleaning media'));
			}

			if ($media['type'] == 'worm') {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/media/erase (%d) => Trying to delete a worm media', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Trying to delete a worm media'));
			}

			$archives = $dbDriver->getArchivesByMedia($eraseInfo['media']);

			if (!$archives and !is_array($archives))
				$dbDriver->cancelTransaction();
			if ($archives === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/media/erase (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/erase (%d) => getArchivesByMedia(%s)', __LINE__, $eraseInfo['media']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			foreach ($archives as $archive_id) {
				$archive = $dbDriver-> getArchive($archive_id, DB::DB_ROW_LOCK_SHARE);
				if (!$archive || !$archive['deleted'])
					$dbDriver->cancelTransaction();
				if ($archive === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/media/erase (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/erase (%d) => getArchive(%s)', __LINE__, $archive_id), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
				if (!$archive['deleted'])
					httpResponse(400, array('message' => 'Archive "' . $archive['name'] . '" should be deleted'));
			}

			$job = array(
				'interval' => null,
				'backup' => null,
				'media' => $eraseInfo['media'],
				'archive' => null,
				'pool' => null,
				'login' => $_SESSION['user']['id'],
				'metadata' => array(),
				'options' => array()
			);

			// name [optional]
			if (isset($eraseInfo['name'])) {
				$ok = is_string($eraseInfo['name']);
				if ($ok)
					$job['name'] = $eraseInfo['name'];
			} elseif (isset($media['name']))
				$job['name'] = "formatMedia_" . $media['name'];
			 elseif (isset($media['label']))
				$job['name'] = "formatMedia_" . $media['label'];
			 elseif (isset($media['mediumserialnumber']))
				$job['name'] = "formatMedia_" . $media['mediumserialnumber'];

			// type
			if ($ok) {
				$jobType = $dbDriver->getJobTypeId("erase-media");
				if ($jobType === null || $jobType === false)
					$failed = true;
				else
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
			if ($ok && isset($eraseInfo['options']['quick mode'])) {
				if (is_bool($eraseInfo['options']['quick mode']))
					$job['options']['quick mode'] = $eraseInfo['options']['quick mode'];
				else
					$ok = false;
			}

			// host
			if ($ok) {
				$hostname = isset($infoJob['host']) ? $infoJob['host'] : posix_uname()['nodename'];
				$host = $dbDriver->getHost($hostname);
				if ($host === null || $host === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getHost(%s)', $hostname), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['host'] = $host;
			}

			// gestion des erreurs
			if ($failed || !$ok)
				$dbDriver->cancelTransaction();

			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/media/erase (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$jobId = $dbDriver->createJob($job);
			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/media/erase (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/media/erase (%d) => createJob(%s)', __LINE__, $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			httpAddLocation('/job/?id=' . $jobId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/media/erase (%d) => Job created successfully', __LINE__, $jobId), $_SESSION['user']['id']);
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
