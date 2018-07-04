<?php
/**
 * \addtogroup script
 * \page script
 * \section Script_ID Script information
 * To get script by its id
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/script/ \endverbatim
 * \param id : script id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim scrip information is returned \endverbatim
 *   - \b 404 Script not found
 *   - \b 500 Query failure

 * \section Scripts Script ids
 * To get script list
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/script/ \endverbatim
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim scrip information is returned \endverbatim
 *   - \b 404 Script not found
 *   - \b 500 Query failure
 */
	require_once("../lib/env.php");

	require_once("http.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			if (isset($_GET['id'])) {
				if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
					httpResponse(400, array('message' => 'Script ID must be an integer'));

				$script = $dbDriver->getScriptById($_GET['id']);
				if ($script === null)
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				elseif ($script === false)
					httpResponse(404, array('message' => 'Script not found'));
				httpResponse(200, array('message' => 'Script found', 'Script' => $script));
			} else {
				$script = $dbDriver->getScripts();
				if ($script === null) {
					httpResponse(500, array(
						'message' => 'Query failure',
						'pool' => array()
					));
				} elseif (count($script) == 0)
					httpResponse(404, array('message' => 'Script not found'));
				httpResponse(200, array('message' => 'Scripts found', 'Scripts' => $script));
			}
			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>