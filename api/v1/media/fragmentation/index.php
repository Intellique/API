<?php
/**
 * \addtogroup media
 * \page media
 * \subpage fragmentation Media Fragmentation
 * \section Fragmentation Fragmentation
 * To do a fragmentation of a media,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/media/fragmentation/ \endverbatim
  * \param id : media id (integer)
 * \return HTTP status codes :
 *   - \b 200 Querry Succeeded
 *     \verbatim Fragmentation is returned
 {
"message":"
   'media_size' => 300,
   'volume_used' => 50,
   'volume_wasted' => 0,
   'volume_free' => 250
"}
      \endverbatim
 *   - \b 400 Bad request - Either ; archive id is required or archive id must be an integer or archive not found or incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 */


	require_once("../../lib/env.php");
	require_once("http.php");
	require_once("session.php");
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (!$_SESSION['user']['isadmin']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'Permission denied', $_SESSION['user']['id']);
				httpResponse(403, array('message'  => 'Permission denied'));
			}

			if (!isset($_GET['id'])) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, 'Trying to select a media without specifying a media id', $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'Media ID required'));
			}

			if (!is_numeric($_GET['id']))
				httpResponse(400, array('message' => 'Media id must be an integer'));

			$media = $dbDriver->getMedia($_GET['id']);
			if ($media === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMedia(%s)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'media' => null
				));
			} elseif ($media === false)
				httpResponse(404, array(
					'message' => 'Media not found',
					'media' => null
				));

			$archive_ids= $dbDriver->getArchivesByMedia($_GET['id']);
			if ($archive_ids === null) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/media => Query failure', $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMedia(%s)', $_GET['id']), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'media' => null
				));
			}

			$result = array(
				'media_size' => $media['totalblock'] * $media['blocksize'],
				'volume_used' => 0,
				'volume_wasted' => 0,
				'volume_free' => $media['freeblock'] * $media['blocksize']
			);

			foreach ($archive_ids as &$archive_id) {
				$archive = $dbDriver->getArchive($archive_id);

				foreach ($archive['volumes'] as &$volume) {
					if ($volume['media'] != $media['id'])
						continue;

					if ($archive['deleted'])
						$result['volume_wasted'] += $volume['size'];
					else
						$result['volume_used'] += $volume['size'];
				}
			}

			httpResponse(200, array(
				'message' => 'Query succeeded',
				'media_fragmentation' => &$result
			));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
