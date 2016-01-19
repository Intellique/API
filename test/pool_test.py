from common_test import CommonTest
from io import StringIO
import json

class PoolTest(CommonTest):
    def test_01_get_pool_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/', headers=headers)
        res = conn.getresponse()
        pools = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(pools)
        self.assertIsInstance(pools['pools'], list)

    def test_02_get_pool_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%spool/?id=%d" % (self.path, 3), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_03_get_pool_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_04_get_pool_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/?id=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_get_pool_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/?id=%d" % (self.path, 3), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_list_pool_user_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'pool/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_07_get_list_pool_admin_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?limit=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_list_pool_admin_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_list_pool_admin_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?limit=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_pool_admin_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?offset=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_delete_user_logged_as_admin_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pool/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_12_delete_admin_with_wrong_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pool/?id=test', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_13_delete_user_not_logged(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'pool/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_14_delete_user_not_admin(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE', self.path + 'pool/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status,403)

    def test_15_pool_deleted_by_admin_successfully(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pool/?id=3', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_16_admin_tries_to_delete_nonexistent_pool(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pool/?id=47', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)