<?php
/**
 * \addtogroup authentication
 * \page auth Authentication
 * \subpage token
 * \section Token_creation Token creation
 * To generate a token (JWT),
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/auth/token/ \endverbatim
 * \param id : user id
 * \param password : user password (hash used as a key to create token's signature)
 * \return HTTP status codes :
 *   - \b 201 Token created
 *     \verbatim Token is created \endverbatim
 */

	require_once("../../lib/env.php");
	require_once ("jwt.php");
	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	use Lcobucci\JWT\Builder;
	use Lcobucci\JWT\Signer\Hmac\Sha256;


	switch ($_SERVER['REQUEST_METHOD']) {
		case 'POST':
			checkConnected();
			$signer = new Sha256();
			$token = (new Builder())->setIssuer('StoriqOneBE')
				->set('login',$_SESSION["user"]["id"])
				->setIssuedAt(time())
				->setExpiration(time() + 20)
				->sign($signer,$_SESSION["user"]["password"])
				->getToken();
			header("Authorization: Bearer ".$token);

			httpResponse(201, array('message' => 'Token created'));

		case 'OPTIONS':
			httpOptionsMethod(HTTP_POST);
			break;

		default:
			httpUnsupportedMethod();
			break;

	}
?>