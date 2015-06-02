<?php
	/**
	 * \brief Constant for HTTP DELETE method
	 */
	define('HTTP_DELETE', 1);
	/**
	 * \brief Constant for HTTP GET method
	 */
	define('HTTP_GET', 2);
	/**
	 * \brief Constant for HTTP POST method
	 */
	define('HTTP_POST', 4);
	/**
	 * \brief Constant for HTTP PUT method
	 */
	define('HTTP_PUT', 8);

	/**
	 * \brief returns allowed http methods to the client
	 * \param $methods : list of http methods
	 * \return HTTP status codes :
	 * - \b 200
	 *   \verbatim Allowed http method(s) is/are returned \endverbatim
	 */
	function httpOptionsMethod($methods) {
		$allow = 'OPTIONS';
		if ($methods & HTTP_DELETE)
			$allow .= ', DELETE';
		if ($methods & HTTP_GET)
			$allow .= ', GET';
		if ($methods & HTTP_POST)
			$allow .= ', POST';
		if ($methods & HTTP_PUT)
			$allow .= ', PUT';

		http_response_code(200);
		header("Allow: " . $allow);
	}

	/**
	 * \brief returns not supported http method
	 * \return HTTP status codes :
	 * - \b 405 Method Not Allowed
	 */
	function httpUnsupportedMethod() {
		header("Content-Type: application/json; charset=utf-8");
		http_response_code(405);
		echo json_encode(array('message' => 'Method Not Allowed'));
		exit;
	}
?>
