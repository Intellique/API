<?php
	function plugin_init_storiqone_client($plugin_filename, $plugin_realpath) {
		$config_file = str_replace('.php', '.ini', $plugin_filename);
		$config = parse_ini_file($config_file);

		function loadDb($path) {
			if (posix_access($path) && posix_access($path . '.tmp')) {
				if (!unlink($path))
					return false;
			}

			if (!posix_access($path) && posix_access($path . '.tmp')) {
				if (!rename($path . '.tmp', $path))
					return false;
			}

			return json_decode(file_get_contents($path), true);
		}

		function saveDb(&$db, $path) {
			$new_db = json_encode($db);

			$fd = fopen($path . '.tmp', 'w');
			if ($fd === false)
				return false;

			if (fwrite($fd, $new_db) === false)
				return false;

			if (fclose($fd) === false)
				return false;

			return unlink($path) && rename($path . '.tmp', $path);
		}

		registerPlugin('post DELETE User', function($event, $user) use (&$config) {
			$db = loadDb($config['db_path']);
			if ($db === false)
				return false;

			if (array_key_exists($user['login'], $db))
				unset($db[$user['login']]);

			return saveDb($db, $config['db_path']);
		});

		registerPlugin('post POST User', function($event, $new_user, $password) use (&$config) {
			$db = loadDb($config['db_path']);
			if ($db === false)
				return false;

			$new_user['password'] = $password;
			$db[$new_user['login']] = $new_user;

			return saveDb($db, $config['db_path']);
		});

		registerPlugin('post PUT User', function($event, $current_user, $new_user, $current_password) use (&$config) {
			$db = loadDb($config['db_path']);
			if ($db === false)
				return false;

			$old_password = $db[$current_user['login']]['password'];
			$db[$current_user['login']] = array_merge($db[$current_user['login']], $new_user);
			$db[$current_user['login']]['password'] = $current_password !== null ? $current_password : $old_password;

			return saveDb($db, $config['db_path']);
		});
	}
?>
