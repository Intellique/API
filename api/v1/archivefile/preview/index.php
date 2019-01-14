<?php
	require_once("../../lib/env.php");

	require_once("conf.php");
	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
		case 'HEAD':
			checkConnected();

			if (!isset($_GET['id']))
				httpResponse(400, array('message' => 'archivefile\'s id required'));

			if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false)
				httpResponse(400, array('message' => 'archivefile\'s id must be an integer'));

			$checkArchiveFilePermission = $dbDriver->checkArchiveFilePermission($_GET['id'], $_SESSION['user']['id']);
			if ($checkArchiveFilePermission === null)
				httpResponse(500, array('message' => 'Query failure'));
			elseif ($checkArchiveFilePermission === false)
				httpResponse(403, array('message' => 'Permission denied'));

			$archivefile = $dbDriver->getArchiveFile($_GET['id']);
			if ($archivefile === null)
				httpResponse(500, array('message' => 'Query failure'));
			elseif ($archivefile === false)
				httpResponse(404, array('message' => 'Not found'));

			if (isset($_GET['type'])) {
				$mimetype = array("image/jpeg" => ".jpg", "video/mp4" => ".mp4", "video/ogg" => ".ogv", "audio/mpeg" => ".m4a");

				if (!isset($mimetype[$_GET['type']]))
					httpResponse(400, array('message' => 'type must be in "' . implode('", "', array_keys($mimetype)) . '"'));

				$found = false;
				$paths = array($proxy_config['movie path'], $proxy_config['picture path'], $proxy_config['sound path']);
				foreach ($paths as &$path) {
					$filename = $path . md5($archivefile['name']) . $mimetype[$_GET['type']];
					if (posix_access($filename)) {
						$found = true;
						break;
					}
				}

				if (!$found)
					httpResponse(404, array('message' => 'Not found'));

				$file_info = stat($filename);

				header('Content-Type: ' . $_GET['type']);
				header('Content-Length: ' . $file_info['size']);
			} elseif (posix_access($archivefile['name'])) {
				$filename = $archivefile['name'];

				header('Content-Type: ' . $archivefile['mimetype']);
				header('Content-Length: ' . $archivefile['size']);
			} else
				httpResponse(404, array('message' => 'Not found'));


			if ($_SERVER['REQUEST_METHOD'] == 'GET')
				readfile($filename);

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
