INSERT INTO MediaFormat(name, dataType, mode, maxLoadCount, maxReadCount, maxWriteCount, maxOpCount, lifespan, capacity, blockSize, densityCode, supportPartition, supportMAM) VALUES
	('LTO-6', 'data', 'linear', 4096, 40960, 40960, 40960, INTERVAL 'P10Y', 2620446998528, 32768, 90, TRUE, TRUE),
	('LTO-5', 'data', 'linear', 4096, 40960, 40960, 40960, INTERVAL 'P10Y', 1529931104256, 32768, 88, TRUE, TRUE),
	('LTO-4', 'data', 'linear', 4096, 40960, 40960, 40960, INTERVAL 'P8Y', 764965552128, 32768, 70, FALSE, TRUE),
	('LTO-3', 'data', 'linear', 4096, 40960, 40960, 40960, INTERVAL 'P6Y', 382482776064, 32768, 68, FALSE, TRUE),
	('LTO-2', 'data', 'linear', 4096, 40960, 40960, 40960, INTERVAL 'P6Y', 191241388032, 32768, 66, FALSE, TRUE),
	('X23', 'data', 'linear', 4096, 40960, 40960, 40960, INTERVAL 'P6Y', 153691136, 1024, 130, FALSE, FALSE),
	('DLT', 'data', 'linear', 4096, 40960, 40960, 40960, INTERVAL 'P6Y', 153691136, 1024, 129, FALSE, FALSE);

INSERT INTO DriveFormat(name, densityCode, mode, cleaningInterval) VALUES
    ('LTO-6', 90, 'linear', INTERVAL 'P1W'),
	('LTO-5', 88, 'linear', INTERVAL 'P1W'),
	('LTO-4', 70, 'linear', INTERVAL 'P1W'),
	('LTO-3', 68, 'linear', INTERVAL 'P1W'),
	('LTO-2', 66, 'linear', INTERVAL 'P1W'),
	('VXA-3', 130, 'linear', INTERVAL 'P1W'),
	('DLT', 129, 'linear', INTERVAL 'P1W');

INSERT INTO DriveFormatSupport(driveFormat, mediaFormat, read, write) VALUES
	(1, 1, TRUE, TRUE),
	(1, 2, TRUE, TRUE),
	(1, 3, TRUE, FALSE),
	(2, 2, TRUE, TRUE),
	(2, 3, TRUE, TRUE),
	(2, 4, TRUE, FALSE),
	(3, 3, TRUE, TRUE),
	(3, 4, TRUE, TRUE),
	(3, 5, TRUE, FALSE),
	(4, 4, TRUE, TRUE),
	(4, 5, TRUE, TRUE),
	(5, 5, TRUE, TRUE),
	(5, 6, TRUE, TRUE),
	(6, 6, TRUE, TRUE);

INSERT INTO Application(name) VALUES
    ('StoriqOne Changer'),
    ('StoriqOne Daemon'),
    ('StoriqOne Drive'),
    ('StoriqOne Job'),
    ('StoriqOne Logger');

COPY archiveformat (id, name, readable, writable) FROM stdin;
1	Storiq One (TAR)	t	t
2	LTFS	t	f
\.
ALTER SEQUENCE archiveformat_id_seq RESTART 2;

INSERT INTO Pool(uuid, name, archiveFormat, mediaFormat, backupPool) VALUES
	('b9650cc3-12ec-4a0f-88db-d70f0b269a6b', 'storiq', 1, 1, FALSE),
	('d9f976d4-e087-4d0a-ab79-96267f6613f0', 'Stone_Db_Backup', 1, 1, TRUE);

INSERT INTO PoolGroup(uuid, name) VALUES
	('7a9102a2-6f4d-c85f-1553-e8d769569558', 'basic'),
	('755d095a-7f59-40be-a11b-c4fdf4be5839', 'archive');

INSERT INTO PoolToPoolGroup VALUES
    (1, 1);

INSERT INTO Users(login, password, salt, fullname, email, homedirectory, isAdmin, canArchive, canRestore, poolgroup, meta) VALUES
	('admin', '8a6eb1d3b4fecbf8a1d6528a6aecb064e801b1e0', 'cd8c63688e0c2cff', 'admin', 'admin@storiqone-backend.net', '/mnt/raid', TRUE, TRUE, TRUE, 1, '{"step":5,"showHelp":true}'),
	('basic', 'd57d82c1ab3abf6ae0c03ed35ae92cea73b60ae2', '1e31dc8ae0f1842b', 'basic', 'basic@storiqone-backend.net', '/mnt/raid', FALSE, FALSE, FALSE, 1, '{"step":5,"showHelp":true}'),
	('archiver', '58587452eadca1a695d8d713e6e94f567e8cd787', '20e90c3cd2be2354', 'archiver', 'archiver@storiqone-backend.net', '/mnt/raid', FALSE, TRUE, FALSE, 2, '{"step":5,"showHelp":true}');

INSERT INTO UserEvent(event) VALUES
	('connection'),
	('disconnection');

INSERT INTO Application(name, apikey) VALUES
    ('UnitTest','d017552c-e005-4bc7-86bc-5e3e8b3ade2b');

COPY host (id, uuid, name, domaine, description, daemonVersion, updated) FROM stdin;
6	9f89164e-9dd3-480d-8afd-a4d66807b6bc	taiko	\N	\N	1.3	2014-11-12 12:31:16.802855
\.
ALTER SEQUENCE host_id_seq RESTART 7;

COPY selectedfile (id, path) FROM stdin;
2	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN
\.
ALTER SEQUENCE selectedfile_id_seq RESTART 3;

COPY pool (id, uuid, name, archiveformat, mediaformat, autocheck, growable, unbreakablelevel, rewritable, metadata, pooloriginal, deleted, lockcheck, poolmirror) FROM stdin;
3	60885acc-aa6f-47e2-8164-f80f039420a5	ARCHIVES_CAPTATIONS	1	2	none	f	file	t	[]	\N	f	f	\N
6	b2719811-bad0-466a-8c00-7e7a51c7f473	EXPORT_PROVISOIRE_RUSHS	1	2	thorough mode	f	file	t	{"NOMENCLATURE":{"mandatory":true,"type":"label"}}	\N	f	f	\N
7	cf3d97b5-d5fe-4384-945e-927ab6fa7608	ARCHIVES_TESTS	1	2	none	f	file	t	[]	\N	f	f	\N
\.
ALTER SEQUENCE pool_id_seq RESTART 8;

COPY pooltopoolgroup (pool, poolgroup) FROM stdin;
3	1
6	1
7	1
\.

COPY media (id, uuid, label, mediumserialnumber, name, status, firstused, usebefore, lastread, lastwrite, loadcount, readcount, writecount, operationcount, nbtotalblockread, nbtotalblockwrite, nbreaderror, nbwriteerror, nbfiles, blocksize, freeblock, totalblock, haspartition, type, mediaformat, pool, archiveformat) FROM stdin;
1	8a391d01-6139-4ad9-8463-2ba6e8852040	EXP006	HA1PFAp084	EXPORTS_RUSHS_06	in use	2012-09-27 13:34:50	2012-09-27 13:34:58	2014-09-24 12:06:48	\N	1938277	44	14	0	7289630	4	0	0	9	32768	8001952	25607232	f	rewritable	2	6	1
2	4fb920af-afbd-4365-91b8-2019fa7297fb	EXP007	HA2PFAp093	EXPORTS_RUSHS_07	foreign	2012-09-27 13:34:50	2012-09-27 13:34:58	2014-09-24 12:06:48	\N	1938277	44	14	0	7289630	4	0	0	9	32768	8001952	25607232	f	rewritable	1	\N	1
3	4fb920af-afbd-4365-91b8-2019fa7297fc	EXP008	HA2PFAp094	EXPORTS_RUSHS_08	foreign	2012-09-27 13:34:50	2012-09-27 13:34:58	2014-09-24 12:06:48	\N	1938277	44	14	0	7289630	4	0	0	9	32768	8001952	25607232	f	rewritable	2	\N	1
\.
ALTER SEQUENCE media_id_seq RESTART 4;

INSERT INTO changer(id, model, vendor, firmwarerev, serialnumber, wwn, barcode, status, isonline, action, enable, host) VALUES
	(1, 'FlexStor II', 'BDT', '5.00', '00MX64100694', 'sas:0x5000e1115d3d3002', 't', 'idle', 't', 'none', 't', 6);

INSERT INTO drive (id, model, vendor, firmwarerev, serialnumber, status, operationduration, lastclean, enable, changer, driveformat) VALUES
	(1, 'ULT3580-HH6', 'IBM', 'F9A1', '10WT067290', 'empty idle', '9923.5', NULL,'t', 1, 1);

INSERT INTO changerslot(changer, index, drive, media, isieport, enable) VALUES
	(1, 0, 1, NULL,'f', 't'),
	(1, 1, NULL, NULL,'f', 't'),
	(1, 2, NULL, NULL,'f', 't'),
	(1, 3, NULL, NULL,'f', 't'),
	(1, 4, NULL, 1,'f', 't'),
	(1, 5, NULL, NULL,'f', 't'),
	(1, 6, NULL, NULL,'f', 't'),
	(1, 7, NULL, NULL,'f', 't'),
	(1, 8, NULL, 3,'f', 't'),
	(1, 9, NULL, NULL,'f', 't'),
	(1, 10, NULL, NULL,'f', 't'),
	(1, 11, NULL, NULL,'f', 't'),
	(1, 12, NULL, NULL,'f', 't'),
	(1, 13, NULL, NULL,'f', 't'),
	(1, 14, NULL, NULL,'f', 't'),
	(1, 15, NULL, NULL,'f', 't'),
	(1, 16, NULL, NULL,'f', 't'),
	(1, 17, NULL, NULL,'f', 't'),
	(1, 18, NULL, NULL,'f', 't'),
	(1, 19, NULL, NULL,'f', 't'),
	(1, 20, NULL, NULL,'f', 't'),
	(1, 21, NULL, NULL,'f', 't'),
	(1, 22, NULL, NULL,'f', 't'),
	(1, 23, NULL, NULL,'f', 't'),
	(1, 24, NULL, NULL,'t', 't');

COPY jobtype (id, name) FROM stdin;
1	backup-db
2	check-archive
3	copy-archive
4	create-archive
5	format-media
6	restore-archive
7	erase-media
\.
ALTER SEQUENCE jobtype_id_seq RESTART 8;

COPY archive (id, uuid, name, creator, owner, canappend, status, deleted) FROM stdin;
2	20c07322-607c-4e67-9ae4-2db8ad9d707f	OESC_AMON_LE_VICTORIEUX_C_BARBOTIN	1	1	t	complete	f
\.
ALTER SEQUENCE archive_id_seq RESTART 3;

COPY job (id, name, type, nextstart, "interval", repetition, status, update, archive, backup, media, pool, host, login, metadata) FROM stdin;
4	OESC_AMON_LE_VICTORIEUX_C_BARBOTIN	4	2012-09-27 16:59:39	\N	0	finished	2014-11-12 12:31:17	\N	\N	\N	3	6	2	{"Nomenclature_Echo":"20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN"}
5	Format-52	5	2014-10-24 10:51:38	\N	0	finished	2014-11-12 12:31:19	\N	\N	1	3	6	1	{}
\.
ALTER SEQUENCE job_id_seq RESTART 6;

INSERT INTO jobtoselectedfile(job, selectedfile) VALUES (5, 2);

COPY jobrun (id, job, numrun, starttime, endtime, status, done, exitcode, stoppedbyuser) FROM stdin;
4	4	1	2012-09-27 16:59:47	2012-09-27 17:02:15	finished	1	0	f
5	4	1	2014-10-22 11:19:03	2014-10-22 11:40:08	finished	1	0	f
\.
ALTER SEQUENCE jobrun_id_seq RESTART 6;

COPY archivevolume (id, sequence, size, starttime, endtime, checksumok, checktime, archive, media, mediaposition, jobrun) FROM stdin;
2	0	15448169984	2012-09-27 16:59:47	2012-09-27 17:02:08	f	\N	2	1	3	4
\.
ALTER SEQUENCE archivevolume_id_seq RESTART 3;

COPY archivefile (id, name, type, mimetype, ownerid, owner, groupid, groups, perm, ctime, mtime, size, parent) FROM stdin;
6	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN	directory		550	postgres	1050	postgres	373	2012-06-22 17:39:42	2012-06-22 17:39:42	4096	2
7	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/.DS_Store	regular file		550	postgres	1050	postgres	373	2012-06-21 17:27:21	2012-06-21 17:27:21	12292	2
8	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/._.DS_Store	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	4096	2
9	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_1s1_DV43_STR	regular file		550	postgres	1050	postgres	373	2012-06-21 17:26:56	2012-06-21 17:26:56	12611926848	2
10	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/._20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_1s1_DV43_STR	regular file		550	postgres	1050	postgres	373	2012-06-21 17:26:56	2012-06-21 17:26:56	4096	2
11	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD	directory		550	postgres	1050	postgres	373	2012-06-21 17:27:09	2012-06-21 17:27:09	48	2
12	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/AUDIO_TS	directory		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	10	2
13	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS	directory		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	4096	2
14	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/LE_DVD.layout	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	1746	2
15	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VIDEO_TS.BUP	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	14336	2
16	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VIDEO_TS.IFO	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	14336	2
17	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VIDEO_TS.VOB	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	157696	2
18	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VOB_DATA.LAY	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	125670	2
19	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_01_0.BUP	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	20480	2
20	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_01_0.IFO	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	20480	2
21	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_01_0.VOB	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	212992	2
22	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_01_1.VOB	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	7407616	2
23	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_02_0.BUP	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	51200	2
24	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_02_0.IFO	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	51200	2
25	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_02_0.VOB	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	157696	2
26	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_02_1.VOB	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	1073565696	2
27	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_02_2.VOB	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	1073565696	2
28	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD/VIDEO_TS/VTS_02_3.VOB	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	596557824	2
29	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DOCS	directory		550	postgres	1050	postgres	373	2012-06-21 17:27:25	2012-06-21 17:27:25	4096	2
30	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DOCS/En matière de statuaire égyptienne.doc	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	14657536	2
31	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DOCS/._En matière de statuaire égyptienne.doc	regular file		550	postgres	1050	postgres	373	2012-01-16 17:46:27	2012-01-16 17:46:27	4096	2
32	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DOCS/barbotinDisque.psd	regular file		550	postgres	1050	postgres	373	2012-06-21 17:27:25	2012-06-21 17:27:25	16676938	2
33	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DOCS/._barbotinDisque.psd	regular file		550	postgres	1050	postgres	373	2012-06-21 17:27:25	2012-06-21 17:27:25	69555	2
34	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DOCS/Barbotin Jaquette DVD.psd	regular file		550	postgres	1050	postgres	373	2012-06-21 17:27:25	2012-06-21 17:27:25	52746988	2
35	/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DOCS/._Barbotin Jaquette DVD.psd	regular file		550	postgres	1050	postgres	373	2012-06-21 17:27:25	2012-06-21 17:27:25	63764	2
\.
ALTER SEQUENCE archivefile_id_seq RESTART 36;

INSERT INTO metadata(id, type, key, value, login) VALUES
	(2, 'archive', 'foo', '"foo"', 1);

INSERT INTO metadata(id, type, key, value, login) VALUES
	(6, 'archive', 'foo', '"bar"', 1);

INSERT INTO metadata(id, type, key, value, login) VALUES
	(6, 'archivefile', 'foo', '"bar"', 1);

COPY archivefiletoarchivevolume (archivevolume, archivefile, blocknumber, archivetime, checktime, checksumok) FROM stdin;
2	6	0	2012-09-27 16:59:47	\N	f
2	7	0	2012-09-27 16:59:47	\N	f
2	8	0	2012-09-27 16:59:47	\N	f
2	9	0	2012-09-27 16:59:47	\N	f
2	10	384886	2012-09-27 16:59:47	\N	f
2	11	384886	2012-09-27 16:59:47	\N	f
2	12	384886	2012-09-27 16:59:47	\N	f
2	13	384886	2012-09-27 16:59:47	\N	f
2	14	384886	2012-09-27 16:59:47	\N	f
2	15	384886	2012-09-27 16:59:47	\N	f
2	16	384887	2012-09-27 16:59:47	\N	f
2	17	384887	2012-09-27 16:59:47	\N	f
2	18	384892	2012-09-27 16:59:47	\N	f
2	19	384896	2012-09-27 16:59:47	\N	f
2	20	384896	2012-09-27 16:59:47	\N	f
2	21	384897	2012-09-27 16:59:47	\N	f
2	22	384904	2012-09-27 16:59:47	\N	f
2	23	385130	2012-09-27 16:59:47	\N	f
2	24	385131	2012-09-27 16:59:47	\N	f
2	25	385133	2012-09-27 16:59:47	\N	f
2	26	385138	2012-09-27 16:59:47	\N	f
2	27	417901	2012-09-27 16:59:47	\N	f
2	28	450663	2012-09-27 16:59:47	\N	f
2	29	468869	2012-09-27 16:59:47	\N	f
2	30	468869	2012-09-27 16:59:47	\N	f
2	31	469316	2012-09-27 16:59:47	\N	f
2	32	469316	2012-09-27 16:59:47	\N	f
2	33	469825	2012-09-27 16:59:47	\N	f
2	34	469827	2012-09-27 16:59:47	\N	f
2	35	471437	2012-09-27 16:59:47	\N	f
\.

INSERT INTO vtl(uuid, path, prefix, nbslots, nbdrives, mediaformat, host, deleted) VALUES ('17b74300-1e97-11e6-bdf4-0800200c9a66', '/mnt/vtl/VTL', 'VTL', 8, 2, 2, 6, 'f');

INSERT INTO pooltemplate(name, autocheck, lockcheck, growable, unbreakablelevel, rewritable, createproxy) VALUES ('test_pool', 'none', 't', 'f', 'archive', 't', 'f');

INSERT INTO poolmirror(uuid, name, synchronized) VALUES
('609abc8a-3f92-4450-89df-5e6012baee70', 'test', 't'),
('322980de-0af7-4e48-beaa-f58c6ab4a302', 'test2', 'f'),
('44dc3636-fa69-4e5c-991a-0ebb6b20cdb2', 'test3', 't');

REFRESH MATERIALIZED VIEW milestones_files;
