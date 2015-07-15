<?php
/**
 * \addtogroup restore Restore archive
 * \section Create_restore_task Restore task creation
 * To create a restore task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/archive/restore/ \endverbatim
 * \param job : hash table
 * \li \c archive id (integer) : archive id
 * \li \c name [optional] (string) : restore task name, <em>default value : "restore_" + archive name</em>
 * \li \c nextstart [optional] (string) : restore task nextstart date, <em>default value : now</em>
 * \param filesFound : archive files array
 * \li \c filesFound (string array) : files to be restored
 * \param destination [optional] : restoration destination path
 * \li \c destination [optional] (string) : restoration destination path, <em>default value : original path</em>
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

			if (!$_SESSION['user']['canrestore'] || !$checkArchivePermission) {
				$dbDriver->cancelTransaction();
				httpResponse(403, array('message' => 'Permission denied'));
			}

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

			// host
			if ($ok) {
				$host = $dbDriver->getHost(posix_uname()['nodename']);
				if ($host === null || $host === false)
					$failed = true;
				else
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

			foreach ($filesFound as $file) {
				$selectedfileId = $dbDriver->getSelectedFile($file);

				if ($selectedfileId === null || !$dbDriver->linkJobToSelectedfile($jobId, $selectedfileId)) {
					$failed = true;
					break;
				}
			}

			if (isset($infoJob['destination'])) {
				$restoreto = $dbDriver->insertIntoRestoreTo($jobId, $infoJob['destination']);
				if (!$restoreto)
					$failed = true;
			}

			if ($failed) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
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