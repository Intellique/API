<?php
/**
 * \addtogroup Date Date time formats
 * \section Date Date time formats
 * <b>Input date time formats</b> : \verbatim ISO8601, RFC2822, RFC822, PostgreSQL ISO ("Y-m-d H:i:sO") \endverbatim
 *
 * <b>Output date time format</b> : \verbatim ISO8601 \endverbatim
 */
	function dateTimeParse($strDate) {
		if ($strDate == "now")
			return new DateTime();

		$dateFormats = array(DateTime::ISO8601, DateTime::RFC2822, DateTime::RFC822, "Y-m-d H:i:s.uO", "Y-m-d H:i:sO");

		foreach ($dateFormats as $format) {
			$date = date_create_from_format($format, $strDate);

			if ($date !== false)
				break;

			$date = null;
		}

		return $date;
	}
?>