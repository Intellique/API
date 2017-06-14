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

			foreach ($archivesByPool as $poolA => &$archivesA) {
				foreach ($archivesByPool as $poolB => &$archivesB) {
					if ($poolA === $poolB)
						continue;

					foreach ($archivesA as $archiveA) {
						$found = false;

						foreach ($archivesB as $archiveB) {
							$result = $dbDriver->checkArchiveMirrorInCommon($archiveA, $archiveB);
							if ($result['result'] === null)
								httpResponse(500, array(
									'message' => 'Query failure',
									'pool' => array(),
								));

							if ($result['result']) {
								$found = true;
								break;
							}
						}

						if (!$found)
							httpResponse(200, array(
								"message" => "Pool mirror is not synchronized",
								"synchonized" => false
							));
					}
				}
			}

			httpResponse(200, array(
				"message" => "Pool mirror is synchronized",
				"synchonized" => true
			));
			break;

		case 'POST':

			checkConnected();

			$poolmirror = httpParseInput();

			if (!isset($poolmirror))
				httpResponse(400, array('message' => 'Poolmirror information is required'));

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/poolmirror => A non-admin user tried to create a poolmirror', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			//uuid
			if (isset($poolmirror['uuid'])) {
				if (!is_string($poolmirror['uuid'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolmirror => uuid must be a string and not "%s"', $poolmirror['uuid']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'uuid must be a string'));
				}

				if (!uuid_is_valid($poolmirror['uuid']))
					httpResponse(400, array('message' => 'uuid is not valid'));
			} else
				$poolmirror['uuid'] = uuid_generate();

			//name
			if (!isset($poolmirror['name'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/poolmirror => Trying to create a poolmirror without specifying poolmirror name', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pool mirror name is required'));
			}

			//synchronized
			if (!isset($poolmirror['synchronized']))
				httpResponse(400, array('message' => 'synchronized is required'));
			elseif (!is_bool($poolmirror['synchronized'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolmirror => synchronized must be a boolean and not "%s"', $poolmirror['synchronized']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'synchronized must be a boolean'));
			}

			$poolmirrorId = $dbDriver->createPoolMirror($poolmirror);

			if ($poolmirrorId === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/poolmirror => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createPoolMirror(%s)', var_export($poolmirror, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			}

			httpAddLocation('/poolmirror/?id=' . $poolmirrorId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('Pool mirror %s created', $poolmirrorId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Pool mirror created successfully',
				'pool_id' => $poolmirrorId
			));
			break;

		case 'PUT':
			checkConnected();
			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/poolmirror => A non-user admin tried to update a poolmirror', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}


			$poolmirror = httpParseInput();
			if ($poolmirror === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/poolmirror => Trying to update a poolmirror without specifying it', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror is required'));
			}
			if (!isset($poolmirror['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/poolmirror => Trying to update a poolmirror without specifying its id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror id is required'));
			}

			if (!is_int($poolmirror['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolmirror => id must be an integer and not "%s"', $poolmirror['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror id must be an integer'));
			}

			$poolmirror_base = $dbDriver->getPoolMirror($poolmirror['id']);

			//uuid
			if (isset($poolmirror['uuid']))
				httpResponse(400, array('message' => 'uuid cannot be modified'));
			$poolmirror['uuid'] = $poolmirror_base['uuid'];

			//name
			if (!isset($poolmirror['name']))
				$poolmirror['name'] = $poolmirror_base['name'];
			elseif (!is_string($poolmirror['name'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolmirror => name must be a string and not "%s"', $poolmirror['name']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'name must be a string'));
			}

			//synchronized
			if (!isset($poolmirror['synchronized']))
				$poolmirror['synchronized'] = $poolmirror_base['synchronized'];
			elseif (!is_bool($poolmirror['synchronized'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolmirror => synchronized must be a boolean and not "%s"', $poolmirror['synchronized']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'synchronized must be a boolean'));
			}

			$result = $dbDriver->updatePoolMirror($poolmirror);
			if ($result) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('Poolmirror %s updated', $poolmirror['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool mirror updated successfully'));
			}
			else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/poolmirror => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updatePoolMirror(%s)', var_export($poolmirror, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}


			break;

		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'DELETE api/v1/poolmirror => A non-admin user tried to delete a poolmirror', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/poolmirror => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'poolmirror ID must be an integer'));
				}

				$poolmirror = $dbDriver->getPoolMirror($_GET['id']);
				if ($poolmirror === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/poolmirror => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolMirror(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($poolmirror === false)
					httpResponse(404, array('message' => 'Pool mirror not found'));

				$result = $dbDriver->deletePoolMirror($_GET['id']);
				if ($result === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/poolmirror => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('deletePoolMirror(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}

				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('poolmirror %s deleted', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool mirror deleted'));

			} else
				httpResponse(400, array('message' => 'Pool mirror ID required'));

		break;
	}
?>
