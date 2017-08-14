<?php
/**
 * \addtogroup poolmirror
 * \page poolmirror
 * \section PoolMirror_ID Pool mirror information
 * To get a pool mirror by its id
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/poolmirror/ \endverbatim
 * \param id : pool mirror id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Pool mirror information is returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 404 Pool mirror not found
 *   - \b 500 Query failure
 *
 * \section Poolmirrors Pool mirrors ids (multiple list)
 * To get pool mirrors ids list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/poolmirror/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple pool mirrors ids list do not pass an id or ids as parameter</b>
 * \section Poolmirror_deletion Pool mirror deletion
 * To delete a pool mirror,
 * use \b DELETE method : <i>with pool mirror id</i>
 * \section Poolmirror-creation Pool mirror creation
 * To create a pool mirror,
 * use \b POST method <i>with pool mirror parameters </i>
 * \section Poolmirror-update Pool mirror update
 * To update a pool mirror,
 * use \b PUT method <i>with pool mirror parameters</i>
 * \verbatim path : /storiqone-backend/api/v1/poolmirror/ \endverbatim
 *        Name          |         Type             |                     Value
 * | :----------------: | :---------------------:  | :---------------------------------------------------------:
 * |  id                |  integer                 | non NULL Default value, nextval('pool_id_seq'::regclass)
 * |  name              |  character varying(64)   | non NULL
 * |  uuid              |  uuid                    | non NULL
 * |  synchronized      |  boolean                 | non NULL
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
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('DELETE api/v1/poolmirror (%d) => A non-admin user tried to delete a poolmirror', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (!isset($_GET['id']))
				httpResponse(400, array('message' => 'Pool mirror ID required'));
			elseif (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/poolmirror (%d) => id must be an integer and not "%s"', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror ID must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('DELETE api/v1/poolmirror (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$poolmirror = $dbDriver->getPoolMirror($_GET['id'], DB::DB_ROW_LOCK_UPDATE);
			if (!$poolmirror)
				$dbDriver->cancelTransaction();
			if ($poolmirror === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/poolmirror (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/poolmirror (%d) => getPoolMirror(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($poolmirror === false)
				httpResponse(404, array('message' => 'Pool mirror not found'));

			$result = $dbDriver->deletePoolMirror($_GET['id']);
			if ($result === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('DELETE api/v1/poolmirror (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('DELETE api/v1/poolmirror (%d) => deletePoolMirror(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Transaction failure'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/poolmirror (%d) => poolmirror %s deleted', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool mirror deleted'));
			}

			break;

		case 'GET':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'GET api/v1/poolmirror => A non-admin user tried to get informations from poolmirrors', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (isset($_GET['id'])) {
				if (fitler_var($_GET['id'], FILTER_VALIDATE_INT) === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolmirror (%d) => id must be an integer and not "%s"', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Pool mirror ID must be an integer'));
				}

				$poolmirror = $dbDriver->getPoolMirror($_GET['id']);
				if ($poolmirror === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/poolmirror (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolmirror (%d) => getPoolMirror(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);

					httpResponse(500, array(
						'message' => 'Query failure',
						'poolmirror' => array()
					));
				} elseif ($poolmirror === false)
					httpResponse(404, array(
						'message' => 'Pool mirror not found',
						'poolmirror' => array()
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'poolmirror' => $poolmirror
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

				$poolmirrors = $dbDriver->getPoolMirrors($params);
				if ($poolmirrors['query_executed'] === false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/poolmirror (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolmirror (%d) => getPoolMirrors(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);

					httpResponse(500, array(
						'message' => 'Query failure',
						'poolmirrors' => array()
					));
				} elseif ($poolmirrors === false)
					httpResponse(404, array(
						'message' => 'Pool mirrors not found',
						'poolmirrors' => array()
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'poolmirrors' => $poolmirrors['rows']
				));
			}
			break;

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/poolmirror (%d) => A non-admin user tried to create a poolmirror', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$poolmirror = httpParseInput();

			if (!isset($poolmirror))
				httpResponse(400, array('message' => 'Poolmirror information is required'));

			//uuid
			if (isset($poolmirror['uuid'])) {
				if (!is_string($poolmirror['uuid'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolmirror (%d) => uuid must be a string and not "%s"', __LINE__, $poolmirror['uuid']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'uuid must be a string'));
				} elseif (!uuid_is_valid($poolmirror['uuid']))
					httpResponse(400, array('message' => 'uuid is not valid'));
			} else
				$poolmirror['uuid'] = uuid_generate();

			//name
			if (!isset($poolmirror['name'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/poolmirror (%d) => Trying to create a poolmirror without specifying poolmirror name', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'pool mirror name is required'));
			}

			//synchronized
			if (!isset($poolmirror['synchronized']))
				httpResponse(400, array('message' => 'synchronized is required'));
			elseif (!is_bool($poolmirror['synchronized'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolmirror (%d) => synchronized must be a boolean and not "%s"', __LINE__, $poolmirror['synchronized']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'synchronized must be a boolean'));
			}

			$poolmirrorId = $dbDriver->createPoolMirror($poolmirror);
			if ($poolmirrorId === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/poolmirror (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolmirror (%d) => createPoolMirror(%s)', __LINE__, var_export($poolmirror, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			}

			httpAddLocation('/poolmirror/?id=' . $poolmirrorId);
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/poolmirror (%d) => Pool mirror %s created', __LINE__, $poolmirrorId), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Pool mirror created successfully',
				'poolmirror id' => $poolmirrorId
			));
			break;

		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/poolmirror (%d) => A non-user admin tried to update a poolmirror', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$poolmirror = httpParseInput();
			if ($poolmirror === NULL) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/poolmirror (%d) => Trying to update a poolmirror without specifying it', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror is required'));
			}

			if (!isset($poolmirror['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/poolmirror (%d) => Trying to update a poolmirror without specifying its id', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror id is required'));
			} elseif (!is_integer($poolmirror['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolmirror (%d) => id must be an integer and not "%s"', __LINE__, $poolmirror['id']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolmirror id must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('PUT api/v1/poolmirror (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$poolmirror_base = $dbDriver->getPoolMirror($poolmirror['id'], DB::DB_ROW_LOCK_UPDATE);
			if ($poolmirror_base === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/poolmirror (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolmirror (%d) => getPoolMirror(%s)', __LINE__, $poolmirror['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($poolmirror_base === false) {
				$dbDriver->cancelTransaction();
				httpResponse(404, array('message' => 'PoolMirror not found'));
			}

			//uuid
			if (isset($poolmirror['uuid'])) {
				$dbDriver->cancelTransaction();
				httpResponse(400, array('message' => 'uuid cannot be modified'));
			} else
				$poolmirror['uuid'] = $poolmirror_base['uuid'];

			//name
			if (!isset($poolmirror['name']))
				$poolmirror['name'] = $poolmirror_base['name'];
			elseif (!is_string($poolmirror['name'])) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolmirror (%d) => name must be a string and not "%s"', __LINE__, $poolmirror['name']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'name must be a string'));
			}

			//synchronized
			if (!isset($poolmirror['synchronized']))
				$poolmirror['synchronized'] = $poolmirror_base['synchronized'];
			elseif (!is_bool($poolmirror['synchronized'])) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolmirror (%d) => synchronized must be a boolean and not "%s"', __LINE__, $poolmirror['synchronized']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'synchronized must be a boolean'));
			}

			$result = $dbDriver->updatePoolMirror($poolmirror);
			if (!result) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/poolmirror (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolmirror (%d) => Query updatePoolMirror(%s)', __LINE__, var_export($poolmirror, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/poolmirror (%d) => Transaction failure', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('PUT api/v1/poolmirror (%d) => Poolmirror %s updated', __LINE__, $poolmirror['id']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Pool mirror updated successfully'));
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
