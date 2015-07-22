<?php
/**
 * \addtogroup add Add to archive
 * \section Create_add_task Add task creation
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
 *     \verbatim New job id is returned \endverbatim
 *   - \b 400 Archive id is required or archive id must be an integer or archive not found or incorrect input
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
			if (!isset($infoJob['archive']))
				httpResponse(400, array('message' => 'Archive id is required'));

			if (!is_int($infoJob['archive']))
				httpResponse(400, array('message' => 'Archive id must be an integer'));

			$dbDriver->startTransaction();

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

			// check archive
			$check_archive = $dbDriver->getArchive($infoJob['archive']);

			if (!$check_archive)
				$dbDriver->cancelTransaction();

			if ($check_archive === null)
				httpResponse(500, array('message' => 'Query failure'));
			elseif ($check_archive === false)
				httpResponse(400, array('message' => 'Archive not found'));

			// archive id
			$checkArchivePermission = $dbDriver->checkArchivePermission($infoJob['archive'], $_SESSION['user']['id']);
			if ($checkArchivePermission === null)
				$failed = true;
			else
				$job['archive'] = $infoJob['archive'];

			if (!$_SESSION['user']['canarchive'] || !$checkArchivePermission) {
				$dbDriver->cancelTransaction();
				httpResponse(403, array('message' => 'Permission denied'));
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
				if ($host === null || $host === false)
					$failed = true;
				else
					$job['host'] = $host;
			}

			// files (checking file access)
			$files = $infoJob['files'];
			$ok = isset($files) && is_array($files) && count($files) > 0;
			if ($ok)
				for ($i = 0, $n = count($files); $ok && $i < $n; $i++)
					$ok = is_string($files[$i]) && posix_access($files[$i], POSIX_F_OK);

			// gestion des erreurs
			if ($failed || !$ok)
				$dbDriver->cancelTransaction();

			if ($failed)
				httpResponse(500, array('message' => 'Query failure'));

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			}

			foreach ($files as $file) {
				$selectedfileId = $dbDriver->getSelectedFile($file);

				if ($selectedfileId === null || !$dbDriver->linkJobToSelectedfile($jobId, $selectedfileId)) {
					$failed = true;
					break;
				}
			}

			$dbDriver->finishTransaction();

			httpAddLocation('/job/?id=' . $jobId);
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