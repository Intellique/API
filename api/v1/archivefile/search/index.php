<?php
/**
 * \addtogroup search
 * \page search
 * \subpage searcharchivefile Search Archive File
 * \section Search_archivefile Searching archive files
 * To search archive files and then to get archive files ids list,
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/archivefile/search \endverbatim

 * <b>Optional parameters</b>
 * |     Name     |  Type   |                                  Description                                        |           Constraint            |
 * | :----------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | name         | string  | search an archive file specifying its name                                          |                                 |
 * | archive      | integer | search an archive file given the archive id                                         |                                 |
 * | archive_name | string  | search an archive file given the archive name                                       |                                 |
 * | mimetype     | string  | search an archive file specifying its mime type                                     |                                 |
 * | order_by     | enum    | order by column                                                                     | value in : 'id', 'size', 'name', 'type', 'owner', 'groups' |
 * | order_asc    | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit        | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset       | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 * | size         | integer | search archive files given the size                                                 | size >= 0                       |
 * | size_inf     | integer | search archive files given the minimum size                                         | size_inf >= 0  & > size_sup     |
 * | size_sup     | integer | search archive files given the maximum size                                         | size_sup >= 0  & < size_inf     |
 * | date         | date    | search archive files given the date                                                 | valid date                      |
 * | date_inf     | date    | search archive files given the minimum date                                         | valid date & date_inf > date_sup |
 * | date_sup     | date    | search archive files given the maximum date                                         | valid date & date_sup < date_inf |
 * | version      | integer | search archive files given the version                                              | version >= 0                     |
 * | version_inf  | integer | search archive files given the minimum version                                      | version_inf >= 0 & > version_sup |
 * | version_sup  | integer | search archive files given the maximum version                                      | version_sup >= 0 & < version_inf |
 * | status       | string  | search archive files given the status                                               | status = checked || status = not_checked || status = not_ok |
 * \warning <b>Make sure to pass at least one of the first four parameters. Otherwise, do not pass them to get the complete list.</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Archive files ids list is returned
{
   {
   "message":"Query successful","archivefiles":[2]
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 404 Archivefiles not found
 *   - \b 500 Query failure
 */
	require_once("../../lib/env.php");

	require_once("http.php");
	require_once("session.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			$params = array();
			$ok = true;

			if (isset($_GET['name']))
				$params['name'] = $_GET['name'];

			if (isset($_GET['archive'])) {
				$archive = filter_var($_GET['archive'], FILTER_VALIDATE_INT);
				if ($archive !== false)
					$params['archive'] = $archive;
				else
					$ok = false;
			}

			if (isset($_GET['type']))
				$params['type'] = $_GET['type'];

			if (isset($_GET['mimetype']))
				$params['mimetype'] = $_GET['mimetype'];

			if (isset($_GET['archive_name']))
				$params['archive_name'] = $_GET['archive_name'];

			/* SIZE */

			if (isset($_GET['size'])) {
				$size = filter_var($_GET['size'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 0)));
				if ($size !== false)
					$params['size'] = $size;
				else
					$ok = false;
			} else {
				if (isset($_GET['size_sup'])) {
					$size_sup = filter_var($_GET['size_sup'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 0)));
					if ($size_sup !== false)
						$params['size_sup'] = $size_sup;
					else
						$ok = false;
				}
				if (isset($_GET['size_inf'])) {
					$size_inf = filter_var($_GET['size_inf'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 0)));
					if ($size_inf !== false)
						$params['size_inf'] = $size_inf;
					else
						$ok = false;
				}
				if(isset($_GET['size_sup']) && isset($_GET['size_inf']) && $size_sup > $size_inf)
					$ok = false;
			}

			/* DATE */
			if (isset($_GET['date'])) {
				$date = dateTimeParse($_GET['date']);
				if ($date !== null)
					$params['date'] = $_GET['date'];
				else
					$ok = false;
			} else {
				if (isset($_GET['date_inf'])) {
					$date_inf = dateTimeParse($_GET['date_inf']);
					if ($date_inf !== null)
						$params['date_inf'] = $_GET['date_inf'];
					else
						$ok = false;
				}
				if (isset($_GET['date_sup'])) {
					$date_sup = dateTimeParse($_GET['date_sup']);
					if ($date_sup !== null)
						$params['date_sup'] = $_GET['date_sup'];
					else
						$ok = false;
				}
				if (isset($_GET['date_sup']) && isset($_GET['date_inf']) && ($date_sup->getTimestamp() - $date_inf->getTimestamp()) > 0)
					$ok = false;
			}

			/* VERSION */

			if (isset($_GET['version'])) {
				$version = filter_var($_GET['version'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 1)));
				if ($version !== false)
					$params['version'] = $version;
				else
					$ok = false;
			} else {
				if (isset($_GET['version_sup'])) {
					$version_sup = filter_var($_GET['version_sup'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 1)));
					if ($version_sup !== false)
						$params['version_sup'] = $version_sup;
					else
						$ok = false;
				}
				if (isset($_GET['version_inf'])) {
					$version_inf = filter_var($_GET['version_inf'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 1)));
					if ($version_inf !== false)
						$params['version_inf'] = $version_inf;
					else
						$ok = false;
				}
				if(isset($_GET['version_sup']) && isset($_GET['version_inf']) && $version_sup > $version_inf)
					$ok = false;
			}

			if (isset($_GET['order_by'])) {
				if (array_search($_GET['order_by'], array('id', 'size', 'name', 'type', 'owner', 'groups')) !== false)
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
				$limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 1)));
				if ($limit !== false)
					$params['limit'] = $limit;
				else
					$ok = false;
			}

			if (isset($_GET['offset'])) {
				$offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT, array("options" => array('min_range' => 0)));
				if ($offset !== false)
					$params['offset'] = $offset;
				else
					$ok = false;
			}


			/* ETAT DE VERIFICATION */

			if (isset($_GET['status'])) {
				switch ($_GET['status']) {
					case 'checked':
					case 'not_checked':
					case 'not_ok':
						$params['status'] = $_GET['status'];
						break;
					default:
						$ok = false;
						break;
				}
			}

			if (!$ok)
				httpResponse(400, array('message' => 'Incorrect input'));

			$archivefile = $dbDriver->getArchiveFilesByParams($params, $_SESSION['user']['id']);
			if (!$archivefile['query_executed']) {
				$dbDriver->writeLog(DB::DB_LOG_CRITICAL, sprintf('GET api/v1/archivefile/search (%d) => Query failure', __LINE__), $_SESSION['user']['id']);
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('GET api/v1/archivefile/search (%d) => getArchiveFilesByParams(%s)', __LINE__, var_export($params, true)), $_SESSION['user']['id']);
				httpResponse(500, array(
					'message' => 'Query failure',
					'archivefiles' => array(),
					'total_rows' => 0
				));
			} elseif ($archivefile['total_rows'] === 0)
				httpResponse(404, array(
					'message' => 'Archivefiles not found',
					'archivefiles' => array(),
					'total_rows' => 0
				));

			httpResponse(200, array(
				'message' => 'Query succeeded',
				'archivefiles' => $archivefile['rows'],
				'total_rows' => $archivefile['total_rows']
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
