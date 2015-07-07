<?php
	function formatContentType() {
		header("Content-Type: application/vnd.php.serialized; charset=ISO-8859-1");
	}

	function formatParseInput(&$option) {
		$content = file_get_contents('php://input');
		return unserialize($content);
	}

	function formatPrint(&$message, &$option) {
		echo serialize($message);
	}
?>
