<?php
	/**
	 * \brief Specific interface for archive object
	 */
	interface DB_Archive {
		/**
		 * \brief Check if \i archiveA and \i archiveB have a common archive mirror
		 * \param $archiveA : id of the first archive
		 * \param $archiveB : id of the second archive
		 * \return true if there is a common archive mirror, false otherwise, or null if error
		 */
		public function checkArchiveMirrorInCommon($archiveA, $archiveB);

		/**
		 * \brief Get an archive by its id
		 * \param $id : archive id
		 * \param $rowLock : put a lock on archive with id $id
		 * \return archive information
		 * \note No permission check will be performed
		 */
		public function getArchive($id, $rowLock = DB::DB_ROW_LOCK_NONE);

		/**
		 * \brief Get archives ids list for user \em $user_id
		 * \param $user : a user information
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getArchives(&$user, &$params);

		/**
		 * \brief Get a list of archive ids by media id
		 * \param $id : media id
		 * \return an array of archive ids or NULL on query failure
		 */
		public function getArchivesByMedia($id);

		/**
		 * \brief Get an archive file by its id
		 * \param $id : archive file id
		 * \param $rowLock : put a lock on archive with id $id
		 * \return archive file information
		 * \note No permission check will be performed
		 */
		public function getArchiveFile($id, $rowLock = DB::DB_ROW_LOCK_NONE);

		/**
		 * \brief Get an archive files ids list by an array of parameters
		 * \param $params : optional parameters
		 * \return array of archive file ids
		 * \note No permission check will be performed
		 */
		public function getArchiveFilesByParams(&$params);

		/**
		 * \brief Get a list of archive ids by pool id
		 * \param $id : pool id
		 * \return an array of archive ids or NULL on query failure
		 */
		public function getArchivesByPool($id);

		/**
		 * \brief Get list of archive format ids
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getArchiveFormats(&$params);

		/**
		 *\brief Get an archive format id by its name
		 *\param $name : archive format name
		 *\return archive format id or false if not found
		 */
		public function getArchiveFormatByName($name);

		/**
		 * \brief Get list of tuple of archive and archive mirror
		 * \param $pool : pool id
		 * \return list of tuple
		 */
		public function getArchiveMirrorsByPool($pool, $poolMirror);

		/**
		 * \brief Get iterator on files list for a specific archive
		 * \param $id : an archive
		 * \param $params : optional parameters
		 * \return an iterator which allow to browse on a files list
		 */
		public function getFilesFromArchive($id, &$params);

		/**
		 * \brief check if an archive is synchronized in its archive mirror
		 * \param $id : the archive's id
		 * \return an object which contains a boolean status : true if synchronized, else false
		 */
		public function isArchiveSynchronized($id);

		/**
		 * \brief Update an archive
		 * \param $archive : an archive
		 * \return \b NULL on failure, \b FALSE if no archive was updated or \b TRUE on success
		 */
		public function updateArchive(&$archive);
	}
?>
