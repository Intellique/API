<?php
	/**
	 * \brief Specific interface for metadata
	 */
	interface DB_Metadata {
		/**
		 * \brief Create a metadata
		 * \param $id : id
		 * \param $key : metadata key
		 * \param $value : metadata value
		 * \param $type : metatype
		 * \param $userId : user id
		 * \return \b TRUE on success or \b NULL on query execution failure
		 */
		public function createMetadata($id, $key, $value, $type, $userId);

		/**
		 * \brief Delete a metadata by key
		 * \param $id : id
		 * \param $key : metadata key
		 * \param $type : metatype
		 * \param $userId : user id
		 * \return \b TRUE on deletion success, \b FALSE when no metadata was deleted, \b NULL on query execution failure
		 */
		public function deleteMetadata($id, $key, $type, $userId);

		/**
		 * \brief Delete a job
		 * \param $id : id
		 * \param $type : metatype
		 * \param $userId : user id
		 * \return \b TRUE on deletion success, \b FALSE when no metadata was deleted, \b NULL on query execution failure
		 */
		public function deleteMetadatas($id, $type, $userId);

		/**
		 * \brief Get a metadata value by key
		 * \param $id : id
		 * \param $key : metadata key
		 * \param $type : metatype
		 * \return <b>Metadata value</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getMetadata($id, $key, $type);

		/**
		 * \brief Get metadata
		 * \param $id : id
		 * \param $type : metatype
		 * \return <b>Hash table of metadata</b>, \b FALSE if not found, \b NULL on query execution failure
		 */
		public function getMetadatas($id, $type);

		/**
		 * \brief Update a metadata
		 * \param $id : id
		 * \param $key : metadata key
		 * \param $value : metadata value
		 * \param $type : metatype
		 * \param $userId : user id
		 * \return \b NULL on failure, \b FALSE if no metadata were updated or \b TRUE on success
		 */
		public function updateMetadata($id, $key, $value, $type, $userId);
	}
?>