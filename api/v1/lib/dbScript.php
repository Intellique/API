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

		/**
		 * \brief Get all scripts by pool
		 * \param $id : an id
		 * \return the id and path of all scripts on failure
		 */
		public function getScriptsByPool($id);

		/**
		 * \brief Get th sequence if a script exist
		 * \param $params : script_id, pool, jobtype
		 * \return true or the sequence
		 */
		public function scriptExist(&$params);

		/**
		 * \brief add a script
		 * \param $params : script_id, pool, jobtype
		 * \return added message
		 */
		public function addScript(&$params);

		/**
		 * \brief delete a script
		 * \param $params : script_id, pool, jobtype
		 * \return deleted message
		 */
		public function deleteScript(&$params);
	}
?>
