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
	require_once("dbArchive.php");
	require_once("dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			$params = array();
			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'GET api/v1/poolgroup => A non-admin user tried to get informations from a poolgroup', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/poolgroup => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Poolgroup ID must be an integer'));
				}
				$params['poolgroup'] = intval($_GET['id']);
			} else
				httpResponse(400, array('message' => 'Poolgroup ID is required'));

			$exists = $dbDriver->getPoolgroup($_GET['id']);
			if ($exists === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/poolgroup => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolgroup(%s)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($exists === false)
				httpResponse(404, array('message' => 'Poolgroup not found'));

			$pools = $dbDriver->getPooltopoolgroup($_GET['id']);
			if ($pools === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/poolgroup => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPooltopoolgroup(%s)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($pools === false)
				httpResponse(404, array(
						'message' => 'Pools not found',
						'pools' => array()
				));

			$users = $dbDriver->getUsers($params);
			if ($users['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/poolgroup => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getUsers(%s)', $params), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'users' => array()
				));
			} elseif ($users['total_rows'] === 0)
				httpResponse(404, array(
					'message' => 'Users not found',
					'users' => array()
				));

			$result['users'] = $users['rows'];
			$result['pools'] = $pools;

			httpResponse(200, array(
				'message' => 'Query succeeded',
				'poolgroup '.intval($_GET['id']) => $result
			));
			break;

		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'PUT api/v1/poolgroup => A non-admin user tried to update a poolgroup', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$input = httpParseInput();
			if (!isset($input['poolgroup']) || !isset($input['pools'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, 'PUT api/v1/poolgroup => poolgroup and pools are required', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Poolgroup and pools are required'));
			}

			if (!is_numeric($input['poolgroup'])) {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/poolgroup => poolgroup must be an integer and not %s', $input['poolgroup']), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Poolgroup must be an integer'));
			}

			$exists = $dbDriver->getPoolgroup($input['poolgroup']);
			if ($exists === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/poolgroup => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPoolgroup(%s)', $input['poolgroup']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($exists === false)
				httpResponse(404, array('message' => 'Poolgroup not found'));

			$updatedPools = explode(',', $input['pools']);

			foreach($updatedPools as $value) {
				if (!is_numeric($value)) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, 'PUT api/v1/poolgroup => values in pools must be integer', $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Values in pools must be integer'));
				}
			}

			$params = array('poolgroup' => $input['poolgroup']);
			$poolsToChange = $dbDriver->getPooltopoolgroup($input['poolgroup']);
			if ($poolsToChange === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/poolgroup => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getPooltopoolgroup(%s)', var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}

			if ($poolsToChange === false)
				httpResponse(404, array(
						'message' => 'Pools not found',
						'pools' => array()
				));
			$result = $dbDriver->updatePoolgroup($input['poolgroup'], $poolsToChange, $updatedPools);

			if ($result === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'PUT api/v1/poolgroup => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updatePoolgroup(%s, %s, %s)', $input['poolgroup'], var_export($poolsToChange, true), var_export($updatedPools, true)), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($result === false) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, 'PUT api/v1/poolgroup => Input contains one or several pools that do not exist', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('updatePoolgroup(%s, %s, %s)', $input['poolgroup'], var_export($poolsToChange, true), var_export($updatedPools, true)), $_SESSION['user']['id']);
				httpResponse(404, array('message' => 'Pool(s) in input not found'));
			}
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('PUT api/v1/poolgroup => Poolgroup %s updated successfully', $input['poolgroup']), $_SESSION['user']['id']);
			httpResponse(200, array('message' => 'Poolgroup updated successfully'));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET & HTTP_PUT);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
