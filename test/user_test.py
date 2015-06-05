from common_test import CommonTest
import urllib.parse

class UserTest(CommonTest):
    def test_01_delete(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_02_get_user_not_logged(self):
        conn = self.newConnection()
        userId = self.users['basic']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_03_get_admin_user_logged(self):
        conn, headers, message = self.newLoggedConnection('admin')
        userId = message['user_id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_admin_user_logged(self):
        conn, headers, message = self.newLoggedConnection('admin')
        userId = self.users['basic']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_get_basic_user_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        userId = message['user_id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_basic_user_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        userId = self.users['admin']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_07_get_list_of_users_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_08_get_list_of_users_logged_as_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_09_get_list_of_users_logged_as_basic(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_10_post(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_11_put(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    