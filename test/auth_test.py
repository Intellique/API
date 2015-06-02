from common_test import CommonTest
import urllib.parse

class AuthTest(CommonTest):
    def test_01_get_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_post_without_params(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_post_with_wrong_param(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'foo': 'bar'})
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_post_with_login_only(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'login': self.login});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_post_auth_ok(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'login': self.login, 'password': self.password});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_post_auth_fail(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'login': self.login, 'password': 'foo'});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_07_delete(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_08_delete_and_get(self):
        conn, headers = self.newLoggedConnection()
        conn.request('DELETE', self.path + 'auth/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
        conn = self.newConnection()
        conn.request('GET', self.path + 'auth/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_09_put(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_10_get_logged(self):
        conn, headers = self.newLoggedConnection()
        conn.request('GET', self.path + 'auth/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
