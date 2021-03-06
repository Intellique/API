<?php
	function plugin_init_nextcloud_docker($plugin_filename, $plugin_realpath) {
		$config_file = dirname($plugin_filename) . '/' . basename($plugin_filename, '.php') . '.ini';
		$config = parse_ini_file($config_file);

		registerPlugin('post DELETE User', function($event, $user) use (&$config) {
			$url = sprintf('%s%s:%d%s/user.php?login=%s', $config['scheme'], $config['host'], $config['port'], $config['base_path'], $user['login']);

			$ctx = curl_init();
			curl_setopt_array($ctx, array(
				CURLOPT_CUSTOMREQUEST => 'DELETE',
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_URL => $url
			));
			$response = curl_exec($ctx);
			curl_close($ctx);

			return $response == true;
		});

		registerPlugin('post POST User', function($event, $new_user, $password) use (&$config) {
			$url = sprintf('%s%s:%d%s/user.php', $config['scheme'], $config['host'], $config['port'], $config['base_path']);

			$data = json_encode(array(
				'login' => $new_user['login'],
				'password' => $password
			));

			$ctx = curl_init();
			curl_setopt_array($ctx, array(
				CURLOPT_HEADER => false,
				CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $data,
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_URL => $url
			));
			$response = curl_exec($ctx);
			curl_close($ctx);

			return $response == true;
		});

		registerPlugin('post PUT User', function($event, $current_user, $new_user, $current_password) use (&$config) {
			$url = sprintf('%s%s:%d%s/user.php', $config['scheme'], $config['host'], $config['port'], $config['base_path']);

			$data = json_encode(array(
				'login' => $new_user['login'],
				'enable' => !$current_user['disabled'],
				'should enable' => !$new_user['disabled'],
				'new password' => $current_password
			));

			$ctx = curl_init();
			curl_setopt_array($ctx, array(
				CURLOPT_CUSTOMREQUEST => 'PUT',
				CURLOPT_HEADER => false,
				CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
				CURLOPT_POSTFIELDS => $data,
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_URL => $url
			));
			$response = curl_exec($ctx);
			curl_close($ctx);

			return $response == true;
		});
	}
?>
