<?php

/**
 * \addtogroup MediaFormat Media Format
 * \page mediaformat
 * \section MediaFormat Media Format
 * \subsection MediaFormatBrief How does it work?
 * If the user inputs an \e id, the function returns information concerning the corresponding media format, regardless of the other parameters.
 * \n Else if the user only inputs a \e name, the id of the corresponding media format is returned.
 * \n Else (user leaves both fields blank), an array of supported formats is returned.
 *
 * \subsection MediaFormatID When inputting an ID
 * To get an media format by its \e id
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/mediaformat/?id=<integer> \endverbatim
 * \param id : id of an existing media format
 *
 * Example of request :
 * \verbatim GET http://api.storiqone/storiqone-backend/api/v1/mediaformat/?id=1 \endverbatim
 * Response :
 * \verbatim
 {
   "mediaformat" : {
      "blocksize" : 32768,
      "capacity" : 2620446998528,
      "datatype" : "data",
      "densitycode" : 90,
      "id" : 1,
      "lifespan" : "10 years",
      "maxloadcount" : 4096,
      "maxopcount" : 40960,
      "maxreadcount" : 40960,
      "maxwritecount" : 40960,
      "mode" : "linear",
      "name" : "LTO-6",
      "supportmam" : true,
      "supportpartition" : true
   },
   "message" : "Query succeeded"
}
  \endverbatim
 *
 *
 * \subsection MediaFormatName When only inputting a name
 * To get an media format id by its \e name
 * use \b GET method :
 * \verbatim path : /storiqone-backend/api/v1/mediaformat/?name=<string> \endverbatim
 * \param name : name of an existing media format
 *
 * Example of request :
 * \verbatim GET http://api.storiqone/storiqone-backend/api/v1/mediaformat/?name=LTFS \endverbatim
 * Response :
 * \verbatim
 {
	"message":"Query succeeded",
	"mediaformat id":2
 }
 \endverbatim
 *
 *
 *\subsection MediaFormatTab Returning an array of supported formats
 * To get media format id list,
 * use \b GET method : <i>without reference to specific id or ids</i>
 * \verbatim path : /storiqone-backend/api/v1/mediaformat/ \endverbatim
 * <b>Optional parameters</b>
 * |   Name    |  Type   |                                  Description                                        |           Constraint            |
 * | :-------: | :-----: | :---------------------------------------------------------------------------------: | :-----------------------------: |
 * | order_by  | enum    | order by column                                                                     | value in : 'id', 'name'         |
 * | order_asc | boolean | \b TRUE will perform an ascending order and \b FALSE will perform an descending order. \n order_asc is ignored if order_by is missing. | |
 * | limit     | integer | specifies the maximum number of rows to return.                                     | limit > 0                       |
 * | offset    | integer | specifies the number of rows to skip before starting to return rows.                | offset >= 0                     |
 *
 * \warning <b>To get multiple media ids list do not pass an id or ids as parameter</b>
 * \return HTTP status codes :
 *   - \b 200 Query succeeded

 *     \verbatim Media format id list is returned
{
   "media formats" : [
      1,
      2,
      3,
      4,
      5,
      6,
      7
   ],


   "message":"Query successful",
   "total_rows" : 7

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
					httpResponse(400, array('message' => 'Mediaformat id must be an integer'));

				$mediaformat = $dbDriver->getMediaFormat($_GET['id']);
				if ($mediaformat === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/mediaformat => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormat(%s)', $_GET['id']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query Failure',
						'mediaformat' => null
					));
				} elseif ($mediaformat === false)
					httpResponse(404, array (
						'message' => 'Media format not found',
						'mediaformat' => NULL
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'mediaformat' => $mediaformat
				));

			} elseif (isset($_GET['name'])) {
				$mediaformat = $dbDriver->getMediaFormatByName($_GET['name']);

				if ($mediaformat === NULL) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/mediaformat => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormatByName(%s)', $_GET['name']), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query Failure',
						'mediaformat id' => null
					));
				} elseif ($mediaformat === false)
					httpResponse(404, array (
						'message' => 'Media format not found',
						'mediaformat id' => NULL
					));

				httpResponse(200, array(
					'message' => 'Query succeeded',
					'mediaformat id' => $mediaformat
				));


			} else {
				$params = array();
				$ok = true;

				if (isset($_GET['order_by'])) {
					if (array_search($_GET['order_by'], array('id', 'name', 'capacity')) !== false)
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

				$result = $dbDriver->getMediaFormats($params);
				if ($result['query_executed'] == false) {
					$dbDriver->writeLog(DB::DB_LOG_CRITICAL, 'GET api/v1/mediaformat => Query failure', $_SESSION['user']['id']);
					$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('getMediaFormats(%s)', $params), $_SESSION['user']['id']);
					httpResponse(500, array(
						'message' => 'Query failure',
						'media formats' => array(),
						'total_rows' => 0
					));
				} else
					httpResponse(200, array(
						'message' => 'Query successful',
						'media formats' => $result['rows'],
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
