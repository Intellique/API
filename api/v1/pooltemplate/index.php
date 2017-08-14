<?php
/**
 * \addtogroup pooltemplate
 * \page pooltemplate
 * \section PoolTemplate_ID Pool template information
 * To get a pool template by its id
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/pooltemplate/ \endverbatim
 * \param id : pool template id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Pool template information is returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 404 Pool template not found
 *   - \b 500 Query failure
 *
 * \section PoolTemplates Pool templates ids (multiple list)
 * To get pool templates ids list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/pooltemplate/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple pool templates ids list do not pass an id or ids as parameter</b>
 * \section PoolTemplate_deletion Pool template deletion
 * To delete a pool template,
 * use \b DELETE method : <i>with pool template id</i>
 * \section PoolTemplate-creation Pool template creation
 * To create a pool template,
 * use \b POST method <i>with pool template parameters </i>
 * \section PoolTemplate-update Pool template update
 * To update a pool template,
 * use \b PUT method <i>with pool template parameters</i>
 * \verbatim path : /storiqone-backend/api/v1/pooltemplate/ \endverbatim
 *        Name          |         Type             |                     Value
 * | :----------------: | :---------------------:  | :---------------------------------------------------------:
 * |  id                |  integer                 | non NULL Default value, nextval('pool_id_seq'::regclass)
 * |  name              |  character varying(64)   | non NULL
 * |  autocheck         |  autocheckmode           | non NULL Default value, 'none'::autocheckmode
 * |  lockcheck         |  boolean                 | non NULL Default value, false
 * |  growable          |  boolean                 | non NULL Default value, false
 * |  unbreakablelevel  |  unbreakablelevel        | non NULL Default value, 'none'::unbreakablelevel
 * |  rewritable        |  boolean                 | non NULL Default value, true
 * |  metadata          |  json                    | non NULL Default value, '{}'::json
 * |  createproxy       |  boolean                 | non NULL Default value, false
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
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
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('DELETE api/v1/pooltemplate (%d) => A non-admin user tried to delete a pooltemplate', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (!isset($_GET['id']))
				httpResponse(400, array('message' => 'Pool template ID required'));
			elseif (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/pooltemplate (%d) => id must be an integer and not "%s"', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pooltemplate ID must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('DELETE api/v1/pooltemplate (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$pooltemplate = $dbDriver->getPoolTemplate($_GET['id'], DB::DB_ROW_LOCK_UPDATE);
			if (!$pooltemplate)
				$dbDriver->cancelTransaction();
			if ($pooltemplate === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/pooltemplate (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/pooltemplate (%d) => getPoolTemplate(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($pooltemplate === false)
				httpResponse(404, array('message' => 'Pool template not found'));

			$result = $dbDriver->deletePoolTemplate($_GET['id']);
			if ($result === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/pooltemplate (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/pooltemplate (%d) => deletePoolTemplate(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($result === null) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'Pool template not found'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/pooltemplate (%d) => pooltemplate %s deleted', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool template deleted'));
			}

			break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pooltemplate (%d) => id must be an integer and not "%s"', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'pooltemplate ID must be an integer'));
				}

				$pooltemplate = $dbDriver->getPoolTemplate($_GET['id']);
				if ($pooltemplate === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pooltemplate (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pooltemplate (%d) => getPoolTemplate(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'pooltemplate' => array()
					));
				} elseif ($pooltemplate === false)
					httpResponse(404, array(
						'message' => 'Pool template not found',
						'pooltemplate' => array()
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'pooltemplate' => $pooltemplate
				));
			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['limit'])) {
					$limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT, array('min_range' => 1));
					if ($limit !== false)
						$params['limit'] = $limit;
					else
						$ok = false;
				}

				if (isset($_GET['offset'])) {
					$offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT, array('min_range' => 0));
					if ($offset !== false)
						$params['offset'] = $offset;
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$result = $dbDriver->getPoolTemplates($params);
				if ($result['query_executed'] === false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/pooltemplate (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pooltemplate (%d) => getPoolTemplates(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'pooltemplates' => array(),
						'total_rows' => $result['total_rows']
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'pooltemplates' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}

			break;

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/pooltemplate (%d) => A non-admin user tried to create a pooltemplate', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$pooltemplate = httpParseInput();

			if (!isset($pooltemplate['name'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/pooltemplate (%d) => Trying to create a pooltemplate without specifying pooltemplate name', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pooltemplate name is required'));
			}

			$autocheckmode = array('quick mode', 'thorough mode', 'none');
			if (!isset($pooltemplate['autocheck']))
				$pooltemplate['autocheck'] = 'none';
			elseif (!is_string ($pooltemplate['autocheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => autocheckmode must be a string and not "%s"', __LINE__, $pooltemplate['autocheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'autocheckmode must be a string'));
			} elseif (array_search($pooltemplate['autocheck'], $autocheckmode) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $autocheckmode));
				httpResponse(400, array('message' => 'autocheckmode value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pooltemplate['lockcheck']))
				$pooltemplate['lockcheck'] = False;
			elseif (!is_bool($pooltemplate['lockcheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => lockcheck must be a boolean and not "%s"', __LINE__, $pooltemplate['lockcheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'lockcheck must be a boolean'));
			}

			if (!isset($pooltemplate['growable']))
				$pooltemplate['growable'] = False;
			elseif (!is_bool($pooltemplate['growable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => growable must be a boolean and not "%s"', __LINE__, $pooltemplate['growable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'growable must be a boolean'));
			}

			$unbreakablelevel = array('archive', 'file', 'none');
			if (!isset($pooltemplate['unbreakablelevel']))
				$pooltemplate['unbreakablelevel'] = 'none';
			elseif (!is_string ($pooltemplate['unbreakablelevel'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => unbreakablelevel must be a string and not "%s"', __LINE__, $pooltemplate['unbreakablelevel']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'unbreakablelevel must be a string'));
			} elseif (array_search($pooltemplate['unbreakablelevel'], $unbreakablelevel) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $unbreakablelevel));
				httpResponse(400, array('message' => 'unbreakablelevel value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pooltemplate['rewritable']))
				$pooltemplate['rewritable'] = True;
			elseif (!is_bool($pooltemplate['rewritable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => rewritable must be a boolean and not "%s"', __LINE__, $pooltemplate['rewritable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'rewritable must be a boolean'));
			}

			if (!isset($pooltemplate['metadata']))
				$pooltemplate['metadata'] = array();

			if (!isset($pooltemplate['createproxy']))
				$pooltemplate['createproxy'] = False;
			elseif (!is_bool($pooltemplate['createproxy'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => createproxy must be a boolean and not "%s"', __LINE__, $pooltemplate['createproxy']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'createproxy must be a boolean'));
			}

			$pooltemplateId = $dbDriver->createPoolTemplate($pooltemplate);
			if ($pooltemplateId === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/pooltemplate (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => createPoolTemplate(%s)', __LINE__, $pooltemplate), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			}

			httpAddLocation('/pooltemplate/?id=' . $pooltemplateId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/pooltemplate (%d) => pooltemplate %s created', __LINE__, $pooltemplateId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Pool template created successfully',
				'pooltemplate_id' => $pooltemplateId
			));

		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/pooltemplate (%d) => A non-user admin tried to update a pool template', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$pooltemplate = httpParseInput();
			if ($pooltemplate === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/pooltemplate (%d) => Trying to update a pooltemplate without specifying it', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pooltemplate is required'));
			}

			if (!isset($pooltemplate['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/pooltemplate (%d) => Trying to update a pooltemplate without specifying its id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pooltemplate id is required'));
			} elseif (!is_integer($pooltemplate['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => id must be an integer and not "%s"', __LINE__, $pooltemplate['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'id must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('POST api/v1/pooltemplate (%d) => Failed to finish transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$pooltemplate_base = $dbDriver->getPoolTemplate($pooltemplate['id'], DB::DB_ROW_LOCK_UPDATE);
			if (!$pooltemplate_base)
				$dbDriver->cancelTransaction();
			if ($pooltemplate_base === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/archive (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/archive (%d) => getPoolTemplate(%s)', __LINE__, $pooltemplate['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($check_archive === false)
				httpResponse(404, array('message' => 'Pool template not found'));

			if (isset($pooltemplate['name'])) {
				$id_pooltemplate_by_name = $dbDriver->getPoolTemplateByName($pooltemplate['name']);
				if ($id_pooltemplate_by_name === NULL) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pooltemplate (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/pooltemplate (%d) => getPoolTemplateByName(%s)', __LINE__, $pooltemplate['name']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				} elseif ($id_pooltemplate_by_name === $pooltemplate['id']) {
					$dbDriver->cancelTransaction();
					httpResponse(400, array('message' => 'Specified name already exists in the database and does not belong to the pooltemplate you are editing'));
				}
			} else
				$pooltemplate['name'] = $pooltemplate_base['name'];

			$autocheckmode = array('quick mode', 'thorough mode', 'none');
			if (!isset($pooltemplate['autocheck']))
				$pooltemplate['autocheck'] = $pooltemplate_base['autocheck'];
			elseif (!is_string ($pooltemplate['autocheck'])) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => autocheck must be a string and not "%s"', __LINE__, $pooltemplate['autocheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'autocheckmode must be a string'));
			} elseif (array_search($pooltemplate['autocheck'], $autocheckmode) === false) {
				$dbDriver->cancelTransaction();
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $autocheckmode));
				httpResponse(400, array('message' => 'autocheckmode value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pooltemplate['lockcheck']))
				$pooltemplate['lockcheck'] = $pooltemplate_base['lockcheck'];
			elseif (!is_bool($pooltemplate['lockcheck'])) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => lockcheck must be a boolean and not "%s"', __LINE__, $pooltemplate['lockcheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'lockcheck must be a boolean'));
			}

			if (!isset($pooltemplate['growable']))
				$pooltemplate['growable'] = $pooltemplate_base['growable'];
			elseif (!is_bool($pooltemplate['growable'])) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => growable must be a boolean and not "%s"', __LINE__, $pooltemplate['growable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'growable must be a boolean'));
			}

			$unbreakablelevel = array('archive', 'file', 'none');
			if (!isset($pooltemplate['unbreakablelevel']))
				$pooltemplate['unbreakablelevel'] = $pooltemplate_base['unbreakablelevel'];
			elseif (!is_string ($pooltemplate['unbreakablelevel'])) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => unbreakablelevel must be a string and not "%s"', __LINE__, $pooltemplate['unbreakablelevel']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'unbreakablelevel must be a string'));
			} elseif (array_search($pooltemplate['unbreakablelevel'], $unbreakablelevel) === false) {
				$dbDriver->cancelTransaction();
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $unbreakablelevel));
				httpResponse(400, array('message' => 'unbreakablelevel value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pooltemplate['rewritable']))
				$pooltemplate['rewritable'] = $pooltemplate_base['rewritable'];
			elseif (!is_bool($pooltemplate['rewritable'])) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => rewritable must be a boolean and not "%s"', __LINE__, $pooltemplate['rewritable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'rewritable must be a boolean'));
			}

			if (!isset($pooltemplate['metadata']))
				$pooltemplate['metadata'] = $pooltemplate_base['metadata'];

			if (!isset($pooltemplate['createproxy']))
				$pooltemplate['createproxy'] = False;
			elseif (!is_bool($pooltemplate['createproxy'])) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate (%d) => createproxy must be a boolean and not "%s"', __LINE__, $pooltemplate['createproxy']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'createproxy must be a boolean'));
			}


			$result = $dbDriver->updatePoolTemplate($pooltemplate);
			if ($result === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/pooltemplate (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updatePoolTemplate(%s)', $pooltemplate['uuid']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($result === false) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'Pool Template not found'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('PUT api/v1/pooltemplate (%d) => pooltemplate %s updated', __LINE__, $pooltemplate['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool template updated successfully'));
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
