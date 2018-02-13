<?php
/**
 * \addtogroup poolgroup
 * \page poolgroup
 * \section Poolgroup_ID Poolgroup information
 * To list users and pools that are linked to a poolgroup
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/poolgroup/ \endverbatim
 * \param id : poolgroup id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Poolgroup information is returned
{
   {
   'message': 'Query succeeded', 'poolgroup 1': {'pools': [3, 5, 7], 'users': [1, 2]}
   }
}\endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Poolgroup not found / Pools not found / Users not found
 *   - \b 500 Query failure
 *
 *
 * \section Poolgroup-update Poolgroup update
 * To update the list of pools assigned to a poolgroup,
 * use \b PUT method
 * \verbatim path : /storiqone-backend/api/v1/poolgroup/ \endverbatim
 * <b>Parameters</b>
 *        Name          |         Type             |                     Constraint
 * | :----------------: | :---------------------:  | :---------------------------------------------------------:
 * |  poolgroup         |  integer                 | non NULL
 * |  pools             |  integer                 | 'pools' format must be 'pools=pool1,pool2,pool3...' replace pool1, pool2, pool3 with the id of an \b existing pool to assign to the poolgroup. These pools will replace the current pools assigned to the poolgroup
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Pools not found / Poolgroup not found
 *   - \b 500 Query failure
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("uuid.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			$params = array();
			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/poolgroup (%d) => A non-admin user tried to get informations from a poolgroup', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (isset($_GET['id'])) {
				$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
				if ($id === false) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolgroup (%d) => id must be an integer and not "%s"', __LINE__, $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Poolgroup ID must be an integer'));
				}
				$params['poolgroup'] = $id;
			} else
				httpResponse(400, array('message' => 'Poolgroup ID is required'));

			$exists = $dbDriver->getPoolGroup($_GET['id']);
			$result = $exists;
			if ($exists === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/poolgroup (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolgroup (%d) => getPoolGroup(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($exists === false)
				httpResponse(404, array('message' => 'Poolgroup not found'));

			$pools = $dbDriver->getPooltopoolgroup($_GET['id']);
			if ($pools === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/poolgroup (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolgroup (%d) => getPooltopoolgroup(%s)', __LINE__, $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($pools === false)
				$result['pools'] = 'Pools not found';
			else
				$result['pools'] = $pools;

			$users = $dbDriver->getUsers($params);
			if ($users['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/poolgroup (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolgroup (%d) => getUsers(%s)', __LINE__, $params), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'users' => array()
				));
			} elseif ($users['total_rows'] === 0)
				$result['users'] = 'Users not found';
			else
				$result['users'] = $users['rows'];

			httpResponse(200, array(
				'message' => 'Query succeeded',
				'poolgroup' => $result
			));
			break;

		case 'POST':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/poolgroup (%d) => A non-admin user tried to create a poolgroup', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$poolgroup = httpParseInput();
			if (!isset($poolgroup['name'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('POST api/v1/poolgroup (%d) => Trying to create a poolgroup without specifying poolgroup name', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'poolgroup name is required'));
			}

			if (isset($poolgroup['uuid'])) {
				if (!is_string($poolgroup['uuid'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolgroup (%d) => uuid must be a string and not "%s"', __LINE__, $poolgroup['uuid']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'uuid must be a string'));
				} elseif (!uuid_is_valid($poolgroup['uuid']))
					httpResponse(400, array('message' => 'uuid is not valid'));
			} else
				$poolgroup['uuid'] = uuid_generate();

			if (isset($poolgroup['pools'])) {
				if (!is_array($poolgroup['pools'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolgroup (%d) => pools must be an array of integers', __LINE__), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'pools must be an array of integers'));
				} else {
					foreach ($poolgroup['pools'] as &$pool)
						if (!is_integer($pool)) {
							$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolgroup (%d) => pools must be an array of integers', __LINE__), $_SESSION['user']['id']);
							httpResponse(400, array('message' => 'pools must be an array of integers'));
						}
				}
			} else
				$poolgroup['pools'] = array();

			$poolgroup_id = $dbDriver->createPoolGroup($poolgroup);
			if (is_integer($poolgroup_id)) {
				httpAddLocation('/poolgroup/?id=' . $poolgroup_id);
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/poolgroup (%d) => PoolGroup %s created', __LINE__, $poolId), $_SESSION['user']['id']);
				httpResponse(201, array(
					'message' => 'PoolGroup created successfully',
					'poolgroup_id' => $poolgroup_id
				));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/poolgroup (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/poolgroup (%d) => createPoolGroup(%s)', __LINE__, var_export($poolgroup, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query Failure'));
			}

			break;


		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/poolgroup (%d) => A non-admin user tried to update a poolgroup', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$input = httpParseInput();
			if (!isset($input['poolgroup']) || !isset($input['pools'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolgroup (%d) => poolgroup and pools are required', __LINE__), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Poolgroup and pools are required'));
			}

			if (!is_integer($input['poolgroup'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolgroup (%d) => poolgroup must be an integer and not %s', __LINE__, var_export($input['poolgroup'], true)), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Poolgroup must be an integer'));
			}

			if (!$dbDriver->startTransaction()) {
				$dbDriver->writeLog(DB::DB_LOG_EMERGENCY, sprintf('PUT api/v1/poolgroup (%d) => Failed to start transaction', __LINE__), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Transaction failure'));
			}

			$exists = $dbDriver->getPoolGroup($input['poolgroup'], DB::DB_ROW_LOCK_UPDATE);
			if (!$exists)
				$dbDriver->cancelTransaction();
			if ($exists === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/poolgroup (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolgroup (%d) => getPoolGroup(%s)', __LINE__, $input['poolgroup']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($exists === false)
				httpResponse(404, array('message' => 'Poolgroup not found'));

			foreach($input['pools'] as $pool) {
				if (!is_integer($pool)) {
					$dbDriver->cancelTransaction();
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolgroup (%d) => values in pools must be integer', __LINE__), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Values in pools must be integer'));
				}

				$exists = $dbDriver->getPool($pool, DB::DB_ROW_LOCK_SHARE);
				if (!$exists)
					$dbDriver->cancelTransaction();
				if ($exists === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/poolgroup (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolgroup (%d) => getPool(%s)', __LINE__, $pool), $_SESSION['user']['id']);
					httpResponse(500, array('message' => 'Query failure'));
				} elseif ($exists === false)
					httpResponse(404, array('message' => sprintf('Pool (%d) not found', $pool)));
			}

			$result = $dbDriver->updatePoolGroup($input['poolgroup'], $input['pools']);
			if ($result === null) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('PUT api/v1/poolgroup (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolgroup (%d) => updatePoolgroup(%s, %s, %s)', __LINE__, $input['poolgroup'], var_export($poolsToChange, true), var_export($updatedPools, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($result === false) {
				$dbDriver->cancelTransaction();
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('PUT api/v1/poolgroup (%d) => Input contains one or several pools that do not exist', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolgroup (%d) => updatePoolgroup(%s, %s, %s)', __LINE__, $input['poolgroup'], var_export($poolsToChange, true), var_export($updatedPools, true)), $_SESSION['user']['id']);
				httpResponse(404, array('message' => 'Pool(s) in input not found'));
			} elseif (!$dbDriver->finishTransaction()) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			} else {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('PUT api/v1/poolgroup (%d) => Poolgroup %s updated successfully', __LINE__, $input['poolgroup']), $_SESSION['user']['id']);
				httpResponse(200, array('message' => 'Poolgroup updated successfully'));
			}

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET | HTTP_POST | HTTP_PUT);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
