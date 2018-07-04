<?php
	interface DB_Script {
		/**
		 * \brief Get script by its id
		 * \param $id : an id
		 * \return the path of the script on failure
		 */
		public function getScriptById($id);

		/**
		 * \brief Get all scripts
		 * \return the id and path of all scripts on failure
		 */
		public function getScripts();
	}
?>