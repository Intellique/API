<?php
	session_set_cookie_params(0, '/api/v1/');
	if (session_id() == NULL)
		session_start();

	// session time out
	if (isset($_SESSION['LASTACTION']) && (time() - $_SESSION['LASTACTION'] > 3600)) {
		// time out 1 hour
		session_destroy();
		error_log("session timeout");

		header("Content-Type: application/json; charset=utf-8");
		http_response_code(401);
		echo json_encode(array('message' => 'Not logged in'));
		exit;
	}

	function checkConnected() {
		if (!isset($_SESSION["userId"])) {
			header("Content-Type: application/json; charset=utf-8");
			http_response_code(401);
			echo json_encode(array('message' => 'Not logged in'));
			exit;
		}
		return true;
	}

	function isLogged() {
		return isset($_SESSION['login']);
	}
?>