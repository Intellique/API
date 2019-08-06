<?php
/**
 * \addtogroup script
 * \page script
 * \subpage actionscript Script Action
 * \section Action_script add or delete script
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/script/action \endverbatim

 * <b>Optional parameters</b>
 * |    Name     |  Type             |                                  Description                                        |           Constraint            |
 * | :---------: | :---------------: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | action      | string            | to add or delete a script                                                           | action = add || action = delete |
 * | script_id   | integer           | script id                                                                           |                                 |
 * | pool        | integer           | pool id                                                                             |                                 |
 * | jobtype     | integer           | jobtype                                                                             | 1 <= jobtype <= 9               |
 *

 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Archives ids list is returned
{
   {
   "message":"Query successful"
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 script exist or not
 *   - \b 500 Query failure
 */

	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'A non-admin user tried to add or delete a script', $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$params = array();
			$ok = true;

      if (isset($_GET['action'])) {
        switch ($_GET['action']) {
          case 'add':
          case 'delete':
            $params['action'] = $_GET['action'];
            break;

          default:
            $ok = false;
            break;
        }
				if (!isset($_GET['script_id']) || !isset($_GET['pool']) || !isset($_GET['jobtype']))
					$ok = false;
				else {
					if (!filter_var($_GET['script_id'], FILTER_VALIDATE_INT) || !filter_var($_GET['pool'], FILTER_VALIDATE_INT) || !filter_var($_GET['jobtype'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 1, 'max_range' => 9))))
						$ok = false;
					else {
						$params['script_id'] = $_GET['script_id'];
						$params['pool'] = $_GET['pool'];
						$params['jobtype'] = $_GET['jobtype'];
					}
				}
      } else
					$ok = false;

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			switch ($_GET['action']) {
				case 'add':
					$result = $dbDriver->addScript($params);
					$function_name = 'add';
					break;

				case 'delete':
					$result = $dbDriver->deleteScript($params);
					$function_name = 'delete';
					break;
			}

			if ($result['query_prepared'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/script/action => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('%s(%s)',$function_name,var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'query' => $result
				));
			}
			if ($result['query_executed'] === false) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/script/action => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('%s(%s)',$function_name,var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'query' => $result
				));
			}
			if (!$result['action'])
				httpResponse(404, array(
					'message' => $result
				));

			httpResponse(200, array(
				'message' => $result
			));
			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
