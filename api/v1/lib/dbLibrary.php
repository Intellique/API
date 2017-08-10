<?php
	/**
	* \brief Specific interface for Library
	*/
	interface DB_Library {
		/**
		 * \brief Get information of specified changer
		 * \param id : id of changer
		 */
		public function getDevice($id);

		/**
		 * \brief Get a list of device's ids
		 */
		public function getDevices(&$params);
		public function getDevicesByParams(&$params);

		/**
		* \brief Returns Drives by Changer
		* \param $changer_id
		* \return \b an array with drives, \b FALSE when no drive was found
		*/
		public function getDrivesByChanger($changer_id);

		/**
		* \brief Returns Library
		* \return \b an array with libraries, \b FALSE when no library was found
		*/
		public function getPhysicalLibraries();

		/**
		* \brief Returns Slots by Changer
		* \param $changer_id
		* \return \b an array with slots, \b FALSE when no slot was found
		*/
		public function getSlotsByChanger($changer_id);

		/**
		* \brief Returns Slots by Drive
		* \param $drive_id
		* \return \b an array with slots, \b FALSE when no slot was found
		*/
		public function getSlotByDrive($drive_id);

		/**
		* \brief Returns standalone Drives
		* \return \b an array with all standalone drives, \b FALSE when no standalone drive was found
		*/
		public function getStandaloneDrives();

		/**
		* \brief Returns VTL
		* \return \b an array with VTL, \b FALSE when no VTL was found
		*/
		public function getVTLs();

		/**
		* \brief Set a library action
		* \param $id changer id
		* \param $act put online or put offline
		* \return \b TRUE of
		*/
		public function setLibraryAction($id, $act);
	}
?>
