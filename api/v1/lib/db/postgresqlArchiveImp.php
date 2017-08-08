<?php
	require_once('postgresql.php');
	require_once('postgresqlArchive.php');
	require_once('postgresqlJob.php');
	require_once('postgresqlMetadata.php');
	require_once('postgresqlPermission.php');
	require_once('postgresqlPool.php');
	require_once('postgresqlUser.php');

	class PostgresqlDBArchiveImp extends PostgresqlDB implements DB_Archive, DB_Job, DB_Metadata, DB_Permission, DB_Pool, DB_User {
		use PostgresqlDBArchive, PostgresqlDBJob, PostgresqlDBMetadata, PostgresqlDBPermission, PostgresqlDBPool, PostgresqlDBUser;
	}
?>
