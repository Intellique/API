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
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':

			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'GET api/v1/poolmirror => A non-admin user tried to get informations from poolmirrors', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}
			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolmirror => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Pool mirror ID must be an integer'));
				}
				$poolmirror = $dbDriver->getPoolMirror($_GET['id']);
				if ($poolmirror === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/poolmirror => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolMirror(%s)', $_GET['id']), $_SESSION['user']['id']);
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

				$poolmirrors = $dbDriver->getPoolMirrors($params);
				if ($poolmirrors['query_executed'] === false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/poolmirror => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolMirrors(%s)', var_export($params, true)), $_SESSION['user']['id']);
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
