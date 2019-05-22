from common_test import CommonTest
import json, unittest

class SearchTest(CommonTest):

    def test_01_search_archivefile_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?type=directory" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_search_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?owner=%d&deleted=yes" % (self.path, 1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_search_job_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/search/?name=%s" % (self.path, 'Nasa'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_search_pool_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/search/?name=storiq" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_search_user_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/search/?isadmin=%s" % (self.path, 'false'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_search_archivefile_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?type=%s&order_by=%s" % (self.path, 'directory', 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_search_archive_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?creator=%s&order_by=%s" % (self.path, '1', 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_search_job_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/search/?name=%s&order_by=%s" % (self.path, 'grumph', 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_search_pool_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/search/?name=%s&order_by=%s" % (self.path, 'FAF', 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_search_user_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/search/?isadmin=%s&order_by=%s" % (self.path, 't', 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    @unittest.skip("demonstrating skipping")
    def test_11_search_archivefile_permission_denied(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%sarchivefile/search/?type=%s" % (self.path, 'directory'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    @unittest.skip("demonstrating skipping")
    def test_12_search_pool_permission_denied(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%spool/search/?name=%s" % (self.path, 'ARCHIVES_CAPTATIONS'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    @unittest.skip("demonstrating skipping")
    def test_13_search_user_permission_denied(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', "%suser/search/?isadmin=%s" % (self.path, 'true'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_14_search_archive_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?creator=%s" % (self.path, '10000'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    @unittest.skip("demonstrating skipping")
    def test_15_search_archivefile_not_found(self):
         conn, headers, message = self.newLoggedConnection('basic')
         conn.request('GET', "%sarchivefile/search/?name=%s" % (self.path, 'foo bar'), headers=headers)
         res = conn.getresponse()
         conn.close()
         self.assertEqual(res.status, 404)

    def test_16_search_job_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/search/?name=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_17_search_pool_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/search/?name=%s" % (self.path, 'bar'), headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 404)

    def test_18_search_user_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/search/?login=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    @unittest.skip("demonstrating skipping")
    def test_19_search_archive_success_owner_string(self):
         conn, headers, message = self.newLoggedConnection('admin')
         conn.request('GET', "%sarchive/search/?owner=admin&deleted=yes" % (self.path), headers=headers)
         res = conn.getresponse()
         message = json.loads(res.read().decode("utf-8"))
         conn.close()
         self.assertEqual(res.status, 200)

    def test_20_search_archivefile_success_multiple_args(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?type=directory&owner=postgres" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    @unittest.skip("demonstrating skipping")
    def test_21_search_archive_success_multiple_args(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?owner=1&name=MONARCHIVE&deleted=yes" % (self.path), headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 200)

    @unittest.skip("demonstrating skipping")
    def test_22_search_job_success_multiple_args(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/search/?name=%s&pool=%s" % (self.path, 'ArchiveCopyTest2', '7'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    @unittest.skip("demonstrating skipping")
    def test_23_search_pool_success_multiple_args(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/search/?name=ARCHIVES_CAPTATIONS&mediaformat=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    @unittest.skip("demonstrating skipping")
    def test_24_search_user_success_multiple_args(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/search/?isadmin=%s&login=%s" % (self.path, 'true', 'admin'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_25_search_media_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/search/?nbfiles=9" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_27_search_media_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/search/?nbfiles=9&order_by=fooo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_28_search_media_permission_denied(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/search/?nbfiles=10" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_29_search_device_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sdevice/search/?isonline=true" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_30_search_device_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sdevice/search/?vendor=foo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_31_search_device_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sdevice/search/?isonline=t&order_by=fooo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

#    def test_32_search_poolmirror_success(self):
#        conn, headers, message = self.newLoggedConnection('admin')
#        conn.request('GET', "%spoolmirror/search/?name=test" % (self.path), headers=headers)
#        res = conn.getresponse()
#        conn.close()
#        self.assertEqual(res.status, 200)

    def test_33_search_poolmirror_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spoolmirror/search/?name=aaa" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_34_search_poolmirror_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spoolmirror/search/?name=test&order_by=fooo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_35_search_pooltemplate_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spooltemplate/search/?name=video" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_36_search_pooltemplate_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spooltemplate/search/?name=bar" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_37_search_pooltemplate_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spooltemplate/search/?name=foo&order_by=fooo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_38_search_archivefile_in_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?deleted=yes&archivefile=10" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_39_search_archivefile_in_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?archivefile=457" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_40_search_archivefile_with_size(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size=-678" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_41_search_archivefile_with_size(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size=819766" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_42_search_archivefile_with_size(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size=678" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_43_search_archivefile_with_size_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_inf=819766" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_44_search_archivefile_with_size_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_inf=-10" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_45_search_archivefile_with_size_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_inf=1" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_46_search_archivefile_with_size_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_sup=999999819766" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_47_search_archivefile_with_size_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_sup=-10" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_48_search_archivefile_with_size_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_sup=0" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_49_search_archivefile_with_size_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_sup=0&size_inf=1000" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_50_search_archivefile_with_size_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_sup=1000&size_inf=10" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_51_search_archivefile_with_size_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?size_sup=999999819766&size_inf=999999819767" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_52_search_archivefile_with_version(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_53_search_archivefile_with_version(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version=-1" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_54_search_archivefile_with_version(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version=5" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_55_search_archivefile_with_version_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_inf=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_56_search_archivefile_with_version_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_inf=-2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_57_search_archivefile_with_version_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_inf=1&archive=35" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_58_search_archivefile_with_version_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_sup=1" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_59_search_archivefile_with_version_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_inf=-2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_60_search_archivefile_with_version_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_sup=6" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_61_search_archivefile_with_version_inf_version_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_inf=3&version_sup=1" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_62_search_archivefile_with_version_inf_version_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_inf=-1&version_sup=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_63_search_archivefile_with_version_inf_version_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?version_inf=6&version_sup=5" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_64_search_archivefile_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?status=checked" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_65_search_archivefile_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?status=not_checked" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_66_search_archivefile_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?status=not_ok" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_67_search_archivefile_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?status=not" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_68_search_archivefile_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?status=not_ok&archive=35" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_69_search_archive_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?status=checked" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_70_search_archive_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?status=not_checked" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_71_search_archive_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?status=not_ok" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_72_search_archive_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?status=not" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_73_search_archive_with_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?status=not_ok&archive=35" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_74_search_archive_with_date(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date=2019-02-26" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_75_search_archive_with_date(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date=26-02-2019" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_76_search_archive_with_date(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date=29-02-2019" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_77_search_archive_with_date(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date=26-08-2019" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_78_search_archive_with_date_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_inf=26-02-2019" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_79_search_archive_with_date_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_inf=2019-02-26" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_80_search_archive_with_date_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_inf=29-02-2019" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_81_search_archive_with_date_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_inf=01-02-1999" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_82_search_archive_with_date_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_sup=26-02-2019" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_83_search_archive_with_date_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_sup=28-02-2045" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_84_search_archive_with_date_sup(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_sup=31-02-2045" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_85_search_archive_with_date_sup_date_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_sup=01-02-2018&date_inf=22-05-2019" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_86_search_archive_with_date_sup_date_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_sup=22-05-2019&date_inf=22-05-2018" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_87_search_archive_with_date_sup_date_inf(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/search/?date_sup=01-10-1999&date_inf=01-10-2000" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)
