<?php
	require_once('db.php');
	require_once("dbJob.php");
	require_once("dbMetadata.php");
	require_once("dbPermission.php");

	/**
	 * \brief Specific interface for archive object
	 */
	interface DB_Archive extends DB, DB_Job, DB_Metadata, DB_Permission {
		/**
		 * \brief Get an archive by its id
		 * \param $id : archive id
		 * \return archive information
		 * \note No permission check will be performed
		 */
		public function getArchive($id);

		/**
		 * \brief Get archives ids list for user \em $user_id
		 * \param $user_id : a user
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getArchives($user_id, &$params);

		/**
		 * \brief Get a list of archive ids by media id
		 * \param $id : media id
		 * \return an array of archive ids or NULL on query failure
		 */
		public function getArchivesByMedia($id);

		/**
		 * \brief Get an archive format by its id
		 * \param $id : archive id
		 * \return archive format information
		 * \note No permission check will be performed
		 */
		public function getArchiveFormat($id);

		/**
		 * \brief Get list of archive format ids
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getArchiveFormats(&$params);

		/**
		 *\brief Get an archive format id by its name
		 *\param $name : archive format name
		 *\return archive format id or false if not found.
		 */
		public function getArchiveFormatByName($name);

		/**
		 * \brief Get iterator on files list for a specific archive
		 * \param $id : an archive
		 * \param $params : optional parameters
		 * \return an iterator which allow to browse on a files list
		 */
		public function getFilesFromArchive($id, &$params);

		/**
		 * \brief Get a media by its id
		 * \param $id : media id
		 * \return media information
		 * \note No permission check will be performed
		 */
		public function getMedia($id);

		/**
		 * \brief Get a medias ids list by its pool
		 * \param $pool : medias pool id
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getMediasByPool($pool, &$params);

		/**
		 * \brief Get a medias ids list by its user poolgroup
		 * \param $user_poolgroup : user poolgroup id
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getMediasByPoolgroup($user_poolgroup, &$params);

		/**
		 * \brief Get a medias ids list without pool
		 * \param $mediaformat : mediaformat id [optional]
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getMediasWithoutPool($mediaformat, &$params);

		/**
		 * \brief Get a media format by its id
		 * \param $id : media id
		 * \return media format information
		 * \note No permission check will be performed
		 */
		public function getMediaFormat($id);

		/**
		 * \brief Get a pool by its id
		 * \param $id : pool id
		 * \return media format information
		 * \note No permission check will be performed
		 */
		public function getPool($id);

		/**
		 * \brief Get a pools ids list by its user poolgroup
		 * \param $user_poolgroup : user poolgroup id
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getPoolsByPoolgroup($user_poolgroup, &$params);

		/**
		 * \brief Update an archive
		 * \param $archive : an archive
		 * \return \b NULL on failure, \b FALSE if no archive was updated or \b TRUE on success
		 */
		public function updateArchive(&$archive);

		/**
		 * \brief Update a media
		 * \param $media : a media
		 * \return \b NULL on failure, \b False if no media was updated or \b TRUE on success
		 */
		public function updateMedia(&$media);

		/**
		 * \brief Update a pool
		 * \param $media : a pool
		 * \return \b NULL on failure, \b False if no pool was updated or \b TRUE on success
		 */
		public function updatePool(&$pool);
	}

	require_once("db/${db_config['driver']}Archive.php");

	$className = ucfirst($db_config['driver']) . 'DBArchive';
	$dbDriver = new $className($db_config);
?>
