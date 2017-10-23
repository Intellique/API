<?php
/**
 * \addtogroup Archive
 * \page archive
 * \subpage restore
 * \section Create_restoration_task Restoration task creation
 * To create a restoration task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/archive/restore/ \endverbatim
 * \param job : hash table
 * \li \c archive id (integer) : archive id
 * \li \c name [optional] (string) : restoration task name, <em>default value : "restore_" + archive name</em>
 * \li \c host [optional] (string) : hostname to run the task, <em>default value : hostname owned by current api</em>
 * \li \c nextstart [optional] (string) : restoration task nextstart date, <em>default value : now</em>
 * \param files : archive files array
 * \li \c files (string array) : files to be restored
 * \param destination [optional] : restoration destination path
 * \li \c destination [optional] (string) : restoration destination path, <em>default value : original path</em>
 * \return HTTP status codes :
 *   - \b 201 Job created successfully
 *     \verbatim New job id is returned \endverbatim
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

			$infoJob = httpParseInput();

			// archive id
			if (!isset($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/archive/restore => Trying to restore an archive without specifying archive id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id is required'));
			}

			if (!is_integer($infoJob['archive'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/restore => Media id must be an integer and not %s', $infoJob['archive']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Archive id must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/archive/restore (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

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

			$checkArchivePermission = $dbDriver->checkArchivePermission($infoJob['archive'], $_SESSION['user']['id']);
			if ($checkArchivePermission === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/restore (%d) => checkArchivePermission(%s)', __LINE__, $archive['id']), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}
			if (!$_SESSION['user']['canrestore'] || !$checkArchivePermission) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/archive/restore (%d) => A non-admin user tried to restore an archive', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			// check archive
			$check_archive = $dbDriver->getArchive($infoJob['archive'], DB::DB_ROW_LOCK_SHARE);
			if (!$check_archive)
				$dbDriver->cancelTransaction();
			if ($check_archive === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/restore (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/restore (%d) => getArchive(%s)', __LINE__, $infoJob['archive']), $_SESSION['user']['id']);
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
				$job['name'] = "restore_" . $check_archive['name'];

			// type
			if ($ok) {
				$jobType = $dbDriver->getJobTypeId("restore-archive");
				if ($jobType === null || $jobType === false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/restore (%d) => getJobTypeId(%s)', __LINE__, "restore-archive"), $_SESSION['user']['id']);
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
				$hostname = isset($infoJob['host']) ? $infoJob['host'] : posix_uname()['nodename'];
				$host = $dbDriver->getHost($hostname);
				if ($host === null || $host === false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/restore (%d) => getHost(%s)', __LINE__, $hostname), $_SESSION['user']['id']);
					$failed = true;
				} else
					$job['host'] = $host;
			}

			// files
			$files = $infoJob['files'];
			$filesFound = array();
			if ($ok) {
				$params = array();
				$result = $dbDriver->getFilesFromArchive($infoJob['archive'], $params);
				if ($result['query_executed'] == false) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/restore (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/restore (%d) => getFilesFromArchive(%s, %s)', __LINE__, $infoJob['archive'], $params), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}

				$iter = $result['iterator'];
				while (count($files) > 0 && $iter->hasNext()) {
					$row = $iter->next();
					$fileName = $row->getValue('name');

					$pos = array_search($fileName, $files);
					if ($pos !== false) {
						array_push($filesFound, $fileName);
						array_splice($files, $pos, 1);
					}
				}

				$ok = count($files) == 0;
			}

			// destination [optional]
			if ($ok && isset($infoJob['destination']))
				$ok = is_string($infoJob['destination']);

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/restore (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Incorrect input'));
			}

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/archive/restore (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/restore (%d) => createJob(%s)', __LINE__, $job), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			foreach ($filesFound as $file) {
				$selectedfileId = $dbDriver->getSelectedFile($file);

				if ($selectedfileId === null || !$dbDriver->linkJobToSelectedfile($jobId, $selectedfileId)) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/archive/restore (%d) => getSelectedFile(%s)', __LINE__, $file), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
			}

			if (isset($infoJob['destination'])) {
				$restoreto = $dbDriver->insertIntoRestoreTo($jobId, $infoJob['destination']);
				if (!$restoreto) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('insertIntoRestoreTo(%s, %s)', $jobId, $infoJob['destination']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
			}

			if (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			}

			httpAddLocation('/job/?id=' . $jobId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/archive/restore => Job %s created', $jobId), $_SESSION['user']['id']);
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
