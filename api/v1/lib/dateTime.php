<?php

	function validateDate($strDate, $date, $format = 'Y-m-d H:i:s') {
		$strDate = preg_replace_callback('/\.(\d{1,5})/', function($matches) {
			for ($i = strlen($matches[1]); $i < 6; $i++)
				$matches[0] .= '0';
			return $matches[0];
		}, $strDate);
		$strDate = preg_replace('/([+-])(\d+):(\d+)/', '\\1\\2\\3', $strDate);

		return $date && $date->format($format) == $strDate;
	}

	function dateTimeParse($strDate) {
		if ($strDate == "now")
			return new DateTime();

		if(preg_match('/\+\d\d$/', $strDate))
			$strDate = $strDate . '00';
		$dateFormats = array(DateTime::ISO8601, DateTime::ATOM, DateTime::RFC3339_EXTENDED, DateTime::RFC3339, DateTime::RFC2822, DateTime::RFC822, "Y-m-d", "d-m-Y" , "Y-m-d\TH:i:s\.uO", "Y-m-d H:i:s.uO", "Y-m-d H:i:sO", "Y-m-d H:i:sP");

		foreach ($dateFormats as $format) {
			$date = date_create_from_format($format, $strDate);

			if ($date !== false && validateDate($strDate, $date, $format))
				break;

			$date = null;
		}

		return $date;
	}
?>
