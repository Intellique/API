<?php
	$occ_path = '/var/www/nextcloud/occ';
	$ncdata = '/var/www/nextcloud/data';

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			if (!isset($_GET['login'])) {
				http_response_code(400);
				exit;
			}
			$archivefile = $ncdata . '/' . $_GET['login'] . '/files/to_archive/archive.txt';

			if (file_exists($archivefile)) {
				$archivename = file_get_contents($archivefile, FALSE, NULL, 0, 255);
				$archivefile=rtrim($archivefile);
				
				if (strlen($archivename) < 5 ) {
					$archivename = strftime("%F_%T");					
				} else {
					$archivename = preg_replace('/\W+/', '_', $archivename );
				}
				http_response_code(200);
				echo $archivename;
				
			} else {
				http_response_code(404);
				echo "No file.";
			}
	
			exit;

		case 'POST':
			$content = file_get_contents('php://input');
			$user = json_decode($content, true);

			putenv('OC_PASS=' . $user['password']);
			array($output):
			$return = exec(sprintf('%s files:scan --path=%s', $occ_path, escapeshellarg($user['login']) . '/files/'), $output);

			if (rtrim($output[0]) == sprintf('Starting scan for user 1 out of 1 (%s)', $user['login'])) {
				http_response_code(204);
				echo $output[0];
			} else {
				http_response_code(400);
				echo $output[0];
			}

			exit;
}
?>
