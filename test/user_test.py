from common_test import CommonTest
from io import StringIO
import urllib.parse, json, unittest

class UserTest(CommonTest):
    def test_01_get_user_not_logged(self):
        conn = self.newConnection()
        userId = self.users['basic']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_get_admin_user_logged(self):
        conn, headers, message = self.newLoggedConnection('admin')
        userId = message['user_id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_admin_user_logged(self):
        conn, headers, message = self.newLoggedConnection('admin')
        userId = self.users['basic']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_basic_user_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        userId = message['user_id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_get_basic_user_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        userId = self.users['admin']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_06_get_list_of_users_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_07_get_list_of_users_logged_as_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_08_get_list_of_users_logged_as_basic(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_09_get_list_of_users_logged_as_admin_with_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?order_by=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_of_users_logged_as_admin_with_right_order_by_and_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?order_by=id&order_asc=bar', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_get_list_of_users_logged_as_admin_with_wrong_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?limit=-3', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_12_get_list_of_users_logged_as_admin_with_wrong_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_13_get_list_of_users_logged_as_admin_with_wrong_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?limit=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_14_get_list_of_users_logged_as_admin_with_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?offset=-3', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_15_get_list_of_users_logged_as_admin_with_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?offset=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_16_post(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_17_post_basic_user_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('POST', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_18_post_admin_user_post_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('POST', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_19_post_admin_user_post_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'login': 'toto',
            'password': 'toto79',
            'fullname': 'la tête à toto'
        }, io);
        body = urllib.parse.urlencode({'user': io.getvalue()});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        headers.update(cookie)
        conn.request('POST', self.path + 'user/', body=body, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_20_post_admin_user_post_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'login': 'toto',
            'password': 'toto79',
            'fullname': 'la tête à toto',
            'email': 'toto@toto.com',
            'homedirectory': '/mnt/raid',
            'isadmin': False,
            'canarchive': True,
            'canrestore': True,
            'meta': {'Description': 'Toto est super content', 'Format': 'totomobile'},
            'poolgroup': 3,
            'disabled': False
        }, io);
        body = urllib.parse.urlencode({'user': io.getvalue()});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        headers.update(cookie)
        conn.request('POST', self.path + 'user/', body=body, headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIn('user_id', message)
        last_user_created = message['user_id']
        conn = self.newConnection()
        conn.request('DELETE', "%suser/?id=%d" % (self.path, last_user_created), headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
        conn = self.newConnection()
        conn.request('DELETE', "%suser/?id=%d" % (self.path, last_user_created), headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_21_put(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_22_delete_user_not_logged(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_23_delete_user_logged_as_admin_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_24_delete_user_logged_suicide(self):
        conn, headers, message = self.newLoggedConnection('admin')
        userId = message['user_id']
        conn.request('DELETE', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_25_delete_user_logged_as_basic(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)