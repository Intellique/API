from common_test import CommonTest
import json

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
        conn.request('GET', "%sjob/search/?name=%s" % (self.path, 'ArchiveAddTest'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_search_pool_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/search/?name=EXPORT_PROVISOIRE_RUSH" % (self.path), headers=headers)
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

    """
    def test_11_search_archivefile_permission_denied(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%sarchivefile/search/?type=%s" % (self.path, 'directory'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_12_search_pool_permission_denied(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%spool/search/?name=%s" % (self.path, 'ARCHIVES_CAPTATIONS'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)
    """

    def test_13_search_user_permission_denied(self):
        conn, headers, message = self.newLoggedConnection('archiver')
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

    def test_15_search_archivefile_not_found(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%sarchivefile/search/?name=%s" % (self.path, 'foo'), headers=headers)
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

    def test_21_search_archive_success_multiple_args(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/search/?owner=1&name=MONARCHIVE&deleted=yes" % (self.path), headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 200)

    def test_22_search_job_success_multiple_args(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/search/?name=%s&pool=%s" % (self.path, 'ArchiveCopyTest2', '7'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_23_search_pool_success_multiple_args(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/search/?name=ARCHIVES_CAPTATIONS&mediaformat=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

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
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', "%smedia/search/?nbfiles=9" % (self.path), headers=headers)
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

    def test_32_search_poolmirror_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spoolmirror/search/?name=test" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

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
        conn.request('GET', "%spooltemplate/search/?name=foo" % (self.path), headers=headers)
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
