<?php
	interface DB_Pool {
		/**
		 * \brief Create a pool
		 * \param $pool : a pool
		 * \return pool id or NULL on failure
		 */
		public function createPool(&$pool);

		/**
		 * \brief Create a pool mirror
		 * \param $poolmirror : a pool mirror
		 * \return pool mirror id or NULL on failure
		 */
		public function createPoolMirror(&$poolmirror);

		/**
		 * \brief Create a pool template
		 * \param $pooltemplate : a pool template
		 * \return pool template id or NULL on failure
		 */
		public function createPoolTemplate(&$pooltemplate);

		/**
		 * \brief Delete a pool mirror
		 * \param $id : a pool mirror's id
		 * \return \b true if succeed or NULL on failure
		 */
		public function deletePoolMirror($id);

		/**
		 * \brief Delete a pool template
		 * \param $id : a pool template's id
		 * \return \b true if succeed or NULL on failure
		 */
		public function deletePoolTemplate($id);

		/**
		 * \brief Get a pool by its id
		 * \param $id : pool id
		 * \param $rowLock : put a lock on pool with id $id
		 * \return media format information
		 * \note No permission check will be performed
		 */
		public function getPool($id, $rowLock = DB::DB_ROW_LOCK_NONE);

		/**
		 * \brief Get a pool by its name
		 * \param $name : pool name
		 * \return pool id or false if not found
		 */
		public function getPoolByName($name);

		/**
		 * \brief Get a pools ids list by an array of parameters
		 * \param $params : optional parameters
		 * \return an array of pool ids or false if not found
		 */
		public function getPoolsByParams(&$params);

		/**
		 * \brief Get pool(s) assigned to the poolmirror id
		 * \return <b>Pool id list</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getPoolsByPoolMirror($id, $uuid);

		/**
		 * \brief Get a pools ids list by its user poolgroup
		 * \param $user_poolgroup : user poolgroup id
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getPoolsByPoolgroup($user_poolgroup, &$params);

		/**
		 * \brief Get a pool mirror by its id
		 * \param $id : id of pool mirror
		 * \return pool mirror information
		 */
		public function getPoolMirror($id);

		/**
		 * \brief Get a list of pool mirror
		 * \param $params : optional parameters
		 * \return pool mirrors information
		 */
		public function getPoolMirrors(&$params);

		/**
		 * \brief Get a list of pool template
		 * \param $params : optional parameters
		 * \return pool templates information
		 */
		public function getPoolTemplates(&$params);

		/**
		 * \brief Get a pool template by its name
		 * \param $name : pool template's name
		 * \return pool template if or \b false if not found
		 */
		public function getPoolTemplateByName($name);

		/**
		 * \brief Update a pool
		 * \param $media : a pool
		 * \return \b NULL on failure, \b False if no pool was updated or \b TRUE on success
		 */
		public function updatePool(&$pool);
	}
?>
