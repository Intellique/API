<?php
/**
* \addtogroup library
* \page library
* \section Library Library information
* use \b GET method
* \verbatim path : /storiqone-backend/api/v1/library/ \endverbatim
* \return HTTP status codes :
*   - \b 200 Query succeeded
*     \verbatim Device information is returned \endverbatim
*   - \b 401 Not logged in
*   - \b 403 Permission denied
*   - \b 404 Device not found
*   - \b 500 Query failure
* use \b PUT method
* \param id : library id
* \param nextAction : putOnline or putOffline
* \section Library library ids (multiple list)
* \verbatim path : /storiqone-backend/api/v1/library/ \endverbatim
*/
	require_once("../lib/env.php");

	require_once("dateTime.php");
	require_once("http.php");
	require_once("session.php");
	require_once("uuid.php");
	require_once("db.php");

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			checkConnected();

			if (!$_SESSION['user']['isadmin'] || !$_SESSION['user']['canarchive'] || !$_SESSION['user']['canrestore']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('GET api/v1/library (%d) => Permission denied for a non-admin/archiver/restorer user', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			//REQ SQL permettant de lister les infos sur une librairie physique
			$physicalDevices = $dbDriver->getPhysicalLibraries();
			if (!empty($physicalDevices)) {
				foreach ($physicalDevices as &$physicalDevice) {
					$physicalDevice['drives'] = $dbDriver->getDrivesByChanger($physicalDevice['id']);
					foreach ($physicalDevice['drives'] as &$drive) {
						$drive['slot'] = $dbDriver->getSlotByDrive($drive['id']);

						if (isset($drive['slot']['media']))
							$drive['slot']['media'] = $dbDriver->getMedia($drive['slot']['media']);
					}

					$physicalDevice['slots'] = $dbDriver->getSlotsByChanger($physicalDevice['id']);
					foreach ($physicalDevice['slots'] as &$slot)
						if (isset($slot['media']))
							$slot['media'] = $dbDriver->getMedia($slot['media']);
				}
			}

			//REQ SQL permettant de lister les infos sur un lecteur autonome
			$standaloneDevices = $dbDriver->getStandaloneDrives();
			if (!empty($standaloneDevices))
				foreach ($standaloneDevices as &$standaloneDevice) {
					$standaloneDevice['slot'] = $dbDriver->getSlotByDrive($standaloneDevice['driveid']);

					if (isset($standaloneDevice['slot']['media']))
						$standaloneDevice['slot']['media'] = $dbDriver->getMedia($standaloneDevice['slot']['media']);
				}

			//REQ SQL permettant de lister les infos sur une Virtual Tape Library
			$VTLs = $dbDriver->getVTLs();
			if (!empty($VTLs)) {
				foreach ($VTLs as &$VTL) {
					$VTL['drives'] = $dbDriver->getDrivesByChanger($VTL['changerid']);
					foreach ($VTL['drives'] as &$drive)
						$drive['slot'] = $dbDriver->getSlotByDrive($drive['driveid']);
					$VTL['slots'] = $dbDriver->getSlotsByChanger($VTL['changerid']);
				}
			}

			$result_periphs = array(
				'Tape Library' => array_values($physicalDevices),
				'External Tape Drive' => array_values($standaloneDevices),
				'Virtual Tape Library' => array_values($VTLs),
			);

			if (count($physicalDevices) + count($standaloneDevices) + count($VTLs) == 0) {
				httpResponse(404, array(
					'message' => 'Device not found',
					'Tape Library' => NULL,
					'External Tape Drive' => NULL,
					'Virtual Tape Library' => NULL,
				));
			}

			httpResponse(200, array(
				'message' => 'Query succeeded',
				'Tape Library' => array_values($physicalDevices),
				'External Tape Drive' => array_values($standaloneDevices),
				'Virtual Tape Library' => array_values($VTLs),
			));

		case 'PUT':
			checkConnected();

			if (!$_SESSION['user']['isadmin'] || !$_SESSION['user']['canarchive'] || !$_SESSION['user']['canrestore']) {
				$dbDriver->writeLog(DB::DB_LOG_WARNING, sprintf('PUT api/v1/library (%d) => Permission denied for a non-admin/archiver/restorer user', __LINE__), $_SESSION['user']['id']);
				httpResponse(403, array('message' => 'Permission denied'));
			}

			$input = httpParseInput();

			//REQ SQL permettant de modifier l'action de la librairie physique
			if (isset($input['id'])) {
				if (!is_int($input['id']))
					httpResponse(400, array('message' => 'Incorrect input'));
				$id = $input['id'];
			} else {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/library => id is required'), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'library id is required'));
			}

			if (isset($input['action'])) {
				if ($input['action'] != 'put online' && $input['action'] != 'put offline')
					httpResponse(400, array('message' => 'Incorrect input'));
				$action = $input['action'];
			} else {
				$dbDriver->writeLog(DB::DB_LOG_DEBUG, sprintf('PUT api/v1/library => action is required'), $_SESSION['user']['id']);
				httpResponse(400, array('message' => 'library action is required'));
			}

			$reponse = $dbDriver->setLibraryAction($id, $action);
			$result = array('isUpdate' => $reponse);
			httpResponse(200, array(
				'message' => 'Query succeeded',
				'return' => $reponse
			));

			break;
	}
?>
