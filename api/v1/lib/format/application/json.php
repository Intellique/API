<?php
	function formatContentType() {
		header("Content-Type: application/json; charset=utf-8");
	}

	function formatPrint(&$message) {
		echo json_encode($message);
	}
?>
