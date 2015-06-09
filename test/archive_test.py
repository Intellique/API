from common_test import CommonTest

class ArchiveTest(CommonTest):
    def test_01_get_archive_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_get_archive_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_get_archive_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 200), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_04_get_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 500), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

