<?php
/**
 * \addtogroup device
 * \page device
 * \section Device Device information
 * To get a device by its id
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/device/ \endverbatim
 * \param id : device id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Device information is returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Device not found
 *   - \b 500 Query failure
 *
 * \section Device Devices ids (multiple list)
 * To get devices ids list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/device/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple devices ids list do not pass an id or ids as parameter</b>
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

			if (!$_SESSION['user']['isadmin'] || !$_SESSION['user']['canarchive'] || !$_SESSION['user']['canrestore']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'GET api/v1/device => Permission denied for a non-admin/archiver/restorer user', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}
			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id'])) {
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/device => id must be an integer and not "%s"', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(400, array('message' => 'Device ID must be an integer'));
				}
				$device = $dbDriver->getDevice($_GET['id']);
				if ($device === null) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/device => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getDevice(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'device' => array()
					));
				} elseif ($device === false)
					httpResponse(404, array(
						'message' => 'Device not found',
						'device' => array()
					));
				httpResponse(200, array(
						'message' => 'Query succeeded',
						'device' => $device
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

				$devices = $dbDriver->getDevices($params);
				if ($devices['query_executed'] === false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/device => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getDevices(%s)', var_export($params, true)), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'devices' => array()
					));
				} elseif ($devices === false)
					httpResponse(404, array(
						'message' => 'Device not found',
						'devices' => array()
					));
				httpResponse(200, array(
						'message' => 'Query succeeded',
						'devices' => $devices['rows']
				));
			}
			break;
	}
?>
