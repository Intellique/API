<?php
	/**
	 * \brief generates a universally unique identifier (UUID) (Version 4)
	 * \return a uuid
	 */
	function uuid_generate() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}

	/**
	 * \brief checks whether the uuid is valid
	 * \return 1 if the uuid is valid, 0 if it is not, and false if an error ocurred
	 * \param $uuid a uuid
	 */
	function uuid_is_valid ($uuid) {
		return preg_match('/^[0-9a-fA-F]{8}(-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}$/', $uuid);
	}
?>