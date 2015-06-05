<?php
	/**
	 * \brief read database configuration
	 */
	$file = file('/etc/storiq/stone.conf');

	$section;
	$conf;

	$db_config = array();

	foreach ($file as $num => $line) {
		if (preg_match('"^;"', $line))
			continue;
		else if (preg_match('"^\[(\w+)\]"', $line, $captures))
			$section = $captures[1];
		else if (preg_match('"(\w+?)\s*=\s*(.+)"', $line, $captures))
			$conf[$captures[1]] = $captures[2];
		else if (preg_match('"^$"', $line)) {
			if ($section == 'database')
				foreach ($conf as $key => $val)
					$db_config[$key] = $val;
			$conf = array();
		}
	}

	if (count($conf) > 0) {
		foreach ($conf as $key => $val)
			$db_config[$key] = $val;
	}

	unset($file, $section, $conf);
?>