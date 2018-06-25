<?php
/**
 * \addtogroup pool
 * \page pool
 * \section Pool_ID Pool information
 * To get pool by its id
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/pool/ \endverbatim
 * \param id : pool id
 * \li \c deleted (string) : indicator to show either no deleted archives, all deleted archives, or only deleted archives
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
 * use \b POST method <i>with pool parameters (uuid, name, archiveformat, mediaformat, \ref PoolScripts)</i>
 * \section Pool-creation_Pooltemplate Pool creation using a pool template
 * To create a pool based on a pool template,
 * use \b POST method <i>with the id of the pool template to use to create the pool, \b plus pool parameters (uuid, name, archiveformat, mediaformat, \ref PoolScripts)</i>
 * \section Pool-update Pool update
 * To update a pool,
 * use \b PUT method <i>with pool parameters (id, uuid, name, archiveformat, mediaformat, \ref PoolScripts) </i>
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
 * |  scripts           |  array of script         |                           _
 * |  deleted           |  boolean                 | non NULL Default value, false
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 *
 * \section PoolScripts Pool Scripts
 * |        Name        |         Type             |                     Value
 * | :----------------: | :---------------------:  | :---------------------------------------------------------:
 * |  sequence          |  integer                 | non NULL
 * |  jobtype           |  integer                 | non NULL
 * |  script            |  integer                 |                           _
 * |  scripttype        |  scripttype              | non NULL
 *
 *
 * <b>Parameters</b>
 *
 * |   Name      |  Type   |                                  Description                                        |           Constraint            |
 * | :---------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | sequence    | integer | sequence number of the script                                                       | sequence >= 0                   |
 * | jobtype     | integer | jobtype of the script                                                               |                                 |
 * | script      | integer | script number of the script                                                         |                                 |
 * | scripttype  | integer | type of the script (one of "on error", "pre job" or "post job")                     |                                 |
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("uuid.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('DELETE api/v1/pool (%d) => A non-admin user tried to delete a pool', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (!isset($_GET['id']))
				httpResponse(400, array('message' => 'Pool ID required'));
			elseif (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/pool (%d) => id must be an integer and not "%s"', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool ID must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('DELETE api/v1/pool (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$pool = $dbDriver->getPool($_GET['id'], DB::DB_ROW_LOCK_UPDATE);
			if (!$pool)
				$dbDriver->cancelTransaction();
			if ($pool === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/pool (%d) => getPool(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($pool === false)
				httpResponse(404, array('message' => 'Pool not found'));

			$pool['deleted'] = true;

			$result = $dbDriver->updatePool($pool);
			if ($result === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/pool (%d) => updatePool(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($result === false) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'Pool not found'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/pool (%d) => Pool %s deleted', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool deleted'));
			}

			break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pool (%d) => id must be an integer and not "%s"', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Pool ID must be an integer'));
				}

				if (!$_SESSION['user']['isadmin']) {
					$permission_granted = $dbDriver->checkPoolPermission($_GET['id'], $_SESSION['user']['id']);
					if ($permission_granted === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pool (%d) => checkPoolPermission(%s, %s)', __LINE__, $_GET['id'], $_SESSION['user']['id']), $_SESSION['user']['id']);
						httpResponse(500, array(
							'message' => 'Query failure',
							'pool' => array()
						));
					} elseif ($permission_granted === false)
						httpResponse(403, array('message' => 'Permission denied'));
				}

				$pool = $dbDriver->getPool($_GET['id']);
				if ($pool === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pool (%d) => getPool(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				} elseif ($pool === false)
					httpResponse(404, array(
						'message' => 'Pool not found',
						'pool' => array()
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'pool' => $pool
				));
			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['deleted'])) {
					if ($_SESSION['user']['isadmin']) {
						if (false !== array_search($_GET['deleted'], array('yes', 'no', 'only')))
							$params['deleted'] = $_GET['deleted'];
						else
							$ok = false;
					} else
						httpResponse(403, array('message' => 'Permission denied'));
				} else
					$params['deleted'] = 'no';

				if (isset($_GET['limit'])) {
					$limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 1)));
					if ($limit !== false)
						$params['limit'] = $limit;
					else
						$ok = false;
				}
				if (isset($_GET['offset'])) {
					$offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 0)));
					if ($offset !== false)
						$params['offset'] = $offset;
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$result = $dbDriver->getPoolsByPoolgroup($_SESSION['user']['poolgroup'], $params);
				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pool (%d) => getPoolsByPoolgroup(%s, %s)', __LINE__, $_SESSION['user']['poolgroup'], $params), $_SESSION['user']['id']);
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

			break;

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/pool (%d) => A non-admin user tried to create a pool', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$pool = httpParseInput();

			if (isset($pool['uuid'])) {
				if (!is_string($pool['uuid'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => uuid must be a string and not "%s"', __LINE__, $pool['uuid']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'uuid must be a string'));
				} elseif (!uuid_is_valid($pool['uuid']))
					httpResponse(400, array('message' => 'uuid is not valid'));
			} else
				$pool['uuid'] = uuid_generate();

			if (!isset($pool['name'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/pool (%d) => Trying to create a pool without specifying pool name', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pool name is required'));
			}

			if (!isset($pool['archiveformat'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/pool (%d) => Trying to create a pool without specifying archiveformat', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'archiveformat is required'));
			}

			if (!isset($pool['mediaformat'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/pool (%d) => Trying to create a pool without specifying mediaformat', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'mediaformat is required'));
			}

			if (is_integer($pool['archiveformat'])) {
				$archiveformat = $dbDriver->getArchiveFormat($pool['archiveformat']);
				if ($archiveformat === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => getArchiveFormat(%s)', __LINE__, $pool['archiveformat']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				} elseif ($archiveformat === False)
					httpResponse(400, array('message' => 'archiveformat id does not exist'));
			} elseif (is_array($pool['archiveformat']) and (array_key_exists('id', $pool['archiveformat']) or array_key_exists('name', $pool['archiveformat']))) {
				if (array_key_exists('id', $pool['archiveformat'])) {
					$archiveformat = $dbDriver->getArchiveFormat($pool['archiveformat']['id']);
					if ($archiveformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => getArchiveFormat(%s)', __LINE__, $pool['archiveformat']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					} elseif ($archiveformat === False)
						httpResponse(400, array('message' => 'archiveformat id does not exist'));
					$pool['archiveformat'] = $pool['archiveformat']['id'];
				} else {
					$archiveformat = $dbDriver->getArchiveFormatByName($pool['archiveformat']['name']);
					if ($archiveformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => getArchiveFormatByName(%s)', __LINE__, $pool['archiveformat']['name']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					} elseif ($archiveformat === False)
						httpResponse(400, array('message' => 'archiveformat name does not exist'));
					$pool['archiveformat'] = $archiveformat;
				}
			} else
				httpResponse(400, array('message' => 'Specified archiveformat is invalid'));

			if (is_integer($pool['mediaformat'])) {
				$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']);
				if ($mediaformat === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => getMediaFormat(%s)', __LINE__, $pool['mediaformat']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				} elseif ($mediaformat === False)
					httpResponse(400, array('message' => 'mediaformat id does not exist'));
			} elseif (is_array($pool['mediaformat']) and (array_key_exists('id', $pool['mediaformat']) or array_key_exists('name', $pool['mediaformat']))) {
				if (array_key_exists('id', $pool['mediaformat'])) {
					$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']['id']);
					if ($mediaformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => getMediaFormat(%s)', __LINE__, $pool['mediaformat']['id']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					} elseif ($mediaformat === False)
						httpResponse(400, array('message' => 'mediaformat id does not exist'));
					$pool['mediaformat'] = $pool['mediaformat']['id'];
				} else {
					$mediaformat = $dbDriver->getMediaFormatByName($pool['mediaformat']['name']);
					if ($mediaformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => getMediaFormatByName(%s)', __LINE__, $pool['mediaformat']['name']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					} elseif ($mediaformat === False)
						httpResponse(400, array('message' => 'mediaformat name does not exist'));
					$pool['mediaformat'] = $mediaformat;
				}
			} else
				httpResponse(400, array('message' => 'Specified mediaformat is invalid'));

			//pooltemplate
			if (isset($pool['pooltemplate'])) {
				if (!is_integer($pool['pooltemplate']))
					httpResponse(400, array('message' => 'pool template id must be an integer'));

				$pooltemplate = $dbDriver->getPoolTemplate($pool['pooltemplate']);
				if ($pooltemplate === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => getPoolTemplate(%s)', __LINE__, $pool['pooltemplate'], $_SESSION['user']['id']));
					httpResponse(500, array('message' => 'Query Failure'));
				} elseif ($pooltemplate === False)
					httpResponse(404, array('message' => 'This pool template does not exist'));

				$pool['autocheck'] = $pooltemplate['autocheck'];
				$pool['lockcheck'] = $pooltemplate['lockcheck'];
				$pool['growable'] = $pooltemplate['growable'];
				$pool['unbreakablelevel'] = $pooltemplate['unbreakablelevel'];
				$pool['rewritable'] = $pooltemplate['rewritable'];
			} else {
				$autocheckmode = array('quick mode', 'thorough mode', 'none');
				if (!isset($pool['autocheck']))
					$pool['autocheck'] = 'none';
				elseif (!is_string($pool['autocheck'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => autocheckmode must be a string and not "%s"', __LINE__, $pool['autocheck']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'autocheckmode must be a string'));
				} elseif (array_search($pool['autocheck'], $autocheckmode) === false) {
					$string_mode = join(', ', array_map(function($value) {
						return '"' . $value . '"';
					}, $autocheckmode));
					httpResponse(400, array('message' => 'autocheckmode value is invalid. It should be in '.$string_mode));
				}

				if (!isset($pool['lockcheck']))
					$pool['lockcheck'] = False;
				elseif (!is_bool($pool['lockcheck'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => lockcheck must be a boolean and not "%s"', __LINE__, $pool['lockcheck']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'lockcheck must be a boolean'));
				}

				if (!isset($pool['growable']))
					$pool['growable'] = False;
				elseif (!is_bool($pool['growable'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => growable must be a boolean and not "%s"', __LINE__, $pool['growable']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'growable must be a boolean'));
				}

				$unbreakablelevel = array('archive', 'file', 'none');
				if (!isset($pool['unbreakablelevel']))
					$pool['unbreakablelevel'] = 'none';
				elseif (!is_string($pool['unbreakablelevel'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => unbreakablelevel must be a string and not "%s"', __LINE__, $pool['unbreakablelevel']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'unbreakablelevel must be a string'));
				} elseif (array_search($pool['unbreakablelevel'], $unbreakablelevel) === false) {
					$string_mode = join(', ', array_map(function($value) {
						return '"' . $value . '"';
					}, $unbreakablelevel));
					httpResponse(400, array('message' => 'unbreakablelevel value is invalid. It should be in ' . $string_mode));
				}

				if (!isset($pool['rewritable']))
					$pool['rewritable'] = true;
				elseif (!is_bool($pool['rewritable'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => rewritable must be a boolean and not "%s"', __LINE__, $pool['rewritable']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'rewritable must be a boolean'));
				}
			}

			if (!isset($pool['metadata']))
				$pool['metadata'] = array();

			if (!isset($pool['backuppool']))
				$pool['backuppool'] = False;
			elseif (!is_bool($pool['backuppool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => backuppool must be a boolean and not "%s"', __LINE__, $pool['backuppool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'backuppool must be a boolean'));
			}

			if (!isset($pool['poolmirror']))
				$pool['poolmirror'] = NULL;
			elseif (!is_integer($pool['poolmirror'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => poolmirror must be an integer and not "%s"', __LINE__, $pool['poolmirror']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror must be an integer'));
			}

			if (!isset($pool['scripts']))
				$pool['scripts'] = array();
			elseif (!is_array($pool['scripts']))
				httpResponse(400, array('message' => 'Specified script is invalid'));
			else {
				foreach ($pool['scripts'] as $script) {
					if (!isset($script['scripttype']))
						httpResponse(400, array('message' => 'Scripttype is required'));
					elseif (!in_array($script['scripttype'], array('on error', 'pre job', 'post job')))
						httpResponse(400, array('message', 'Invalid script type'));
					elseif (!is_int($script['sequence']) || $script['sequence'] < 0)
						httpResponse(400, array('message' => 'Sequence must be an integer and superior or equal to zero'));
					elseif (!isset($script['jobtype']))
						httpResponse(400, array('message' => 'Jobtype is required'));
					elseif (!isset($script['script']))
						httpResponse(400, array('message' => 'Script is required'));

					$jobtype = $dbDriver->getJobTypeId($script['jobtype']);
					if ($jobtype === NULL)
						httpResponse(500, array(
							'message' => 'Query Failure',
							'pool' => array()));
					elseif ($jobtype === false)
						httpResponse(404, array('message' => 'jobtype not found'));

					$script = $dbDriver->getScripts($script['script']);
					if ($script === NULL) {
						httpResponse(500, array(
							'message' => 'Query Failure',
							'pool' => array()));
					} elseif ($script === False)
						httpResponse(400, array('message' => 'This pool script id does not exist'));
				}
			}

			$poolId = $dbDriver->createPool($pool);
			if ($poolId === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => createPool(%s)', __LINE__, var_export($pool, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			}

			httpAddLocation('/pool/?id='.$poolId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/pool (%d) => Pool %s created', __LINE__, $poolId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Pool created successfully',
				'pool_id' => $poolId
			));

			break;

		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/pool (%d) => A non-user admin tried to update a pool', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$pool = httpParseInput();
			if ($pool === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/pool (%d) => Trying to update a pool without specifying it', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool is required'));
			}

			if (!isset($pool['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/pool (%d) => Trying to update a pool without specifying its id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Pool id is required'));
			} elseif (!is_integer($pool['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pool (%d) => id must be an integer and not "%s"', __LINE__, $pool['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'id must be an integer'));
			}

			$pool_base = $dbDriver->getPool($pool['id']);
			if ($pool_base === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getPool(%s)', __LINE__, $pool['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			} elseif ($pool_base === False)
				httpResponse(400, array('message' => 'id does not exist'));

			if (isset($pool['uuid']))
				httpResponse(400, array('message' => 'uuid cannot be modified'));
			$pool['uuid'] = $pool_base['uuid'];

			if (isset($pool['name'])) {
				$id_pool_by_name = $dbDriver->getPoolByName($pool['name']);
				if ($id_pool_by_name === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getPoolByName(%s)', __LINE__, $pool['name']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				} elseif ($id_pool_by_name !== $pool['id'])
					httpResponse(400, array('message' => 'Specified name already exists in the database and does not belong to the pool you are editing'));
			} else
				$pool['name'] = $pool_base['name'];

			if (isset($pool['mediaformat'])) {
				if (is_integer($pool['mediaformat'])) {
					$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']);
					if ($mediaformat === NULL) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getMediaFormat(%s)', __LINE__, $pool['mediaformat']), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					} elseif ($mediaformat === False)
						httpResponse(400, array('message' => 'mediaformat id does not exist'));
				} elseif (is_array($pool['mediaformat']) and (array_key_exists('id', $pool['mediaformat']) or array_key_exists('name', $pool['mediaformat']))) {
					if (array_key_exists('id', $pool['mediaformat'])) {
						$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']['id']);
						if ($mediaformat === NULL) {
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getMediaFormat(%s)', __LINE__, $pool['mediaformat']['id']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query Failure'));
						} elseif ($mediaformat === False)
							httpResponse(400, array('message' => 'mediaformat id does not exist'));
						$pool['mediaformat'] = $pool['mediaformat']['id'];
					} else {
						$mediaformat = $dbDriver->getMediaFormatByName($pool['mediaformat']['name']);
						if ($mediaformat === NULL) {
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getMediaFormatByName(%s)', __LINE__, $pool['mediaformat']['name']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query Failure'));
						} elseif ($mediaformat === False)
							httpResponse(400, array('message' => 'mediaformat name does not exist'));
						$pool['mediaformat'] = $mediaformat;
					}
				} else
					httpResponse(400, array('message' => 'Specified mediaformat is invalid'));

				if ($pool['mediaformat'] !== $pool_base['mediaformat']['id']) {
					$params = array();
					$nbMedias = $dbDriver->getMediasByPool($pool['id'], $params);
					if ($nbMedias === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getMediasByPool(%s)', $pool), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					} elseif ($nbMedias['total_rows'] !== 0)
						httpResponse(400, array('message' => 'mediaformat cannot be modified if specified pool contains medias', 'pool user' => $pool, 'pool db' => $pool_base));
				}
			} else
				$pool['mediaformat'] = $pool_base['mediaformat']['id'];

			if (isset($pool['archiveformat'])) {
				if (is_integer($pool['archiveformat'])) {
				} elseif (is_array($pool['archiveformat']) and (array_key_exists('id', $pool['archiveformat']) or array_key_exists('name', $pool['archiveformat']))) {
					if (array_key_exists('id', $pool['archiveformat'])) {
						$pool['archiveformat'] = $pool['archiveformat']['id'];
					} else {
						$archiveformat = $dbDriver->getArchiveFormatByName($pool['archiveformat']['name']);
						if ($archiveformat === NULL) {
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getArchiveFormatByName(%s)', __LINE__, $pool['archiveformat']['name']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query Failure'));
						} elseif ($archiveformat === False)
							httpResponse(400, array('message' => 'archiveformat name does not exist'));
						$pool['archiveformat'] = $archiveformat;
					}
				} else
					httpResponse(400, array('message' => 'Specified archiveformat is invalid'));

				$archiveformat = $dbDriver->getArchiveFormat($pool['archiveformat']);
				if ($archiveformat === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getArchiveFormat(%s)', __LINE__, $pool['archiveformat']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				} elseif ($archiveformat === False)
					httpResponse(400, array('message' => 'archiveformat id does not exist'));

				if ($pool['archiveformat'] !== $pool_base['archiveformat']['id']) {
					if ($archiveformat['name'] === 'LTFS') {
						$mediaformat = $dbDriver->getMediaFormat($pool['mediaformat']);
						if ($mediaformat === NULL) {
							$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getMediaFormat(%s)', __LINE__, $pool['mediaformat']), $_SESSION['user']['id']);
							httpResponse(500, array('message' => 'Query Failure'));
						} elseif ($mediaformat['mode'] !== 'linear' or $mediaformat['supportpartition'] !== True)
							httpResponse(400, array('message' => 'mediaformat must be in linear mode and must support partition'));
					}

					$params = array();
					$nbMedias = $dbDriver->getMediasByPool($pool['id'], $params);
					if ($nbMedias === null) {
						$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => getMediasByPool(%s)', __LINE__, $pool), $_SESSION['user']['id']);
						httpResponse(500, array('message' => 'Query Failure'));
					} elseif ($nbMedias['total_rows'] !== 0)
						httpResponse(400, array('message' => 'archiveformat cannot be modified if specified pool contains medias'));
				}
			} else
				$pool['archiveformat'] = $pool_base['archiveformat']['id'];

			$autocheckmode = array('quick mode', 'thorough mode', 'none');
			if (!isset($pool['autocheck']))
				$pool['autocheck'] = $pool_base['autocheck'];
			elseif (!is_string($pool['autocheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => autocheck must be a string and not "%s"', __LINE__, $pool['autocheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'autocheckmode must be a string'));
			} elseif (array_search($pool['autocheck'], $autocheckmode) === false) {
				$string_mode = join(', ', array_map(function($value) {
					return '"' . $value . '"';
				}, $autocheckmode));
				httpResponse(400, array('message' => 'autocheckmode value is invalid. It should be in '.$string_mode));
			}

			if (!isset($pool['lockcheck']))
				$pool['lockcheck'] = $pool_base['lockcheck'];
			elseif (!is_bool($pool['lockcheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => lockcheck must be a boolean and not "%s"', __LINE__, $pool['lockcheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'lockcheck must be a boolean'));
			}

			if (!isset($pool['growable']))
				$pool['growable'] = $pool_base['growable'];
			elseif (!is_bool($pool['growable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => growable must be a boolean and not "%s"', __LINE__, $pool['growable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'growable must be a boolean'));
			}

			$unbreakablelevel = array('archive', 'file', 'none');
			if (!isset($pool['unbreakablelevel']))
				$pool['unbreakablelevel'] = $pool_base['unbreakablelevel'];
			elseif (!is_string($pool['unbreakablelevel'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => unbreakablelevel must be a string and not "%s"', __LINE__, $pool['unbreakablelevel']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'unbreakablelevel must be a string'));
			} elseif (array_search($pool['unbreakablelevel'], $unbreakablelevel) === false) {
				$string_mode = join(', ', array_map(function($value) {
					return '"' . $value . '"';
				}, $unbreakablelevel));
				httpResponse(400, array('message' => 'unbreakablelevel value is invalid. It should be in '.$string_mode));
			}

			if (!isset($pool['rewritable']))
				$pool['rewritable'] = $pool_base['rewritable'];
			elseif (!is_bool($pool['rewritable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => rewritable must be a boolean and not "%s"', __LINE__, $pool['rewritable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'rewritable must be a boolean'));
			}

			if (!isset($pool['metadata']))
				$pool['metadata'] = $pool_base['metadata'];

			if (!isset($pool['backuppool']))
				$pool['backuppool'] = $pool_base['backuppool'];
			elseif (!is_bool($pool['backuppool'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => backuppool must be a boolean and not "%s"', __LINE__, $pool['backuppool']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'backuppool must be a boolean'));
			}

			if (!isset($pool['poolmirror']))
				$pool['poolmirror'] = $pool_base['poolmirror'];
			elseif (!is_int($pool['poolmirror'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => poolmirror must be an integer and not "%s"', __LINE__, $pool['poolmirror']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror must be an integer'));
			}

			if (!isset($pool['scripts']))
				$pool['scripts'] = $pool_base['scripts'];
			elseif (!is_array($pool['scripts']))
				httpResponse(400, array('message' => 'Specified script is invalid'));
			else {
				foreach ($pool['scripts'] as $script) {
					if (!isset($script['scripttype']))
						httpResponse(400, array('message' => 'Scripttype is required'));
					elseif (!in_array($script['scripttype'], array('on error', 'pre job', 'post job')))
						httpResponse(400, array('message', 'Invalid script type'));
					elseif (!is_int($script['sequence']) || $script['sequence'] < 0)
						httpResponse(400, array('message' => 'Sequence must be an integer and superior or equal to zero'));
					elseif (!isset($script['jobtype']))
						httpResponse(400, array('message' => 'Jobtype is required'));
					elseif (!isset($script['script']))
						httpResponse(400, array('message' => 'Script is required'));

					$jobtype = $dbDriver->getJobTypeId($script['jobtype']);
					if ($jobtype === NULL)
						httpResponse(500, array(
							'message' => 'Query Failure',
							'pool' => array()));
					elseif ($jobtype === false)
						httpResponse(404, array('message' => 'jobtype not found'));

					$script = $dbDriver->getScripts($script['script']);
					if ($script === NULL) {
						httpResponse(500, array(
							'message' => 'Query Failure',
							'pool' => array()));
					} elseif ($script === False)
						httpResponse(400, array('message' => 'This pool script id does not exist'));
				}
			}

			$pool['deleted'] = $pool_base['deleted'];

			$result = $dbDriver->updatePool($pool);
			if ($result) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('Pool %s updated', $pool['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool updated successfully'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pool (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pool (%d) => updatePool(%s)', __LINE__, $pool), $_SESSION['user']['id']);
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
