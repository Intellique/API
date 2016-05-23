<?php
/**
 * \addtogroup vtl
 * \section VTL_deletion VTL deletion
 * To delete a VTL,
 * use \b DELETE method : <i>with VTL id</i>
 * \section VTL-creation VTL creation
 * To create a pool,
 * use \b POST method <i>with VTL parameters (uuid, path, prefix, mediaformat, nbdrives, nbslots, deleted) </i>
 * \section VTL-update VTL update
 * To update a VTL,
 * use \b PUT method <i>with VTL parameters (id, path, prefix, mediaformat, nbdrives, nbslots, deleted) </i>
 * \verbatim path : /storiqone-backend/api/v1/vtl/ \endverbatim
 *        Name          |         Type             |                     Value
 * | :----------------: | :---------------------:  | :---------------------------------------------------------:
 * |  id                |  integer                 | non NULL Default value, nextval('vtl_id_seq'::regclass)
 * |  uuid              |  uuid                    | non NULL Automatically generated if missing
 * |  path              |  character varying(255)  | non NULL
 * |  prefix            |  character varying(255)  | non NULL
 * |  nbslots           |  integer                 | non NULL
 * |  nbdrives          |  integer                 | non NULL
 * |  mediaformat       |  integer                 | non NULL
 * |  deleted           |  boolean                 | non NULL Default value, false
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 VTL not found
 *   - \b 500 Query failure
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("uuid.php");
	require_once("dbArchive.php");


	switch ($_SERVER['REQUEST_METHOD']) {

		case 'GET':
			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id']))
					httpResponse(400, array('message' => 'id must be an integer'));
				$vtl = $dbDriver->getVTL($_GET['id']);
				if ($vtl === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/vtl => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl => getVTL(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}
				elseif ($vtl === false)
					httpResponse(404, array('message' => 'VTL not found'));
				httpResponse(200, array(
							'message' => 'Query succeeded',
							'vtl' => $vtl
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

				$vtl = $dbDriver->getVTLs($params);
				if ($result['query_executed'] === false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/vtl => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getVTLs(%s)', $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'vtls' => array(),
						'query' => $vtl['query'],
						'query_executed' => $vtl['query_executed'],
						'total_rows' => $vtl['total_rows']
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'vtls' => $vtl['rows'],
						'total_rows' => $vtl['total_rows']
					));
			}
			break;

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/vtl => A non-admin user tried to create a VTL', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$vtl = httpParseInput();
			if ($vtl === null)
				httpResponse(400, array('message' => 'VTL information is required'));

			$ok = (bool) $vtl;
			$failed = false;

			// path
			if ($ok)
				$ok = isset($vtl['path']) && is_string($vtl['path']);

			// prefix
			if ($ok)
				$ok = isset($vtl['prefix']) && is_string($vtl['prefix']);

			// host
			if ($ok) {
				$host = $dbDriver->getHost(posix_uname()['nodename']);
				if ($host === null || $host === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/vtl => getHost(%s)', posix_uname()['nodename']), $_SESSION['user']['id']);
					$failed = true;
				} else
					$vtl['host'] = $host;
			}

			// mediaformat
			if ($ok)
				$ok = isset($vtl['mediaformat']) && is_int($vtl['mediaformat']);
			if ($ok) {
				$exists = $dbDriver->getMediaFormat($vtl['mediaformat']);
				if ($exists === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/vtl => getMediaFormat(%s)', $vtl['mediaformat']), $_SESSION['user']['id']);
					$failed = true;
				}
				if ($exists === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/vtl => mediaformat %s does not exists', $vtl['mediaformat']), $_SESSION['user']['id']);
					$ok = false;
				}
			}

			// uuid
			if ($ok) {
				if (isset($vtl['uuid'])) {
					if (!is_string($vtl['uuid'])) {
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, 'POST api/v1/vtl => uuid must be a string', $_SESSION['user']['id']);
						httpResponse(400, array('message' => 'uuid must be a string'));
					}

					if (!uuid_is_valid($vtl['uuid']))
						httpResponse(400, array('message' => 'uuid is not valid'));
				} else
					$vtl['uuid'] = uuid_generate();
			}

			// nbdrives
			if ($ok)
				$ok = isset($vtl['nbdrives']) && is_int($vtl['nbdrives']);

			// nbslots
			if ($ok)
				$ok = isset($vtl['nbslots']) && is_int($vtl['nbslots']);

			// deleted
			if (isset($vtl['deleted'])) {
				if ($ok)
					$ok = is_bool($vtl['deleted']);
			}

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/vtl => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$result = $dbDriver->createVTL($vtl);

			if ($result) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/vtl => VTL %s created', $result), $_SESSION['user']['id']);
				httpResponse(200, array(
					'message' => 'VTL created successfully',
					'vtl_id' => $result
				));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/vtl => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/vtl => createUser(%s)', $user), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			break;

		case 'PUT':
			checkConnected();

			$vtl = httpParseInput();

			if (!isset($vtl))
				httpResponse(400, array('message' => 'VTL information is required'));

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/vtl => A non-admin user tried to update a VTL', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$ok = (bool) $vtl;
			$failed = false;

			// id
			if ($ok)
				$ok = isset($vtl['id']) && is_int($vtl['id']);
			if ($ok) {
				$vtl_base = $dbDriver->getVTL($vtl['id']);
				if ($vtl_base === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl => getVTL(%s)', $vtl['id']), $_SESSION['user']['id']);
					$failed = true;
				}
				elseif ($vtl_base === false)
					httpResponse(404, array('message' => 'VTL not found'));
			}

			// path
			if ($ok)
				$ok = isset($vtl['path']) && is_string($vtl['path']);

			// prefix
			if ($ok)
				$ok = isset($vtl['prefix']) && is_string($vtl['prefix']);

			// host
			if ($ok) {
				$host = $dbDriver->getHost(posix_uname()['nodename']);
				if ($host === null || $host === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl => getHost(%s)', posix_uname()['nodename']), $_SESSION['user']['id']);
					$failed = true;
				} else
					$vtl['host'] = $host;
			}

			// mediaformat
			if ($ok)
				$ok = isset($vtl['mediaformat']) && is_int($vtl['mediaformat']);
			if ($ok) {
				$exists = $dbDriver->getMediaFormat($vtl['mediaformat']);
				if ($exists === null) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl => getMediaFormat(%s)', $vtl['mediaformat']), $_SESSION['user']['id']);
					$failed = true;
				}
				if ($exists === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl => mediaformat %s does not exists', $vtl['mediaformat']), $_SESSION['user']['id']);
					$ok = false;
				}
			}

			// uuid
			if ($ok) {
				if (isset($vtl['uuid']))
					httpResponse(400, array('message' => 'uuid cannot be modified'));
				$vtl['uuid'] = $vtl_base['uuid'];
			}

			// nbdrives
			if ($ok)
				$ok = isset($vtl['nbdrives']) && is_int($vtl['nbdrives']);

			// nbslots
			if ($ok)
				$ok = isset($vtl['nbslots']) && is_int($vtl['nbslots']);

			// deleted
			if ($ok)
				$ok = isset($vtl['deleted']) && is_bool($vtl['deleted']);

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/vtl => Query failure', $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$result = $dbDriver->updateVTL($vtl);

			if ($result) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('PUT api/v1/vtl => VTL %s updated', $vtl['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'VTL updated successfully',
						'vtl_id' => $vtl['id']));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/vtl => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl => updateVTL(%s)', $vtl['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			break;

		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'DELETE api/v1/vtl => A non-admin user tried to delete a VTL', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (!isset($_GET['id']))
				httpResponse(400, array('message' => 'VTL id is required'));

			if (!is_numeric($_GET['id']))
				httpResponse(400, array('message' => 'VTL id must be an integer'));

			$exists = $dbDriver->getVTL($_GET['id']);
			if ($exists === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/vtl => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/vtl => getVTL(%s)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			elseif ($exists === false)
				httpResponse(404, array('message' => 'VTL not found'));

			$deleted = $dbDriver->deleteVTL($_GET['id']);
			if ($deleted === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/vtl => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/vtl => deleteVTL(%s)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($deleted === false)
				httpResponse(404, array('message' => 'VTL not found'));

			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/vtl => User %s deleted VTL %s', $_SESSION['user']['login'], $_GET['id']), $_SESSION['user']['id']);
			httpResponse(200, array('message' => 'VTL deleted successfully'));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_ALL_METHODS);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>