<?php
/**
 * \addtogroup pool
 * \section Pool_ID Pool information
 * To get pool by its id
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/pool/ \endverbatim
 * \param id : pool id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Pool information is returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Pool not found
 *   - \b 500 Query failure
 *
 * \section Pools Pools ids (multiple list)
 * To get pools ids list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/pool/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple pools ids list do not pass an id or ids as parameter</b>
 * \section Pool_deletion Pool deletion
 * To delete a pool,
 * use \b DELETE method : <i>with pool id</i>
 * \section Pool-creation Pool creation
 * To create a pool,
 * use \b POST method <i>with pool parameters (uuid, name, archiveformat, mediaformat) </i>
 * \section Pool-update Pool update
 * To update a pool,
 * use \b PUT method <i>with pool parameters (id, uuid, name, archiveformat, mediaformat) </i>
 * \verbatim path : /storiqone-backend/api/v1/pool/ \endverbatim
 *        Name          |         Type             |                     Value
 * | :----------------: | :---------------------:  | :---------------------------------------------------------:
 * |  id                |  integer                 | non NULL Default value, nextval('pool_id_seq'::regclass)
 * |  uuid              |  uuid                    | non NULL
 * |  name              |  character varying(64)   | non NULL
 * |  archiveformat     |  integer                 | non NULL
 * |  mediaformat       |  integer                 | non NULL
 * |  autocheck         |  autocheckmode           | non NULL Default value, 'none'::autocheckmode
 * |  lockcheck         |  boolean                 | non NULL Default value, false
 * |  growable          |  boolean                 | non NULL Default value, false
 * |  unbreakablelevel  |  unbreakablelevel        | non NULL Default value, 'none'::unbreakablelevel
 * |  rewritable        |  boolean                 | non NULL Default value, true
 * |  metadata          |  json                    | non NULL Default value, '{}'::json
 * |  backuppool        |  boolean                 | non NULL Default value, false
 * |  poolmirror        |  integer                 |                           _
 * |  deleted           |  boolean                 | non NULL Default value, false
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("uuid.php");
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'DELETE api/v1/pool => A non-admin user tried to delete a pool', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/pool => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Pool ID must be an integer'));
				}

				$pool = $dbDriver->getPool($_GET['id']);
				if ($pool === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPool(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($pool === false)
					httpResponse(404, array('message' => 'Pool not found'));

				$pool['deleted'] = true;

				$result = $dbDriver->updatePool($pool);
				if ($result === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updatePool(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($result === false)
					httpResponse(404, array('message' => 'Pool not found'));
				else {
					$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('Pool %s deleted', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(200, array('message' => 'Pool deleted'));
				}
			} else
				httpResponse(400, array('message' => 'Pool ID required'));

		break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pool => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Pool ID must be an integer'));
				}

			$pool = $dbDriver->getPool($_GET['id']);
				if ($pool === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPool(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				} elseif ($pool === false)
					httpResponse(404, array(
						'message' => 'Pool not found',
						'pool' => array()
					));

				$permission_granted = $dbDriver->checkPoolPermission($_GET['id'], $_SESSION['user']['id']);
				if ($permission_granted === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('checkPoolPermission(%s, %s)', $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				} elseif ($permission_granted === false)
					httpResponse(403, array('message' => 'Permission denied'));

				httpResponse(200, array(
						'message' => 'Query succeeded',
						'pool' => $pool
				));
			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['limit'])) {
					if (is_numeric($_GET['limit']) && $_GET['limit'] > 0)
						$params['limit'] = intval($_GET['limit']);
					else
						$ok = false;
				}
				if (isset($_GET['offset'])) {
					if (is_numeric($_GET['offset']) && $_GET['offset'] >= 0)
						$params['offset'] = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$result = $dbDriver->getPoolsByPoolgroup($_SESSION['user']['poolgroup'], $params);
				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolsByPoolgroup(%s, %s)', $_SESSION['user']['poolgroup'], $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'pools' => array(),
						'total_rows' => 0
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'pools' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/pool => A non-admin user tried to create a pool', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$pool = httpParseInput();
			if (isset($pool['uuid'])) {
				if (!is_string($pool['uuid'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => uuid must be a string and not "%s"', $pool['uuid']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'uuid must be a string'));
				}

				if (!uuid_is_valid($pool['uuid']))
					httpResponse(400, array('message' => 'uuid is not valid'));
			} else
				$pool['uuid'] = uuid_generate();

			if (!isset($pool['name'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/pool => Trying to create a pool without specifying pool name', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pool name is required'));
			}

			if (!isset($pool['archiveformat'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/pool => Trying to create a pool without specifying archiveformat', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'archiveformat is required'));
			}

			if (is_int($pool['archiveformat'])) {
				$archiveformat = $dbDriver->getArchiveFormat($pool['archiveformat']);
				if ($archiveformat === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchiveFormat(%s)', $pool['archiveformat']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				}
				if ($archiveformat === False)
					httpResponse(400, array('message' => 'archiveformat id does not exist'));
			} elseif (is_array ($pool['archiveformat']) and (array_key_exists('id', $pool['archiveformat']) or array_key_exists('name', $pool['archiveformat']))) {
				if (array_key_exists('id', $pool['archiveformat'])) {
					$archiveformat = $dbDriver->getArchiveFormat($pool['archiveformat']['id']);
					if ($archiveformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/pool => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchiveFormat(%s)', $pool['archiveformat']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					}
					if ($archiveformat === False)
						httpResponse(400, array('message' => 'archiveformat id does not exist'));
					$pool['archiveformat'] = $pool['archiveformat']['id'];
				} else {
					$archiveformat = $dbDriver->getArchiveFormatByName($pool['archiveformat']['name']);
					if ($archiveformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/pool => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchiveFormatByName(%s)', $pool['archiveformat']['name']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					}
					if ($archiveformat === False)
						httpResponse(400, array('message' => 'archiveformat name does not exist'));
					$pool['archiveformat'] = $archiveformat;
				}
			} else
				httpResponse(400, array('message' => 'Specified archiveformat is invalid'));

			if (!isset($pool['mediaformat'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/pool => Trying to create a pool without specifying mediaformat', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'mediaformat is required'));
			}

			if (is_int($pool['mediaformat'])) {
				$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']);
				if ($mediaformat === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormat(%s)', $pool['mediaformat']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				}
				if ($mediaformat === False)
					httpResponse(400, array('message' => 'mediaformat id does not exist'));
			} elseif (is_array ($pool['mediaformat']) and (array_key_exists('id', $pool['mediaformat']) or array_key_exists('name', $pool['mediaformat']))) {
				if (array_key_exists('id', $pool['mediaformat'])) {
					$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']['id']);
					if ($mediaformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/pool => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormat(%s)', $pool['mediaformat']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					}
					if ($mediaformat === False)
						httpResponse(400, array('message' => 'mediaformat id does not exist'));
					$pool['mediaformat'] = $pool['mediaformat']['id'];
				} else {
					$mediaformat = $dbDriver->getMediaFormatByName($pool['mediaformat']['name']);
					if ($mediaformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/pool => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormatByName(%s)', $pool['mediaformat']['name']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					}
					if ($mediaformat === False)
						httpResponse(400, array('message' => 'mediaformat name does not exist'));
					$pool['mediaformat'] = $mediaformat;
				}
			} else
				httpResponse(400, array('message' => 'Specified mediaformat is invalid'));

			$autocheckmode = array('quick mode', 'thorough mode', 'none');
			if (!isset($pool['autocheck']))
				$pool['autocheck'] = 'none';
			elseif (!is_string ($pool['autocheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => autocheckmode must be a string and not "%s"', $pool['autocheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'autocheckmode must be a string'));
			}
			elseif (array_search($pool['autocheck'], $autocheckmode) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $autocheckmode));
				httpResponse(400, array('message' => 'autocheckmode value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pool['lockcheck']))
				$pool['lockcheck'] = False;
			elseif (!is_bool($pool['lockcheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => lockcheck must be a boolean and not "%s"', $pool['lockcheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'lockcheck must be a boolean'));
			}
			if (!isset($pool['growable']))
				$pool['growable'] = False;
			elseif (!is_bool($pool['growable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => growable must be a boolean and not "%s"', $pool['growable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'growable must be a boolean'));
			}

			$unbreakablelevel = array('archive', 'file', 'none');
			if (!isset($pool['unbreakablelevel']))
				$pool['unbreakablelevel'] = 'none';
			elseif (!is_string ($pool['unbreakablelevel'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => unbreakablelevel must be a string and not "%s"', $pool['unbreakablelevel']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'unbreakablelevel must be a string'));
			}
			elseif (array_search($pool['unbreakablelevel'], $unbreakablelevel) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $unbreakablelevel));
				httpResponse(400, array('message' => 'unbreakablelevel value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pool['rewritable']))
				$pool['rewritable'] = True;
			elseif (!is_bool($pool['rewritable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => rewritable must be a boolean and not "%s"', $pool['rewritable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'rewritable must be a boolean'));
			}

			if (!isset($pool['metadata']))
				$pool['metadata'] = array();

			if (!isset($pool['backuppool']))
				$pool['backuppool'] = False;
			elseif (!is_bool($pool['backuppool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => backuppool must be a boolean and not "%s"', $pool['backuppool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'backuppool must be a boolean'));
			}

			if (!isset($pool['poolmirror']))
				$pool['poolmirror'] = NULL;
			elseif (!is_int($pool['poolmirror'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => poolmirror must be an integer and not "%s"', $pool['poolmirror']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror must be an integer'));
			}

			$poolId = $dbDriver->createPool($pool);

			if ($poolId === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/pool => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createPool(%s)', $pool), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			}

			httpAddLocation('/pool/?id=' . $poolId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('Pool %s created', $poolId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Pool created successfully',
				'pool_id' => $poolId
			));

		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/pool => A non-user admin tried to update a pool', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$pool = httpParseInput();
			if ($pool === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/pool => Trying to update a pool without specifying it', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool is required'));
			}
			if (!isset($pool['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/pool => Trying to update a pool without specifying its id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id is required'));
			}

			if (!is_int($pool['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => id must be an integer and not "%s"', $pool['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'id must be an integer'));
			}

			$pool_base = $dbDriver->getPool($pool['id']);

			if ($pool['archiveformat'] === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPool(%s)', $pool['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			}
			if ($pool['archiveformat'] === False)
				httpResponse(400, array('message' => 'id does not exist'));

			if (isset($pool['uuid']))
				httpResponse(400, array('message' => 'uuid cannot be modified'));
			$pool['uuid'] = $pool_base['uuid'];

			if (isset($pool['name'])) {
				$id_pool_by_name = $dbDriver->getPoolByName($pool['name']);
				if ($id_pool_by_name === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolByName(%s)', $pool['name']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				}
				if ($id_pool_by_name !== $pool['id'])
					httpResponse(400, array('message' => 'Specified name already exists in the database and does not belong to the pool you are editing'));
			} else
				$pool['name'] = $pool_base['name'];


			if (isset($pool['mediaformat'])) {
				if (is_int($pool['mediaformat'])) {
					$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']);
					if ($mediaformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormat(%s)', $pool['mediaformat']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					}
					if ($mediaformat === False)
						httpResponse(400, array('message' => 'mediaformat id does not exist'));
				} elseif (is_array ($pool['mediaformat']) and (array_key_exists('id', $pool['mediaformat']) or array_key_exists('name', $pool['mediaformat']))) {
					if (array_key_exists('id', $pool['mediaformat'])) {
						$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']['id']);
						if ($mediaformat === NULL) {
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormat(%s)', $pool['mediaformat']['id']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query Failure'));
						}
						if ($mediaformat === False)
							httpResponse(400, array('message' => 'mediaformat id does not exist'));
						$pool['mediaformat'] = $pool['mediaformat']['id'];
					} else {
						$mediaformat = $dbDriver->getMediaFormatByName($pool['mediaformat']['name']);
						if ($mediaformat === NULL) {
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormatByName(%s)', $pool['mediaformat']['name']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query Failure'));
						}
						if ($mediaformat === False)
							httpResponse(400, array('message' => 'mediaformat name does not exist'));
						$pool['mediaformat'] = $mediaformat;
					}
				} else
					httpResponse(400, array('message' => 'Specified mediaformat is invalid'));

				if ($pool['mediaformat'] !== $pool_base['mediaformat']) {
					$params = array();
					$nbMedias = $dbDriver->getMediasByPool($pool['id'], $params);
					if ($nbMedias === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediasByPool(%s)', $pool), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					}
					if ($nbMedias['total_rows'] !== 0)
						httpResponse(400, array('message' => 'mediaformat cannot be modified if specified pool contains medias'));
				}
			} else
				$pool['mediaformat'] = $pool_base['mediaformat']['id'];



			if (isset($pool['archiveformat'])) {
				if (is_int($pool['archiveformat'])) {
				} elseif (is_array($pool['archiveformat']) and (array_key_exists('id', $pool['archiveformat']) or array_key_exists('name', $pool['archiveformat']))) {
					if (array_key_exists('id', $pool['archiveformat'])) {
						$pool['archiveformat'] = $pool['archiveformat']['id'];
					} else {
						$archiveformat = $dbDriver->getArchiveFormatByName($pool['archiveformat']['name']);
						if ($archiveformat === NULL) {
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchiveFormatByName(%s)', $pool['archiveformat']['name']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query Failure'));
						}
						if ($archiveformat === False)
							httpResponse(400, array('message' => 'archiveformat name does not exist'));
						$pool['archiveformat'] = $archiveformat;
					}
				} else
					httpResponse(400, array('message' => 'Specified archiveformat is invalid'));

				$archiveformat = $dbDriver->getArchiveFormat($pool['archiveformat']);
				if ($archiveformat === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getArchiveFormat(%s)', $pool['archiveformat']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				}
				if ($archiveformat === False)
					httpResponse(400, array('message' => 'archiveformat id does not exist'));

				if ($pool['archiveformat'] != $pool_base['archiveformat']) {
					if ($archiveformat['name'] === 'LTFS') {
						$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']);
						if ($mediaformat === NULL) {
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormat(%s)', $pool['mediaformat']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query Failure'));
						}
						if ($mediaformat['mode'] !== 'linear' or $mediaformat['supportpartition'] !== True)
							httpResponse(400, array('message' => 'mediaformat must be in linear mode and must support partition'));
					}

					$params = array();
					$nbMedias = $dbDriver->getMediasByPool($pool['id'], $params);
					if ($nbMedias === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediasByPool(%s)', $pool), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					}
					if ($nbMedias['total_rows'] !== 0)
						httpResponse(400, array('message' => 'archiveformat cannot be modified if specified pool contains medias'));
				}
			} else
				$pool['archiveformat'] = $pool_base['archiveformat']['id'];


			$autocheckmode = array('quick mode', 'thorough mode', 'none');
			if (!isset($pool['autocheck']))
				$pool['autocheck'] = $pool_base['autocheck'];
			elseif (!is_string ($pool['autocheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => autocheck must be a string and not "%s"', $pool['autocheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'autocheckmode must be a string'));
			}
			elseif (array_search($pool['autocheck'], $autocheckmode) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $autocheckmode));
				httpResponse(400, array('message' => 'autocheckmode value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pool['lockcheck']))
				$pool['lockcheck'] = $pool_base['lockcheck'];
			elseif (!is_bool($pool['lockcheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => lockcheck must be a boolean and not "%s"', $pool['lockcheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'lockcheck must be a boolean'));
			}

			if (!isset($pool['growable']))
				$pool['growable'] = $pool_base['growable'];
			elseif (!is_bool($pool['growable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => growable must be a boolean and not "%s"', $pool['growable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'growable must be a boolean'));
			}

			$unbreakablelevel = array('archive', 'file', 'none');
			if (!isset($pool['unbreakablelevel']))
				$pool['unbreakablelevel'] = $pool_base['unbreakablelevel'];
			elseif (!is_string ($pool['unbreakablelevel'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => unbreakablelevel must be a string and not "%s"', $pool['unbreakablelevel']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'unbreakablelevel must be a string'));
			}
			elseif (array_search($pool['unbreakablelevel'], $unbreakablelevel) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $unbreakablelevel));
				httpResponse(400, array('message' => 'unbreakablelevel value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pool['rewritable']))
				$pool['rewritable'] = $pool_base['rewritable'];
			elseif (!is_bool($pool['rewritable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => rewritable must be a boolean and not "%s"', $pool['rewritable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'rewritable must be a boolean'));
			}

			if (!isset($pool['metadata']))
				$pool['metadata'] = $pool_base['metadata'];

			if (!isset($pool['backuppool']))
				$pool['backuppool'] = $pool_base['backuppool'];
			elseif (!is_bool($pool['backuppool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => backuppool must be a boolean and not "%s"', $pool['backuppool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'backuppool must be a boolean'));
			}

			if (!isset($pool['poolmirror']))
				$pool['poolmirror'] = $pool_base['poolmirror'];
			elseif (!is_int($pool['poolmirror'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool => poolmirror must be an integer and not "%s"', $pool['poolmirror']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror must be an integer'));
			}

			$pool['deleted'] = $pool_base['deleted'];

			error_log('update pool: ' . json_encode($pool));


			$result = $dbDriver->updatePool($pool);

			if ($result) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('Pool %s updated', $pool['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool updated successfully'));
			}
			else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pool => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updatePool(%s)', $pool), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			break;


		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>