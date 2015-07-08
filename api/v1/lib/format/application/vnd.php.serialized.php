<?php
	if ($input_functions) {
		function formatParseInput(&$option) {
			$content = file_get_contents('php://input');
			return unserialize($content);
		}
	} else {
		function formatContentType() {
			header("Content-Type: application/vnd.php.serialized; charset=ISO-8859-1");
		}

		function formatPrint(&$message, &$option) {
			echo serialize($message);
		}
	}
?>
