<?php
	function formatContentType() {
		header("Content-Type: application/json; charset=utf-8");
	}

	function formatParseInput(&$option) {
		$content = file_get_contents('php://input');
		return json_decode($content, true);
	}

	function formatPrint(&$message, &$option) {
		echo json_encode($message);
	}
?>
