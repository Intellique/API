from common_test import CommonTest
import json

class LibraryTest(CommonTest):
    def test_01_basic_user_denied(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('GET', self.path + 'library/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_02_admin_user_succeed(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('GET', self.path + 'library/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    """
    def test_03_admin_user_no_device_found(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('GET', self.path + 'library/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)
    """

    def test_04_put_basic_user_access_denied(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data = json.dumps({
            'id':2,
            'action': 'put online'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'library/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_05_put_id_incorrect_input(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'id':'coucou',
            'action': 'put online'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'library/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_06_put_action_incorrect_input(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'id': 2,
            'action': 'coucou'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'library/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_put_no_id_specified(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'action': 'put online'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'library/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_no_action_specified(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'id': 2
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'library/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'id': 2,
            'action': 'put online'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'library/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
