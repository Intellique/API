<?php
	/**
	* \brief Specific interface for Library
	*/
	interface DB_Library {
		/**
		 * \brief Create a vtl
		 * \param vtl : attributs of new VTL
		 */
		public function createVTL(&$vtl);

		/**
		 * \brief Mark a vtl as deleted
		 * \param id : id of VTL
		 */
		public function deleteVTL($id);

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
		 * \param $id : VTL's id
		 * \param $rowLock : put a lock on archive with id $id
		* \return \b an array with VTL, \b FALSE when no VTL was found
		*/
		public function getVTL($id, $rowLock = DB::DB_ROW_LOCK_NONE);

		/**
		* \brief Returns VTLs
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

		/**
		 * \brief Update a VTL
		 * \param vtl : new attributs of VTL
		 */
		public function updateVTL($vtl);
	}
?>
