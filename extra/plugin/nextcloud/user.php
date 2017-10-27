<?php
	$occ_path = '/var/www/nextcloud/occ';

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			if (!isset($_GET['login'])) {
				http_response_code(400);
				exit;
			}

			$return = exec(sprintf('%s user:delete %s', $occ_path, escapeshellarg($_GET['login'])));

			if (rtrim($return) == 'The specified user was deleted') {
				http_response_code(204);
				echo $return;
			} else {
				http_response_code(400);
				echo $return;
			}

			exit;

		case 'POST':
			$content = file_get_contents('php://input');
			$user = json_decode($content, true);

			putenv('OC_PASS=' . $user['password']);
			$return = exec(sprintf('%s user:add --password-from-env %s', $occ_path, escapeshellarg($user['login'])));

			if (rtrim($return) == sprintf('The user "%s" was created successfully', $user['login'])) {
				http_response_code(204);
				echo $return;
			} else {
				http_response_code(400);
				echo $return;
			}

			exit;

		case 'PUT':
			$content = file_get_contents('php://input');
			$user = json_decode($content, true);

			if ($user['new password']) {
				putenv('OC_PASS=' . $user['new password']);

				$return = exec(sprintf('%s user:resetpassword --password-from-env %s', $occ_path, $user['login']));
				error_log($return);
				if (rtrim($return) != sprintf('Successfully reset password for %s', $user['login'])) {
					http_response_code(400);
					echo $return;
				}
			}

			if ($user['enable'] != $user['should enable']) {
				$action = $user['should enable'] ? 'enable' : 'disable';

				$return = exec(sprintf('%s user:%s %s', $occ_path, $action, $user['login']));
				error_log($return);
				if (rtrim($return) != sprintf('The specified user is %sd', $action)) {
					http_response_code(400);
					echo $return;
				}
			}

			http_response_code(204);
			exit;
	}
?>
