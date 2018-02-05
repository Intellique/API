from common_test import CommonTest
import json

class FragmentationTest(CommonTest):
    def test_01_put_not_logged(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'media/fragmentation/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_02_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'media/fragmentation/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_03_delete_not_logged(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'media/fragmentation/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 405)

    def test_04_get_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'media/fragmentation/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_05_get_basic_user_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'media/fragmentation/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_06_get_admin_user_with_no_media_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('GET', self.path + 'media/fragmentation/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_get_admin_user_with_wrong_media_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'archive': 'toto'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('GET', self.path + 'media/fragmentation/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_media_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'media/fragmentation/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)







