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
 * \section Authentifaction with a token
 * To authenficate a user using a JSON Web Token,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/auth/ \endverbatim
 * \param token : token containing an header, a payload and a signature
 * \return HTTP status codes :
 *   - \b 201 Logged in
 *     \verbatim User id is returned \endverbatim
 *   - \b 400 Bad request token is invalid (expired or missing informations)
 *   - \b 401 Log in failed (wrong id user or wrong signature)
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
	require_once("db.php");
	require_once("jwt.php");

	use Lcobucci\JWT\Parser;
	use Lcobucci\JWT\ValidationData;
	use Lcobucci\JWT\Signer;
	use Lcobucci\JWT\Signer\Hmac\Sha256;

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			if (isset($_SESSION['user'])) {
				$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('DELETE api/v1/auth (%d) => User %s logged out', __LINE__, $_SESSION['user']['login']), $_SESSION['user']['id']);
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
			$headers = apache_request_headers();
			if (array_key_exists('Authorization', $headers)) {
				$authJWTs = explode(' ', $headers['Authorization'], 2);
				if (count($authJWTs) < 2)
					httpResponse(400, array('message' => 'token is not valid', 'debug' => array($authJWTs)));

				error_log($authJWTs[0]);
				if ($authJWTs[0] != 'Basic') {
					$testJwt = array_filter(explode('.', $authJWTs[1]));
					if ($authJWTs[0] != 'Bearer' || count($testJwt) != 3)
						httpResponse(400, array('message' => 'token is not valid'));

					$vToken = (new Parser())->parse((string) $authJWTs[1]);
					$vData = new ValidationData();
					$vData->setIssuer('StoriqOneBE');

					if (!$vToken->validate($vData))
						httpResponse(400, array('message' => 'token is not valid'));

					$id = $vToken->getClaim('login');
					$user = $dbDriver->getUserById($id);
					if ($user === null)
						httpResponse(500, array('message' => 'Query failure'));
					elseif ($user === false)
						httpResponse(401, array('message' => 'Log in failed'));

					$signer = new Sha256();
					if (!$vToken->verify($signer, $user['password']))
						httpResponse(401, array('message' => 'Log in failed'));

					$_SESSION['user'] = $user;

					httpAddLocation('/auth/');
					$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/auth (%d) => User %s logged in', __LINE__, $_SESSION['user']['login']), $_SESSION['user']['id']);
					httpResponse(201, array(
						'message' => 'Logged in',
						'user_id' => $user['id']
					));
				}
			}

			$credential = httpParseInput();

			if (!$credential || !isset($credential['login']) || !isset($credential['password']) || !isset($credential['apikey']))
				httpResponse(400, array('message' => '"login", "password" and "apikey" are required'));
			if (!uuid_is_valid($credential['apikey']))
				httpResponse(400, array('message' => 'apikey is not valid'));

			$apikey = $dbDriver->getApiKeyByKey($credential['apikey']);
			if ($apikey === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('POST api/v1/auth (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('POST api/v1/auth (%d) => getApiKeyByKey(%s)', __LINE__, $credential['apikey']), $_SESSION['user']['id']);
				httpResponse(500, array('message' => 'Query failure'));
			} elseif ($apikey === false)
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
			$dbDriver->writeLog(DB::DB_LOG_INFO, sprintf('POST api/v1/auth (%d) => User %s logged in', __LINE__, $_SESSION['user']['login']), $_SESSION['user']['id']);
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
