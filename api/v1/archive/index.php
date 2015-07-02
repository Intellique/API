<?php
/**
 * \addtogroup archive
 * \section Delete_Archive Archive deletion
 * To mark archive as deleted,
 * use \b DELETE method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param id : archive's id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Archive information are returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 404 Archive not found
 *   - \b 500 Query failure
 *
 * \section Archive_ID Archive information
 * To get archive by its ID,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param id : archive's id
 * \return HTTP status codes :
 *   - \b 200 Query succeeded
 *     \verbatim Archive information are returned \endverbatim
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 *
 * \section Archives Archives id,
 * use \b GET method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'uuid', 'name' |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning To get archives ID list do not pass an id as parameter
 * \return HTTP status codes :
 *   - \b 200 Query successfull
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 *
 * \section Create_archival_task Archival task creation
 * To create an archival task,
 * use \b POST method
 * \verbatim path : /storiqone-backend/api/v1/archive/ \endverbatim
 * \param job : hash table
 * \li \c pool id (integer) : pool id
 * \li \c files (JSON) : files to be archived
 * \li \c name (string) : archive name
 * \li \c metadata [optional] (JSON) : archive metadata
 * \li \c date [optional] (JSON) : archival task nextstart date
 * \return HTTP status codes :
 *   - \b 200 Job created successfully
 *     \verbatim New job id is returned \endverbatim
 *   - \b 400 Pool id is required or pool id must be an integer or incorrect input
 *   - \b 401 Not logged in
 *   - \b 403 Permission denied
 *   - \b 500 Query failure
 *
 * \note \ref Date "Date time formats supported"
 */
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'DELETE':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (!$_SESSION['user']['isadmin'])
				httpResponse(403, array('message' => 'Permission denied'));

			if (isset($_GET['id'])) {
				$archive = $dbDriver->getArchive($_GET['id']);
				if ($archive === null)
					httpResponse(500, array('message' => 'Query failure'));
				elseif ($archive === false)
					httpResponse(404, array('message' => 'Archive not found'));

				$archive['deleted'] = true;

				$result = $dbDriver->updateArchive($archive);
				if ($result === null)
					httpResponse(500, array('message' => 'Query failure'));
				elseif ($result === false)
					httpResponse(404, array('message' => 'Archive not found'));
				else
					httpResponse(200, array('message' => 'Archive deleted'));
			}

			break;

		case 'GET':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			if (isset($_GET['id'])) {
				$permission_granted = $dbDriver->checkArchivePermission($_GET['id'], $_SESSION['user']['id']);
				if ($permission_granted === null)
					httpResponse(500, array(
						'message' => 'Query failure',
						'archive' => array()
					));
				elseif ($permission_granted === false)
					httpResponse(403, array('message' => 'Permission denied'));

				$archive = $dbDriver->getArchive($_GET['id']);
				if ($archive === null)
					httpResponse(500, array(
						'message' => 'Query failure',
						'archive' => array()
					));

				httpResponse(200, array(
						'message' => 'Query succeeded',
						'archive' => $archive
				));
			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'uuid', 'name')))
						$params['order_by'] = $_GET['order_by'];
					else
						$ok = false;

					if (isset($_GET['order_asc'])) {
						$is_asc = filter_var($_GET['order_asc'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
						if ($is_asc !== null)
							$params['order_asc'] = $is_asc;
						else
							$ok = false;
					}
				}
				if (isset($_GET['limit'])) {
					if (ctype_digit($_GET['limit']) && $_GET['limit'] > 0)
						$params['limit'] = intval($_GET['limit']);
					else
						$ok = false;
				}
				if (isset($_GET['offset'])) {
					if (ctype_digit($_GET['offset']) && $_GET['offset'] >= 0)
						$params['offset'] = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$result = $dbDriver->getArchives($_SESSION['user']['id'], $params);
				if ($result['query_executed'] == false)
					httpResponse(500, array(
						'message' => 'Query failure',
						'archives' => array(),
						'total_rows' => 0
					));
				else
					httpResponse(200, array(
						'message' => 'Query successfull',
						'archives' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}

			break;

		case 'POST':
			header("Content-Type: application/json; charset=utf-8");

			checkConnected();

			$infoJob = httpParseInput();

			// pool id
			if (!isset($infoJob['pool']))
				httpResponse(400, array('message' => 'Pool id is required'));

			if (!intval($infoJob['pool']))
				httpResponse(400, array('message' => 'Pool id must be an integer'));

			$dbDriver->startTransaction();

			$ok = true;
			$failed = false;

			$job = array(
				'interval' => null,
				'archive' => null,
				'backup' => null,
				'media' => null,
				'options' => array()
			);

			// pool id
			if (isset($infoJob['pool']) && intval($infoJob['pool'])) {
				$checkPoolPermission = $dbDriver->checkPoolPermission($infoJob['pool'], $_SESSION['user']['id']);
				if ($checkPoolPermission === null)
					$failed = true;
				else
					$job['pool'] = intval($infoJob['pool']);
			}

			if (!$_SESSION['user']['canarchive'] || !$checkPoolPermission) {
				$dbDriver->cancelTransaction();
				httpResponse(403, array('message' => 'Permission denied'));
			}

			// files (checking file access)
			$files = $infoJob['files'];
			$ok = isset($files) && is_array($files) && count($files) > 0;
			if ($ok)
				for ($i = 0, $n = count($files); $ok && $i < $n; $i++)
					$ok = is_string($files[$i]) && posix_access($files[$i], POSIX_F_OK);

			// name
			if ($ok)
				$ok = isset($infoJob['name']) && is_string($infoJob['name']);
			if ($ok)
				$job['name'] = $infoJob['name'];

			// type
			if ($ok) {
				$jobType = $dbDriver->getJobTypeId("create-archive");
				if ($jobType === null || $jobType === false)
					$failed = true;
				else
					$job['type'] = $jobType;
			}

			// host
			if ($ok) {
				$host = $dbDriver->getHost(posix_uname()['nodename']);
				if ($host === null || $host === false)
					$failed = true;
				else
					$job['host'] = $host;
			}

			// login
			if ($ok)
				$job['login'] = $_SESSION['user']['id'];

			// metadata [optional]
			if ($ok && isset($infoJob['metadata'])) {
				$metadata = $infoJob['metadata'];
				$ok = is_array($metadata);
				if ($ok)
					$job['metadata'] = $metadata;
			}

			// date [optional]
			if ($ok && isset($infoJob['date'])) {
				$job['nextstart'] = dateTimeParse($infoJob['date']);
				if ($job['nextstart'] === null)
					$ok = false;
			} elseif ($ok)
				$job['nextstart'] = new DateTime();

			// gestion des erreurs
			if ($failed || !$ok)
				$dbDriver->cancelTransaction();

			if ($failed)
				httpResponse(500, array('message' => 'Query failure'));

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$jobId = $dbDriver->createJob($job);

			if ($jobId === null) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			}

			foreach ($files as $file) {

				$selectedfileId = $dbDriver->getSelectedFile($file);

				if ($selectedfileId === null || !$dbDriver->linkJobToSelectedfile($jobId, $selectedfileId)) {
					$failed = true;
					break;
				}
			}

			if ($failed) {
				$dbDriver->cancelTransaction();
				httpResponse(500, array('message' => 'Query failure'));
			}

			$dbDriver->finishTransaction();
			httpResponse(200, array(
				'message' => 'Job created successfully',
				'job_id' => $jobId
			));

			break;

		case 'OPTIONS':
			httpOptionsMethod(HTTP_ALL_METHODS & ~HTTP_PUT);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>
