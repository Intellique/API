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
        conn.request('GET', "%sarchive/search/?owner=3" % (self.path), headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
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
        conn.request('GET', "%spool/search/?name=ARCHIVES_CAPTATIONS" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_search_user_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/search/?isadmin=%s" % (self.path, 'f'), headers=headers)
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
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 403)

    def test_13_search_user_permission_denied(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%suser/search/?isadmin=%s" % (self.path, 't'), headers=headers)
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
        conn, headers, message = self.newLoggedConnection('admin')
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
        conn.request('GET', "%spool/search/?name=%s" % (self.path, 'foo'), headers=headers)
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
        conn.request('GET', "%sarchive/search/?owner=archiver" % (self.path), headers=headers)
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
        conn.request('GET', "%sarchive/search/?owner=3&name=ArchiveModifTest" % (self.path), headers=headers)
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
        conn.request('GET', "%suser/search/?isadmin=%s&login=%s" % (self.path, 't', 'admin'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_25_search_media_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/search/?nbfiles=9" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_26_search_media_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/search/?nbfiles=95" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

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