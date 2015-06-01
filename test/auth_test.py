from common_test import CommonTest
import urllib.parse

class AuthTest(CommonTest):
    def test_1_get(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_2_post_without_params(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_3_post_with_wrong_param(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'foo': 'bar'})
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_4_post_with_login_only(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'login': self.login});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_5_post_auth_ok(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'login': self.login, 'password': self.password});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_6_post_auth_ok_2(self):
        conn, res = self.newLoggedConnection()
        self.assertEqual(res.status, 200)
        if (conn != None):
            conn.close()

    def test_7_post_auth_fail(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'login': self.login, 'password': 'foo'});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_8_delete(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_9_put(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'auth/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

