<?php
/**
 * \addtogroup vtl
 * \section VTL VTL information
 * To get a VTL by its id
 * use \b GET method : <i>with VTL id</i>
 * \section VTL_id_list VTLs ids
 * To get VTL ids list
 * use \b GET method : <i>without reference to specific id or ids</i>
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
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('DELETE api/v1/vtl (%d) => A non-admin user tried to delete a VTL', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (!isset($_GET['id']))
				httpResponse(400, array('message' => 'VTL id is required'));

			if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
				httpResponse(400, array('message' => 'VTL id must be an integer'));

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('DELETE api/v1/vtl (%d) => Failed to finish transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$exists = $dbDriver->getVTL($_GET['id'], DB::DB_ROW_LOCK_UPDATE);
			if ($exists === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/vtl (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/vtl (%d) => getVTL(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($exists === false)
				httpResponse(404, array('message' => 'VTL not found'));

			$deleted = $dbDriver->deleteVTL($_GET['id']);
			if (!$deleted)
				$dbDriver->cancelTransaction();
			if ($deleted === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/vtl (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/vtl (%d) => deleteVTL(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} else if ($deleted === false)
				httpResponse(404, array('message' => 'VTL not found'));
			elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/vtl (%d) => User %s deleted VTL %s', __LINE__, $_SESSION['user']['login'], $_GET['id']), $_SESSION['user']['id']);
			httpResponse(200, array('message' => 'VTL deleted successfully'));

			break;

		case 'GET':
			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
					httpResponse(400, array('message' => 'id must be an integer'));

				$vtl = $dbDriver->getVTL($_GET['id']);
				if ($vtl === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/vtl (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl (%d) => getVTL(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($vtl === false)
					httpResponse(404, array('message' => 'VTL not found'));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'vtl' => $vtl
				));
			} else {
				$params = array();
				$ok = true;

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

				$vtl = $dbDriver->getVTLs($params);
				if ($vtl === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/vtl (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/vtl (%d) => getVTLs(%s)', __LINE__, $params), $_SESSION['user']['id']);

					httpResponse(500, array(
						'message' => 'Query failure',
						'vtls' => array(),
						'total_rows' => 0
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'vtls' => $vtl,
						'total_rows' => count($vtl)
					));
			}

			break;

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/vtl (%d) => A non-admin user tried to create a VTL', __LINE__), $_SESSION['user']['id']);
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
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/vtl (%d) => getHost(%s)', __LINE__, posix_uname()['nodename']), $_SESSION['user']['id']);
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
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/vtl (%d) => getMediaFormat(%s)', __LINE__, $vtl['mediaformat']), $_SESSION['user']['id']);
					$failed = true;
				}
				if ($exists === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/vtl (%d) => mediaformat %s does not exists', __LINE__, $vtl['mediaformat']), $_SESSION['user']['id']);
					$ok = false;
				}
			}

			// uuid
			if ($ok) {
				if (isset($vtl['uuid'])) {
					if (!is_string($vtl['uuid'])) {
						$dbDriver->writeLog(DB::DB_LOG_DEBUG, 'POST api/v1/vtl (%d) => uuid must be a string', __LINE__, $_SESSION['user']['id']);
						httpResponse(400, array('message' => 'uuid must be a string'));
					} elseif (!uuid_is_valid($vtl['uuid']))
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
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/vtl (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$result = $dbDriver->createVTL($vtl);
			if ($result) {
				httpAddLocation('/vtl/?id=' . $result);
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/vtl (%d) => VTL %s created', __LINE__, $result), $_SESSION['user']['id']);
				httpResponse(201, array(
					'message' => 'VTL created successfully',
					'vtl_id' => $result
				));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/vtl (%d) => Query failure', __LINE__, $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/vtl (%d) => createVTL(%s)', var_export($vtl, true)), __LINE__, $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			break;

		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/vtl (%d) => A non-admin user tried to update a VTL', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$vtl = httpParseInput();

			if (!isset($vtl))
				httpResponse(400, array('message' => 'VTL information is required'));

			if (!$dbDriver->startTransaction()) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('PUT api/v1/vtl (%d) => Failed to finish transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$ok = (bool) $vtl;
			$failed = false;

			// id
			if ($ok)
				$ok = isset($vtl['id']) && is_integer($vtl['id']);
			if ($ok) {
				$vtl_base = $dbDriver->getVTL($vtl['id']);
				if ($vtl_base === null) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl (%d) => getVTL(%s)', __LINE__, $vtl['id']), $_SESSION['user']['id']);
					$failed = true;
				} elseif ($vtl_base === false) {
					$dbDriver->cancelTransaction();
					httpResponse(404, array('message' => 'VTL not found'));
				}
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
					if ($host === null)
						$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl (%d) => getHost(%s)', __LINE__, posix_uname()['nodename']), $_SESSION['user']['id']);
					$failed = true;
				} else
					$vtl['host'] = $host;
			}

			// mediaformat
			if ($ok)
				$ok = isset($vtl['mediaformat']) && is_integer($vtl['mediaformat']);
			if ($ok) {
				$exists = $dbDriver->getMediaFormat($vtl['mediaformat']);
				if ($exists === null) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl (%d) => getMediaFormat(%s)', __LINE__, $vtl['mediaformat']), $_SESSION['user']['id']);
					$failed = true;
				} else if ($exists === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl (%d) => mediaformat %s does not exists', __LINE__, $vtl['mediaformat']), $_SESSION['user']['id']);
					$ok = false;
				}
			}

			// uuid
			if ($ok) {
				if (isset($vtl['uuid'])) {
					$dbDriver->cancelTransaction();
					httpResponse(400, array('message' => 'uuid cannot be modified'));
				}
				$vtl['uuid'] = $vtl_base['uuid'];
			}

			// nbdrives
			if ($ok)
				$ok = isset($vtl['nbdrives']) && is_integer($vtl['nbdrives']);

			// nbslots
			if ($ok)
				$ok = isset($vtl['nbslots']) && is_integer($vtl['nbslots']);

			// deleted
			if ($ok)
				$ok = isset($vtl['deleted']) && is_bool($vtl['deleted']);

			// gestion des erreurs
			if ($failed) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/vtl (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if (!$ok) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'Incorrect input'));
			}

			$result = $dbDriver->updateVTL($vtl);
			if (!$result)
				$dbDriver->cancelTransaction();
			if ($result === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/vtl (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/vtl (%d) => updateVTL(%s)', __LINE__, $vtl['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($result === false) {
				httpResponse(404, array('message' => 'VTL not found'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('PUT api/v1/vtl (%d) => VTL %s updated', __LINE__, $vtl['id']), $_SESSION['user']['id']);
				httpResponse(200, array(
					'message' => 'VTL updated successfully',
					'vtl_id' => $vtl['id']
				));
			}

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_ALL_METHODS);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
