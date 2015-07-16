from common_test import CommonTest
from io import StringIO
import json

class AuthTest(CommonTest):
    def test_01_delete(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_delete_and_get(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'auth/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
        conn = self.newConnection()
        conn.request('GET', self.path + 'auth/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_03_get_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_04_get_logged(self):
        conn, headers, res = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'auth/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_post_without_params(self):
        conn = self.newConnection()
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_06_post_with_wrong_param(self):
        conn = self.newConnection()
        io = StringIO()
        json.dump({
            'foo': 'bar'
        }, io)
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', io.getvalue(), headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_post_with_login_only(self):
        conn = self.newConnection()
        io = StringIO()
        json.dump({
            'login': self.users['admin']['login']
        }, io)
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', io.getvalue(), headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_post_auth_ok(self):
        conn = self.newConnection()
        io = StringIO()
        json.dump({
            'login': self.users['admin']['login'],
            'password': self.users['admin']['password']
        }, io)
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', io.getvalue(), headers)
        res = conn.getresponse()
        location = res.getheader('location')
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)
        self.assertIsNotNone(location)
        self.assertIsNotNone(message)
        conn = self.newConnection()
        conn.request('GET', location, headers={'Cookie': res.getheader('Set-Cookie').split(';')[0]})
        res = conn.getresponse()
        user = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(user)
        self.assertIn('user_id', user)
        self.assertEqual(user['user_id'], message['user_id'])

    def test_09_post_auth_fail(self):
        conn = self.newConnection()
        io = StringIO()
        json.dump({
            'login': self.users['admin']['login'],
            'password': 'foo'
        }, io)
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', io.getvalue(), headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_10_post_and_get(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Accept": "application/json"}
        headers.update(cookie)
        conn.request('GET', self.path + 'user/?id=2', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
        conn = self.newConnection()
        conn.request('GET', self.path + 'auth/', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIn('user_id', message)
        self.assertEqual(message['user_id'], self.users['admin']['id'])

    def test_11_put(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    
