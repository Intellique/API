from common_test import CommonTest
from io import StringIO
import json

class ArchiveFileTest(CommonTest):

    def test_01_get_archivefile_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/?id=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_02_get_archivefile_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/?id=%d" % (self.path, 47910), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_03_get_archivefile_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/?id=%d" % (self.path, 8), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_archivefile_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('g')
        conn.request('GET', "%sarchivefile/?id=%d" % (self.path, 28), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_05_get_archivefiles_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/?archive=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_list_archivefile_admin_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&order_by=foo' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_get_list_archivefile_admin_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&order_by=id&order_asc=bar' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_list_archivefile_admin_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&limit=foo' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_list_archivefile_admin_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&limit=0' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_archivefile_admin_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&limit=-82' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_get_list_archivefile_admin_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&offset=-82' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)
