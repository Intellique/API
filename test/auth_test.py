from common_test import CommonTest
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
        params = json.dumps({
            'foo': 'bar'
        })
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_post_with_login_only(self):
        conn = self.newConnection()
        params = json.dumps({
            'login': self.users['admin']['login']
        })
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_post_auth_ok(self):
        conn = self.newConnection()
        params = json.dumps({
            'login': self.users['admin']['login'],
            'password': self.users['admin']['password'],
            'apikey': self.apikey
        })
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', params, headers)
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
        params = json.dumps({
            'login': self.users['admin']['login'],
            'password': 'foo',
            'apikey': self.apikey
        })
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_10_post_and_get(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Accept": "application/json"}
        headers.update(cookie)
        conn.request('GET', self.path + 'user/?id=1', headers=headers)
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

    def test_12_post_wrong_apikey(self):
        conn = self.newConnection()
        params = json.dumps({
            'login': self.users['admin']['login'],
            'password': self.users['admin']['password'],
            'apikey': '0d58efeb-e322-45b6-aa9f-bd0d5cf45d49',
        })
        headers = {"Content-type": "application/json"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_13_created(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Accept": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'auth/token/',None, headers)
        res = conn.getresponse()
        headers = res.getheader("Authorization")
        self.assertIsNotNone(headers)
        self.assertEqual(res.status, 201)
        conn.close()

    def test_14_token_validated(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Accept": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'auth/token/',None, headers)
        res = conn.getresponse()
        token = res.getheader("Authorization")
        self.assertIsNotNone(token)
        self.assertEqual(res.status, 201)
        conn.close()
        conn = self.newConnection()
        tokenHeader = {"Authorization": token}
        conn.request('POST', self.path + 'auth/',None, tokenHeader)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 201)

    def test_15_token_expired(self):
        conn = self.newConnection()
        headers =  {"Authorization": """Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.
                     eyJpc3MiOiJTdG9yaXFPbmVCRSIsImxvZ2luIjoxLCJpYXQiOjE1MjI3NjU3NjgsImV4cCI6MTUyMjc2NTc4OH0.
                     nm5SdYznBRrbvWeJ9tVRfiBWPpPJOWYkeuJ6DZgGTyw
                   """}
        conn.request('POST', self.path + 'auth/',None, headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_16_token_unsigned(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Accept": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'auth/token/',None, headers)
        res = conn.getresponse()
        token = res.getheader("Authorization")
        self.assertIsNotNone(token)
        self.assertEqual(res.status, 201)
        conn.close()
        conn = self.newConnection()
        s = '.'
        token = token.split(s)
        tokenSeq = (token[0],token[1])
        token = s.join(tokenSeq)+'.'
        tokenHeader = {"Authorization": token}
        conn.request('POST', self.path + 'auth/',None, tokenHeader)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)
