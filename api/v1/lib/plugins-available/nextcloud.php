<?php
	function plugin_init_nextcloud($plugin_filename, $plugin_realpath) {
		$config_file = dirname($plugin_filename) . '/' . basename($plugin_filename, '.php') . '.ini';
		$config = parse_ini_file($config_file);

		registerPlugin('post DELETE User', function($event, $user) use (&$config) {
			$return = exec(sprintf('%s user:delete %s', $config['occ_path'], escapeshellarg($user['login'])));
			return (rtrim($return) == 'The specified user was deleted');
		});

		registerPlugin('post POST User', function($event, $new_user, $password) use (&$config) {
			putenv('OC_PASS=' . $password);
			$return = exec(sprintf('%s user:add --password-from-env %s', $config['occ_path'], escapeshellarg($new_user['login'])));
			return (rtrim($return) == sprintf('The user "%s" was created successfully', $new_user['login']));
		});

		registerPlugin('post PUT User', function($event, $current_user, $new_user, $current_password) use (&$config) {
			if ($current_password) {
				putenv('OC_PASS=' . $current_password);

				$return = exec(sprintf('%s user:resetpassword --password-from-env %s', $config['occ_path'], $current_user['login']));
				if (rtrim($return) != sprintf('Successfully reset password for %s', $current_user['login']))
					return false;
			}

			if ($current_user['disabled'] != $new_user['disabled']) {
				$action = $new_user['disabled'] ? 'disable' : 'enable';

				$return = exec(sprintf('%s user:%s %s', $config['occ_path'], $action, $user['login']));
				if (rtrim($return) != sprintf('The specified user is %sd', $action))
					return false;
			}

			return true;
		});
	}
?>
