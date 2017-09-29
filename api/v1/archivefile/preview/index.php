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
			if ($checkArchiveFilePermission === null)
				httpResponse(500, array('message' => 'Query failure'));
			elseif ($checkArchiveFilePermission === false)
				httpResponse(404, array('message' => 'Not found'));

			if (isset($_GET['type'])) {
				$mimetype = array("video/mp4" => ".mp4", "video/ogg" => ".ogv");

				if (!isset($mimetype[$_GET['type']]))
					httpResponse(400, array('message' => 'type must be in "' . implode('", "', array_keys($mimetype)) . '"'));

				$filename = $proxy_config['path'] . md5($archivefile['name']) . $mimetype[$_GET['type']];
				if (!posix_access($filename))
					httpResponse(404, array('message' => 'Not found'));

				$file_info = stat($filename);

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
