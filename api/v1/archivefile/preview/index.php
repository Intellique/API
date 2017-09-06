<?php
	require_once("../../lib/env.php");

	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
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
			elseif ($checkArchiveFilePermission === false || !posix_access($archivefile['name']))
				httpResponse(404, array('message' => 'Not found'));

			header('Content-Type: ' . $archivefile['mimetype']);
			header('Content-Length: ' . $archivefile['size']);

			readfile($archivefile['name']);

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
