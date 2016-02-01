<?php

/**
 * \addtogroup ArchiveFormat Archive Format
 * \section ArchiveFormat Archive Format
 * \subsection ArchiveFormatBrief How does it work?
 * If the user inputs an \e id, the function returns information concerning the corresponding archive format, regardless of the other parameters.
 * \n Else if the user only inputs a \e name, the id of the corresponding archive format is returned.
 * \n Else (user leaves both fields blank), an array of supported formats is returned.
 *
 * \subsection ArchiveFormatID When inputting an ID
 * To get an archive format by its \e id
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/archiveformat/?id=<integer> \endverbatim
 * \param id : id of an existing archive format
 *
 * Example of request :
 * \verbatim GET http://api.storiqone/storiqone-backend/api/v1/archiveformat/?id=1 \endverbatim
 * Response :
 * \verbatim
 {
    "message": "Query succeeded",
    "archiveformat": {
       "id": 1,
       "name": "Storiq One",
       "readable": true,
       "writable":true
    }
  \endverbatim
 *
 *
 * \subsection ArchiveFormatName When only inputting a name
 * To get an archive format id by its \e name
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/archiveformat/?name=<string> \endverbatim
 * \param name : name of an existing archive format
 *
 * Example of request :
 * \verbatim GET http://api.storiqone/storiqone-backend/api/v1/archiveformat/?name=LTFS \endverbatim
 * Response :
 * \verbatim
 {
	"message":"Query succeeded",
	"archiveformat id":2
 }
 \endverbatim
 *
 *
 *\subsection FormatTab Returning an array of supported formats
 * To get archive format id list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/archiveformat/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'name'         |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple archives ids list do not pass an id or ids as parameter</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Archive format id list is returned
{
   {
   "message":"Query successful"
   }
}\endverbatim
 *   - \b 400 Incorrect input
 *   - \b 401 Not logged in
 *   - \b 500 Query failure
 */

	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("dbArchive.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			if (isset($_GET['id'])) {
				if (!is_numeric($_GET['id']))
					httpResponse(400, array('message' => 'Archiveformat id must be an integer'));

				$archiveformat = $dbDriver->getArchiveFormat($_GET['id']);
				if ($archiveformat === NULL)
					httpResponse(500, array(
						'message' => 'Query Failure',
						'archiveformat' => null
					));
				elseif ($archiveformat === false)
					httpResponse(404, array (
						'message' => 'Archive format not found',
						'archiveformat' => NULL
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'archiveformat' => $archiveformat
				));
			} elseif (isset($_GET['name'])) {
				$archiveformat = $dbDriver->getArchiveFormatByName($_GET['name']);

				if ($archiveformat === NULL)
					httpResponse(500, array(
						'message' => 'Query Failure',
						'archiveformat id' => null
					));
				elseif ($archiveformat === false)
					httpResponse(404, array (
						'message' => 'Archive format not found',
						'archiveformat id' => NULL
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'archiveformat id' => $archiveformat
				));
			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'name')) !== false)
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
					if (is_numeric($_GET['limit']) && $_GET['limit'] > 0)
						$params['limit'] = intval($_GET['limit']);
					else
						$ok = false;
				}
				if (isset($_GET['offset'])) {
					if (is_numeric($_GET['offset']) && $_GET['offset'] >= 0)
						$params['offset'] = intval($_GET['offset']);
					else
						$ok = false;
				}

				if (!$ok)
					httpResponse(400, array('message' => 'Incorrect input'));

				$result = $dbDriver->getArchiveFormats($params);
				if ($result['query_executed'] == false)
					httpResponse(500, array(
						'message' => 'Query failure',
						'archive formats' => array(),
						'total_rows' => 0
					));
				else
					httpResponse(200, array(
						'message' => 'Query successful',
						'archive formats' => $result['rows'],
						'total_rows' => $result['total_rows']
					));
			}


		case 'OPTIONS':
			httpOptionsMethod(HTTP_GET);
			break;

		default:
			httpUnsupportedMethod();
			break;
	}
?>