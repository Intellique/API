<?php
	require_once("http.php");

	$directory = '/storiqone-backend';
	$sub_directory = '/api/v1/';

	$pos_api = strpos($_SERVER['SCRIPT_FILENAME'], '/api/v1/');
	$cookie_path = substr($_SERVER['SCRIPT_FILENAME'], $pos_api - strlen($directory), strlen($directory) + strlen($sub_directory));

	session_set_cookie_params(0, $cookie_path);
	if (session_id() == NULL)
		session_start();

	/**
	 * \brief check if user is connected
	 * \return \b TRUE on success, <b>HTTP status code 401 Authentication failed</b> on failure
	 */
	function checkConnected() {
		if (!isset($_SESSION["user"]))
			httpResponse(401, array('message' => 'Not logged in'));
		$_SESSION['LASTACTION'] = time();
		return true;
	}

	/**
	 * \brief check if user is logged
	 * \return \b TRUE on success, \b FALSE on failure
	 */
	function isLogged() {
		return isset($_SESSION['login']);
	}

	/**
	 * Session timeout.
	 * After 1 hour unactive : session is detroyed
	 * Return : HTTP status code 401 Not logged in on failure
	 */
	if (isset($_SESSION['LASTACTION']) && (time() - $_SESSION['LASTACTION'] > 3600)) {
		session_destroy();
		error_log("session timeout");

		httpResponse(401, array('message' => 'Not logged in'));
	}
?>
