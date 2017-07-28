<?php
    require_once("postgresql.php");
	require_once("postgresqlMedia.php");

    class PostgresqlDBLibrary extends PostgresqlDB implements DB_Library {
		use PostgresqlDBMedia;

        public function getDrivesByChanger($id) {
            $query_name = 'get_drives_by_changer';
            if (!$this->prepareQuery($query_name, "SELECT cs.index AS drivenumber, d.id, d.model, d.vendor, d.serialnumber, d.status FROM changerslot cs INNER JOIN drive d ON cs.drive = d.id WHERE cs.changer = $1 AND cs.drive IS NOT NULL"))
                return false;

            $query_result = pg_execute($this->connect, $query_name, array($id));
            if (!$query_result)
                return false;

            $result = array();
            while ($row = pg_fetch_assoc($query_result)) {
				$row['id'] = intval($row['id']);
				$row['drivenumber'] = intval($row['drivenumber']);
                $result[] = $row;
			}

            return $result;
        }

		public function getPhysicalLibraries() {
			$query_name = 'get_physical_librairies';
			if(!$this->prepareQuery($query_name, "SELECT c.id, c.model, c.vendor, c.serialnumber, c.status, c.isonline, c.action FROM changer c WHERE c.serialnumber NOT IN (SELECT uuid::TEXT FROM vtl) AND c.enable AND serialnumber NOT IN (SELECT serialnumber FROM drive WHERE changer <> c.id)"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array());
			if (!$query_result)
				return false;

			$result = array();
			while ($row = pg_fetch_assoc($query_result)) {
				$row['id'] = intval($row['id']);
				$row['isonline'] = $row['isonline'] == 't';
				$result[] = $row;
			}

			return $result;
		}

		public function getSlotsByChanger($id) {
			$query_name = 'get_slots_by_changer';
			if (!$this->prepareQuery($query_name, "SELECT changer || '_' || index AS changerslotid, index AS slotnumber, CASE WHEN isieport THEN 'import / export' ELSE 'storage' END AS type, media FROM changerslot WHERE changer = $1 AND drive IS NULL ORDER BY index"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array($id));
			if (!$query_result)
				return false;

			$result = array();

			while ($row = pg_fetch_assoc($query_result)) {
				$row['slotnumber'] = intval($row['slotnumber']);
				$row['media'] = isset($row['media']) ? intval($row['media']) : NULL;
				$result[] = $row;
			}

			return $result;
		}

		public function getSlotByDrive($id) {
			$query_name = 'get_slots_by_drive';
			if (!$this->prepareQuery($query_name, "SELECT cs.changer || '_' || cs.index AS slotid, cs.index AS slotnumber, cs.media FROM changerslot cs INNER JOIN drive d ON cs.drive = d.id AND d.id = $1"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array($id));
			if (!$query_result)
				return false;

			$result = array();

			while ($row = pg_fetch_assoc($query_result)) {
				$row['media'] = isset($row['media']) ? intval($row['media']) : NULL;
				$row['type'] = 'drive';
				$row['slotnumber'] = intval($row['slotnumber']);
				$result[] = $row;
			}

			return $result;
		}

		public function getStandaloneDrives() {
			$query_name = 'get_standalone_drives';
			if (!$this->prepareQuery($query_name, "SELECT d.id AS driveid, c.model, c.vendor, d.serialnumber AS driveserialnumber, d.status FROM changer c LEFT JOIN drive d ON d.changer = c.id AND d.serialnumber = c.serialnumber WHERE c.serialnumber NOT IN (SELECT uuid::TEXT FROM vtl) AND c.enable AND d.id IS NOT NULL"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array());
			if (!$query_result)
				return false;

			$result = array();
			while ($row = pg_fetch_assoc($query_result))
				$result[] = $row;

			return $result;
		}

		public function getVTLs() {
			$query_name = 'get_VTLs';
			if (!$this->prepareQuery($query_name, "SELECT DISTINCT c.id AS changerid, SUBSTRING(v.path FROM CHAR_LENGTH(SUBSTRING(v.path FROM '(.+/)[^/]+')) + 1) AS name, c.model, c.vendor, c.serialnumber AS changerserialnumber, c.status, c.isonline, c.action FROM changer c INNER JOIN vtl v ON c.serialnumber = v.uuid::TEXT AND c.enable"))
				return false;

			$query_result = pg_execute($this->connect, $query_name, array());
			if (!$query_result)
				return false;

			$result = array();
			while ($row = pg_fetch_assoc($query_result))
				$result[] = $row;

			return $result;
		}

		public function setLibraryAction($id, $act) {
			$query_name = 'set_library_action';
			if (!$this->prepareQuery($query_name,"UPDATE changer set action = $1 WHERE id = $2"))
				return false;
			
			return pg_execute($this->connect, $query_name, array($act, $id)) !== false;
		}

    }
?>
