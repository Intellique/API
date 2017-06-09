<?php
/**
 * \addtogroup authentication
 * \page auth Authentication
 * \section Authentication
 * To authenticate a user,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/auth/ \endverbatim
 * \param login : user login
 * \param password : user password
 * \param apikey : application key
 * \return HTTP status codes :
 *   - \b 201 Logged in
 *     \verbatim User id is returned \endverbatim
 *   - \b 400 Bad request - Either ; login and/or password and/or apikey missing
 *   - \b 401 Log in failed
 *
 * \section Connection_status Connection status
 * To check user's connection status,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/auth/ \endverbatim
 * \return HTTP status codes :
 * - \b 200 Logged in
 * - \b 401 Not logged in
 *
 * \section Disconnection
 * To log out,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/auth/ \endverbatim
 * \return HTTP status codes :
 * - \b 200 Logged out
 */
	require_once("../lib/env.php");

	require_once("http.php");
	require_once("uuid.php");
	require_once("session.php");
	require_once("dbSession.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			if (isset($_SESSION['user'])) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/auth => User %s logged out', $_SESSION['user']['login']), $_SESSION['user']['id']);
				session_destroy();
				httpResponse(200, array('message' => 'Logged out'));
			}
			break;

		case 'GET':
			if (isset($_SESSION['user']))
				httpResponse(200, array(
					'message' => 'Logged in',
					'user_id' => $_SESSION['user']['id']
				));
			else
				httpResponse(401, array('message' => 'Not logged in'));
			break;

		case 'POST':
			$credential = httpParseInput();

			if (!$credential || !isset($credential['login']) || !isset($credential['password']) || !isset($credential['apikey']))
				httpResponse(400, array('message' => '"login", "password" and "apikey" are required'));
			if (!uuid_is_valid($credential['apikey']))
				httpResponse(400, array('message' => 'apikey is not valid'));

			$apikey = $dbDriver->getApiKeyByKey($credential['apikey']);

			if ($apikey === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'POST api/v1/auth => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getApiKeyByKey(%s)', $credential['apikey']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			}
			if ($apikey === false)
				httpResponse(401, array('message' => 'Invalid API key'));

			$user = $dbDriver->getUser(null, $credential['login'], true);

			if ($user === false || $user['disabled'])
				httpResponse(401, array('message' => 'Log in failed'));

			$password = $credential['password'];
			$half_length = strlen($password) >> 1;
			$password = sha1(substr($password, 0, $half_length) . $user['salt'] . substr($password, $half_length));

			if ($password != $user['password'])
				httpResponse(401, array('message' => 'Authentication failed'));

			$_SESSION['user'] = $user;
			$_SESSION['apikey'] = $apikey;

			httpAddLocation('/auth/');
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/auth => User %s logged in', $_SESSION['user']['login']), $_SESSION['user']['id']);
			httpResponse(201, array(
				'message' => 'Logged in',
				'user_id' => $user['id']
			));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_ALL_METHODS & ~HTTP_PUT);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
