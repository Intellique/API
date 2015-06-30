DROP TABLE IF EXISTS Archive CASCADE;
DROP TABLE IF EXISTS ArchiveFile CASCADE;
DROP TABLE IF EXISTS ArchiveFileToArchiveVolume CASCADE;
DROP TABLE IF EXISTS ArchiveFileToChecksumResult CASCADE;
DROP TABLE IF EXISTS ArchiveFormat CASCADE;
DROP TABLE IF EXISTS ArchiveMirror CASCADE;
DROP TABLE IF EXISTS ArchiveToArchiveMirror CASCADE;
DROP TABLE IF EXISTS ArchiveVolume CASCADE;
DROP TABLE IF EXISTS ArchiveVolumeToChecksumResult CASCADE;
DROP TABLE IF EXISTS Backup CASCADE;
DROP TABLE IF EXISTS BackupVolume CASCADE;
DROP TABLE IF EXISTS BackupVolumeToChecksumResult CASCADE;
DROP TABLE IF EXISTS Changer CASCADE;
DROP TABLE IF EXISTS ChangerSlot CASCADE;
DROP TABLE IF EXISTS Checksum CASCADE;
DROP TABLE IF EXISTS ChecksumResult CASCADE;
DROP TABLE IF EXISTS Drive CASCADE;
DROP TABLE IF EXISTS DriveFormat CASCADE;
DROP TABLE IF EXISTS DriveFormatSupport CASCADE;
DROP TABLE IF EXISTS Host CASCADE;
DROP TABLE IF EXISTS JobRun CASCADE;
DROP TABLE IF EXISTS Job CASCADE;
DROP TABLE IF EXISTS JobRecord CASCADE;
DROP TABLE IF EXISTS JobToSelectedFile CASCADE;
DROP TABLE IF EXISTS JobType CASCADE;
DROP TABLE IF EXISTS Log CASCADE;
DROP TABLE IF EXISTS Media CASCADE;
DROP TABLE IF EXISTS MediaFormat CASCADE;
DROP TABLE IF EXISTS MediaLabel CASCADE;
DROP TABLE IF EXISTS Metadata CASCADE;
DROP TABLE IF EXISTS Pool CASCADE;
DROP TABLE IF EXISTS PoolGroup CASCADE;
DROP TABLE IF EXISTS PoolMirror CASCADE;
DROP TABLE IF EXISTS PoolTemplate CASCADE;
DROP TABLE IF EXISTS PoolTemplateToChecksum CASCADE;
DROP TABLE IF EXISTS PoolToChecksum CASCADE;
DROP TABLE IF EXISTS PoolToPoolGroup CASCADE;
DROP TABLE IF EXISTS Proxy CASCADE;
DROP TABLE IF EXISTS Scripts CASCADE;
DROP TABLE IF EXISTS Script CASCADE;
DROP TABLE IF EXISTS Report CASCADE;
DROP TABLE IF EXISTS Reports CASCADE;
DROP TABLE IF EXISTS RestoreTo CASCADE;
DROP TABLE IF EXISTS SelectedFile CASCADE;
DROP TABLE IF EXISTS UserEvent CASCADE;
DROP TABLE IF EXISTS UserLog CASCADE;
DROP TABLE IF EXISTS Users CASCADE;
DROP TABLE IF EXISTS Vtl CASCADE;

DROP FUNCTION IF EXISTS check_metadata();

DROP TYPE IF EXISTS AutoCheckMode;
DROP TYPE IF EXISTS ChangerAction;
DROP TYPE IF EXISTS ChangerSlotType;
DROP TYPE IF EXISTS ChangerStatus;
DROP TYPE IF EXISTS DriveStatus;
DROP TYPE IF EXISTS FileType;
DROP TYPE IF EXISTS JobRecordNotif;
DROP TYPE IF EXISTS JobStatus;
DROP TYPE IF EXISTS LogLevel;
DROP TYPE IF EXISTS LogType;
DROP TYPE IF EXISTS MediaFormatDataType;
DROP TYPE IF EXISTS MediaFormatMode;
DROP TYPE IF EXISTS MediaLocation;
DROP TYPE IF EXISTS MediaStatus;
DROP TYPE IF EXISTS MediaType;
DROP TYPE IF EXISTS MetaType;
DROP TYPE IF EXISTS ProxyStatus;
DROP TYPE IF EXISTS ScriptType;
DROP TYPE IF EXISTS UnbreakableLevel;
