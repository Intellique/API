from common_test import CommonTest
import json

class MetadataTest(CommonTest):

    def test_01_get_metadata_pool_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/metadata/?id=6" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_get_metadata_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/metadata/?id=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_metadata_user_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/metadata/?id=3" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_metadata_job_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/metadata/?id=8" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_get_metadata_archivefile_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/metadata/?id=6" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_metadata_key_pool_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/metadata/?id=6&key=NOMENCLATURE" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    """def test_07_get_metadata_key_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/metadata/?id=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)"""

    def test_08_get_metadata_key_user_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/metadata/?id=3&key=showHelp" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    """def test_09_get_metadata_key_job_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/metadata/?id=8" % (self.path), headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 200)

    def test_10_get_metadata_key_archivefile_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/metadata/?id=6" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)"""

    def test_11_get_metadata_pool_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/metadata/?id=7889" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_12_get_metadata_archive_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/metadata/?id=7889" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_13_get_metadata_user_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/metadata/?id=7889" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_14_get_metadata_job_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/metadata/?id=7889" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_15_get_metadata_archivefile_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/metadata/?id=7889" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_16_get_metadata_pool_wrong_input(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/metadata/?id=foo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_17_get_metadata_archive_wrong_input(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/metadata/?id=foo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_18_get_metadata_user_wrong_input(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/metadata/?id=foo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_19_get_metadata_job_wrong_input(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/metadata/?id=foo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_20_get_metadata_archivefile_wrong_input(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/metadata/?id=foo" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)
