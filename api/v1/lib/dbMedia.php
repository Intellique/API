<?php
	interface DB_Media {
		/**
		 * \brief Get an archive format by its id
		 * \param $id : archive id
		 * \return archive format information
		 * \note No permission check will be performed
		 */
		public function getArchiveFormat($id);

		/**
		 * \brief Get a media by its id
		 * \param $id : media id
		 * \param $rowLock : put a lock on media with id $id
		 * \return media information
		 * \note No permission check will be performed
		 */
		public function getMedia($id, $rowLock = DB::DB_ROW_LOCK_NONE);

		/**
		 * \brief Get a media format by its id
		 * \param $id : media id
		 * \return media format information
		 * \note No permission check will be performed
		 */
		public function getMediaFormat($id);

		/**
		 * \brief Get iterator on files list for a specific media
		 * \param $id : a media
		 * \param $params : optional parameters
		 * \return an iterator which allow to browse on a files list
		 */
		public function getMediaFormatByName($id);

		/**
		 * \brief Get list of media format ids
		 * \param $params : optional parameters
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getMediaFormats(&$params);

		/**
		 * \brief Get a media by its name, its pool, its format, its type, its nbfiles, or the format of the archive which it's in.
		 * \param $name : media name
		 * \param $pool : media pool id
		 * \param $mediaformat : media format
		 * \param $type : media type
		 * \param $nbfiles : media nbfiles
		 * \param $archiveformat : format of the archive the media is in 
		 * \return an object which contains 'rows', 'total_rows', 'query', 'query_name', 'query_prepared', 'query_executed'
		 */
		public function getMediasByParams(&$params);

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
		 * \brief Update a media
		 * \param $media : a media
		 * \return \b NULL on failure, \b False if no media was updated or \b TRUE on success
		 */
		public function updateMedia(&$media);
	}
?>
