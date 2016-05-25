<?php
/**
 * \addtogroup pooltemplate
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
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'DELETE api/v1/pooltemplate => A non-admin user tried to delete a pooltemplate', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/pooltemplate => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'pooltemplate ID must be an integer'));
				}

				$pooltemplate = $dbDriver->getPoolTemplate($_GET['id']);
				if ($pooltemplate === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/pooltemplate => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolTemplate(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($pooltemplate === false)
					httpResponse(404, array('message' => 'Pool template not found'));

				$result = $dbDriver->deletePoolTemplate($_GET['id']);
				if ($result === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'DELETE api/v1/pooltemplate => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('deletePoolTemplate(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				}

				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('pooltemplate %s deleted', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool template deleted'));

			} else
				httpResponse(400, array('message' => 'Pool template ID required'));

		break;

		case 'GET':
			checkConnected();

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/pooltemplate => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'pooltemplate ID must be an integer'));
				}

				$pooltemplate = $dbDriver->getPoolTemplate($_GET['id']);
				if ($pooltemplate === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pooltemplate => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolTemplate(%s)', $_GET['id']), $_SESSION['user']['id']);
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

				$result = $dbDriver->getPoolTemplates($params);
				if ($result['query_executed'] === false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/pooltemplate => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolTemplates(%s)', var_export($params, true)), $_SESSION['user']['id']);
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

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/pooltemplate => A non-admin user tried to create a pooltemplate', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$pooltemplate = httpParseInput();

			if (!isset($pooltemplate['name'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'POST api/v1/pooltemplate => Trying to create a pooltemplate without specifying pooltemplate name', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pooltemplate name is required'));
			}

			$autocheckmode = array('quick mode', 'thorough mode', 'none');
			if (!isset($pooltemplate['autocheck']))
				$pooltemplate['autocheck'] = 'none';
			elseif (!is_string ($pooltemplate['autocheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => autocheckmode must be a string and not "%s"', $pooltemplate['autocheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'autocheckmode must be a string'));
			}
			elseif (array_search($pooltemplate['autocheck'], $autocheckmode) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $autocheckmode));
				httpResponse(400, array('message' => 'autocheckmode value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pooltemplate['lockcheck']))
				$pooltemplate['lockcheck'] = False;
			elseif (!is_bool($pooltemplate['lockcheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => lockcheck must be a boolean and not "%s"', $pooltemplate['lockcheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'lockcheck must be a boolean'));
			}
			if (!isset($pooltemplate['growable']))
				$pooltemplate['growable'] = False;
			elseif (!is_bool($pooltemplate['growable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => growable must be a boolean and not "%s"', $pooltemplate['growable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'growable must be a boolean'));
			}

			$unbreakablelevel = array('archive', 'file', 'none');
			if (!isset($pooltemplate['unbreakablelevel']))
				$pooltemplate['unbreakablelevel'] = 'none';
			elseif (!is_string ($pooltemplate['unbreakablelevel'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => unbreakablelevel must be a string and not "%s"', $pooltemplate['unbreakablelevel']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'unbreakablelevel must be a string'));
			}
			elseif (array_search($pooltemplate['unbreakablelevel'], $unbreakablelevel) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $unbreakablelevel));
				httpResponse(400, array('message' => 'unbreakablelevel value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pooltemplate['rewritable']))
				$pooltemplate['rewritable'] = True;
			elseif (!is_bool($pooltemplate['rewritable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => rewritable must be a boolean and not "%s"', $pooltemplate['rewritable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'rewritable must be a boolean'));
			}

			if (!isset($pooltemplate['metadata']))
				$pooltemplate['metadata'] = array();

			if (!isset($pooltemplate['createproxy']))
				$pooltemplate['createproxy'] = False;
			elseif (!is_bool($pooltemplate['createproxy'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => createproxy must be a boolean and not "%s"', $pooltemplate['createproxy']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'createproxy must be a boolean'));
			}

			$pooltemplateId = $dbDriver->createPoolTemplate($pooltemplate);

			if ($pooltemplateId === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/pooltemplate => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('createPoolTemplate(%s)', $pooltemplate), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			}

			httpAddLocation('/pooltemplate/?id=' . $pooltemplateId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('pooltemplate %s created', $pooltemplateId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Pool template created successfully',
				'pooltemplate_id' => $pooltemplateId
			));

		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/pooltemplate => A non-user admin tried to update a pool template', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$pooltemplate = httpParseInput();
			if ($pooltemplate === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/pooltemplate => Trying to update a pooltemplate without specifying it', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pooltemplate is required'));
			}
			if (!isset($pooltemplate['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/pooltemplate => Trying to update a pooltemplate without specifying its id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pooltemplate id is required'));
			}

			if (!is_int($pooltemplate['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => id must be an integer and not "%s"', $pooltemplate['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'id must be an integer'));
			}

			$pooltemplate_base = $dbDriver->getPoolTemplate($pooltemplate['id']);

			if (isset($pooltemplate['name'])) {
				$id_pooltemplate_by_name = $dbDriver->getPoolTemplateByName($pooltemplate['name']);
				if ($id_pooltemplate_by_name === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pooltemplate => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolTemplateByName(%s)', $pooltemplate['name']), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query Failure'));
				}
				if ($id_pooltemplate_by_name === $pooltemplate['id'])
					httpResponse(400, array('message' => 'Specified name already exists in the database and does not belong to the pooltemplate you are editing'));
			} else
				$pooltemplate['name'] = $pooltemplate_base['name'];

			$autocheckmode = array('quick mode', 'thorough mode', 'none');
			if (!isset($pooltemplate['autocheck']))
				$pooltemplate['autocheck'] = $pooltemplate_base['autocheck'];
			elseif (!is_string ($pooltemplate['autocheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => autocheck must be a string and not "%s"', $pooltemplate['autocheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'autocheckmode must be a string'));
			}
			elseif (array_search($pooltemplate['autocheck'], $autocheckmode) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $autocheckmode));
				httpResponse(400, array('message' => 'autocheckmode value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pooltemplate['lockcheck']))
				$pooltemplate['lockcheck'] = $pooltemplate_base['lockcheck'];
			elseif (!is_bool($pooltemplate['lockcheck'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => lockcheck must be a boolean and not "%s"', $pooltemplate['lockcheck']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'lockcheck must be a boolean'));
			}

			if (!isset($pooltemplate['growable']))
				$pooltemplate['growable'] = $pooltemplate_base['growable'];
			elseif (!is_bool($pooltemplate['growable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => growable must be a boolean and not "%s"', $pooltemplate['growable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'growable must be a boolean'));
			}

			$unbreakablelevel = array('archive', 'file', 'none');
			if (!isset($pooltemplate['unbreakablelevel']))
				$pooltemplate['unbreakablelevel'] = $pooltemplate_base['unbreakablelevel'];
			elseif (!is_string ($pooltemplate['unbreakablelevel'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => unbreakablelevel must be a string and not "%s"', $pooltemplate['unbreakablelevel']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'unbreakablelevel must be a string'));
			}
			elseif (array_search($pooltemplate['unbreakablelevel'], $unbreakablelevel) === false) {
				$string_mode = join(', ', array_map(function ($value) { return '"'.$value.'"';}, $unbreakablelevel));
				httpResponse(400, array('message' => 'unbreakablelevel value is invalid. It should be in ' . $string_mode));
			}

			if (!isset($pooltemplate['rewritable']))
				$pooltemplate['rewritable'] = $pooltemplate_base['rewritable'];
			elseif (!is_bool($pooltemplate['rewritable'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => rewritable must be a boolean and not "%s"', $pooltemplate['rewritable']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'rewritable must be a boolean'));
			}

			if (!isset($pooltemplate['metadata']))
				$pooltemplate['metadata'] = $pooltemplate_base['metadata'];

			if (!isset($pooltemplate['createproxy']))
				$pooltemplate['createproxy'] = False;
			elseif (!is_bool($pooltemplate['createproxy'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/pooltemplate => createproxy must be a boolean and not "%s"', $pooltemplate['createproxy']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'createproxy must be a boolean'));
			}

			error_log('update pooltemplate: ' . json_encode($pooltemplate));


			$result = $dbDriver->updatePoolTemplate($pooltemplate);

			if ($result) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('pooltemplate %s updated', $pooltemplate['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool template updated successfully'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/pooltemplate => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updatePoolTemplate(%s)', var_export($pooltemplate, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
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