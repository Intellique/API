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
		 * \brief Get a pool by its id
		 * \param $id : pool id
		 * \param $rowLock : put a lock on pool with id $id
		 * \return media format information
		 * \note No permission check will be performed
		 */
		public function getPool($id, $rowLock = DB::DB_ROW_LOCK_NONE);
	}
?>
