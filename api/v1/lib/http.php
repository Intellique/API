<?php
	define('HTTP_DELETE', 1);
	define('HTTP_GET', 2);
	define('HTTP_POST', 4);
	define('HTTP_PUT', 8);

	function httpOptionsMethod($methods) {
		http_response_code(200);

		$allow = 'OPTIONS';
		if ($methods & HTTP_DELETE)
			$allow .= ', DELETE';
		if ($methods & HTTP_GET)
			$allow .= ', GET';
		if ($methods & HTTP_POST)
			$allow .= ', POST';
		if ($methods & HTTP_PUT)
			$allow .= ', PUT';

		header("Allow: " . $allow);
	}

	function httpUnsupportedMethod() {
		header("Content-Type: application/json; charset=utf-8");
		http_response_code(405);
		echo json_encode(array('message' => 'Method Not Allowed'));
		exit;
	}
?>