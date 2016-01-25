from common_test import CommonTest
import json

class MediaformatTest(CommonTest):
    def test_01_get_media_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'mediaformat/', headers=headers)
        res = conn.getresponse()
        medias = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_get_list_media_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'mediaformat/?offset=-100', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_get_media_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smediaformat/?id=%d" % (self.path, 42000), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_04_get_media_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smediaformat/?id=%s" % (self.path, 'test'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_get_media_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smediaformat/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_list_media_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'mediaformat/?limit=-100', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_get_list_media_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'mediaformat/?order_by=test', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_list_media_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'mediaformat/?order_by=id&order_asc=bar', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_list_media_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'mediaformat/?limit=test', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_media_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'mediaformat/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

