<?php
	/**
	* \file conf.php
	* Script de lecture du fichier de configuration contenant les informations de la base.
	*/

	$file = file_get_contents('/etc/storiq/storiqone.conf');
	$config = json_decode($file, true);

	$db_config = array(
		"driver"   => $config['database'][0]['type'],
		"host"     => $config['database'][0]['host'],
		"db"       => $config['database'][0]['db'],
		"user"     => $config['database'][0]['user'],
		"password" => $config['database'][0]['password'],
		"port"     => isset($config['database'][0]['port']) ? $config['database'][0]['port'] : null
	);

	$proxy_config = array(
		"path" => $config['proxy']['path']
	);

	unset($file, $config);
?>
