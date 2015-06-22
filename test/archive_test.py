from common_test import CommonTest

class ArchiveTest(CommonTest):
    def test_01_get_archive_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_get_archive_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_03_get_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_list_archive_user_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_05_get_list_archive_admin_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?order_by=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_06_get_list_archive_admin_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?order_by=id&order_asc=bar', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_get_list_archive_admin_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_list_archive_admin_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_list_archive_admin_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_archive_admin_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?offset=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

