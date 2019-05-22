<?php

	function validateDate($strDate, $date, $format = 'Y-m-d H:i:s') {
		return $date && $date->format($format) == $strDate;
	}

	function dateTimeParse($strDate) {
		if ($strDate == "now")
			return new DateTime();

		$dateFormats = array(DateTime::ISO8601, DateTime::RFC2822, DateTime::RFC822, "Y-m-d", "d-m-Y" , "Y-m-d H:i:s.uO", "Y-m-d H:i:sO");

		foreach ($dateFormats as $format) {
			$date = date_create_from_format($format, $strDate);

			if ($date !== false && validateDate($strDate, $date, $format))
				break;

			$date = null;
		}

		return $date;
	}
?>
