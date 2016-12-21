<?php
	/**
	* \file conf.php
	* Script de lecture du fichier de configuration contenant les informations de la base.
	*/

	$file = file_get_contents('/etc/storiq/storiqone.conf');
	$config=json_decode($file, true);

	$db_config = array();

	$db_config["driver"]   = $config['database'][0]['type'] ;
	$db_config["host"]     = $config['database'][0]['host'] ;
	$db_config["db"]       = $config['database'][0]['db'] ;
	$db_config['user']     = $config['database'][0]['user'] ;
	$db_config['password'] = $config['database'][0]['password'] ;
	$db_config['port']     = isset($config['database'][0]['port']) ? $config['database'][0]['port'] : null;

	unset($file);
