<?php
	require_once("../../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("uuid.php");
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (!isset($_GET['id']) or !is_numeric($_GET['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolmirror => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool mirror ID must be an integer'));
			}

			$result = $dbDriver->getPoolsByPoolMirror($_GET['id'], null);
			if ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/poolmirror/synchronize/ => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolsByPoolMirror(%s, null)', $_GET['id']));
				httpResponse(500, array(
					'message' => 'Query failure',
					'poolmirror' => array()
				));
			}

			if (!$_SESSION['user']['isadmin']) {
				$autorise = false;
				foreach ($result['rows'] as $pool) {
					$permission_granted = $dbDriver->checkPoolPermission($pool, $_SESSION['user']['id']);
					if ($permission_granted === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pool/synchronize => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkPoolPermission(%s, %s)', $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
						httpResponse(500, array(
							'message' => 'Query failure',
							'pool' => array()
						));
					} elseif ($permission_granted === true) {
						$autorise = true;
						break;
					}
				}
				if ($autorise === false)
					httpResponse(403, array('message' => 'Permission denied'));
			}

			$archivesByPool = array();
			$archive2archiveMirror = array();
			$archiveMirror2archive = array();

			foreach ($result['rows'] as $pool) {
				$temp = $dbDriver->getArchiveMirrorsByPool($pool, $_GET['id']);

				if (!$temp['query_executed']) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pool/synchronize => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchivesByPool(%s)', $pool));
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				}

				$archivesByPool[$pool] = array();
				foreach ($temp['result'] as list($archive, $archiveMirror)) {
					$archivesByPool[$pool][$archive] = array();

					if ($archiveMirror == null)
						continue;

					$archive2archiveMirror[$archive] = $archiveMirror;
					if (array_key_exists($archiveMirror, $archiveMirror2archive))
						$archiveMirror2archive[$archiveMirror][] = $archive;
					else
						$archiveMirror2archive[$archiveMirror] = array($archive);
				}
			}

			$nbArchives = 0;
			$nbSynchronized = 0;

			foreach ($archivesByPool as $poolA => &$archivesA) {
				foreach ($archivesByPool as $poolB => &$archivesB) {
					if ($poolA === $poolB)
						continue;

					$archiveFound = array();
					foreach ($archivesA as $archiveA => &$archiveInfoA) {
						foreach ($archivesB as $archiveB => &$archiveInfoB) {
							if (in_array($archiveA, $archiveInfoB) || in_array($archiveB, $archiveInfoA))
								continue;

							if (in_array($archiveB, $archiveFound))
								continue;

							if (!array_key_exists($archiveA, $archive2archiveMirror))
								continue;

							if (in_array($archiveB, $archiveMirror2archive[$archive2archiveMirror[$archiveA]])) {
								$archiveInfoA[] = $archiveB;
								$archiveInfoB[] = $archiveA;
								$archiveFound[] = $archiveB;
								break;
							}
						}
					}
				}
			}

			$nbPools = count($archivesByPool);
			$sync = array();

			foreach ($archivesByPool as $pool => &$archives) {
				foreach ($archives as $archive => &$info) {
					if (in_array($archive, $sync))
						continue;

					if (1 + count($info) == $nbPools) {
						$sync[] = $archive;
						$nbSynchronized++;
					}

					if (count($info) > 0)
						$sync = array_merge($sync, $info);

					$nbArchives++;
				}
			}

			if ($nbArchives == $nbSynchronized)
				httpResponse(200, array(
					"message" => "Pool mirror is synchronized",
					"nb synchronized archives" => $nbSynchronized,
					"nb archives" => $nbArchives,
					"synchonized" => true
				));
			else
				httpResponse(200, array(
					"message" => "Pool mirror not is synchronized",
					"nb synchronized archives" => $nbSynchronized,
					"nb archives" => $nbArchives,
					"synchonized" => false
				));

			break;

		case 'POST':
			checkConnected();

			$poolmirror = httpParseInput();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to delete a user', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (!isset($poolmirror))
				httpResponse(400, array('message' => 'Poolmirror information is required'));

			if (isset($poolmirror['id'])) {
				if (!is_numeric($poolmirror['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolmirror => id must be an integer and not "%s"', $poolmirror['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'id must be an integer'));
				}
			} else {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolmirror => id is required "%s"', $poolmirror['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'id is required'));
			}

			$result = $dbDriver->getPoolsByPoolMirror($poolmirror['id'], null);
			if ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/poolmirror/synchronize/ => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolsByPoolMirror(%s, null)', $poolmirror['id']));
				httpResponse(500, array(
					'message' => 'Query failure',
					'poolmirror' => array()
				));
			}

			$archivesByPool = array();
			foreach ($result['rows'] as $pool) {
				$temp = $dbDriver->getArchivesByPool($pool);
				if (!$temp['query_executed']){
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pool/synchronize => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchivesByPool(%s)', $pool));
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array(),
					));
				}
				$archivesByPool[$pool] = $temp['rows'];
			}

			$jobType = $dbDriver->getJobTypeId("copy-archive");
			if ($jobType === null || $jobType === false) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getJobTypeId(%s)', "copy-archive"), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'pool' => array(),
				));
			}

			if (isset($poolmirror['nextstart'])) {
				$nextstart = dateTimeParse($poolmirror['nextstart']);
				if ($job['nextstart'] === null)
					httpResponse(400, array('message' => 'Incorrect input'));
			} else
				$nextstart = new DateTime();

			$host = $dbDriver->getHost(posix_uname()['nodename']);
			if ($host === null || $host === false) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getHost(%s)', posix_uname()['nodename']), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'pool' => array(),
				));
			}

			if (isset($poolmirror['options']['quick_mode'])) {
				if (is_bool($poolmirror['options']['quick_mode']))
					$quick_mode = $poolmirror['options']['quick_mode'];
				else
					httpResponse(400, array('message' => 'Incorrect input'));
			} elseif (isset($poolmirror['options']['thorough_mode'])) {
				if (is_bool($poolmirror['options']['thorough_mode']))
					$quick_mode = !$poolmirror['options']['thorough_mode'];
				else
					httpResponse(400, array('message' => 'Incorrect input'));
			}

			$jobs = array();

			$dbDriver->startTransaction();

			foreach ($archivesByPool as $poolA => &$archivesA) {
				foreach ($archivesByPool as $poolB => &$archivesB) {
					if ($poolA === $poolB)
						continue;

					$poolInfo = $dbDriver->getPool($poolB);

					foreach ($archivesA as $archiveA) {
						$found = false;

						foreach ($archivesB as $archiveB) {
							$result = $dbDriver->checkArchiveMirrorInCommon($archiveA, $archiveB);
							if ($result['result'] === null) {
								$dbDriver->cancelTransaction();
								httpResponse(500, array(
									'message' => 'Query failure',
									'pool' => array(),
								));
							}

							if ($result['result']) {
								$found = true;
								break;
							}
						}

						if (!$found) {

							$archiveInfo = $dbDriver->getArchive($archiveA);

							$job = array(
								'interval' => null,
								'backup' => null,
								'media' => null,
								'login' => $_SESSION['user']['id'],
								'metadata' => array(),
								'options' => array(),
								'type' => $jobType,
								'archive' => $archiveA,
								'pool' => $poolB,
								'name' => "copy_of_" . $check_archive['name'] . "_in_pool_" . $poolInfo['name'],
								'nextstart' => $nextstart,
								'host' => $host
							);

							if (isset($quick_mode))
								$job['options']['quick_mode'] = $quick_mode;

							$jobId = $dbDriver->createJob($job);

							if ($jobId === null) {
								$dbDriver->cancelTransaction();
								$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/archive/copy => Query failure', $_SESSION['user']['id']);
								$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createJob(%s)', $job), $_SESSION['user']['id']);
								httpResponse(500, array('message' => 'Query failure'));
							} else
								$jobs[] = $jobId;
						}
					}
				}
			}

			$dbDriver->finishTransaction();

			//$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/archive/copy => Job %s created', $jobId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Jobs created successfully',
				'jobs_id' => &$jobs
			));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET | HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
