from common_test import CommonTest
from io import StringIO
import json

class MediaTest(CommonTest):
    def test_01_get_media_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', "%smedia/?id=%d" % (self.path, 1))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_get_media_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?id=%d" % (self.path, 520), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_03_get_media_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?id=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_get_media_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?id=%d" % (self.path, 1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_get_medias_by_pool_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', "%smedia/?pool=%d" % (self.path, 5))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_06_get_medias_by_pool_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%smedia/?pool=%d" % (self.path, 5), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_07_get_medias_by_pool_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%d&limit=%s" % (self.path, 5, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_medias_by_pool_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%d&limit=%d" % (self.path, 5, 0), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_medias_by_pool_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%d&limit=%d" % (self.path, 5, -82), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_medias_by_pool_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%d&offset=%d" % (self.path, 5, -82), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_get_medias_by_pool_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%d" % (self.path, 5), headers=headers)
        res = conn.getresponse()
        medias = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(medias)
        self.assertIsInstance(medias['medias'], list)

    def test_12_get_medias_without_pool_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', "%smedia/?pool=%s" % (self.path, 'null'))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_13_get_medias_without_pool_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%s&limit=%s" % (self.path, 'null', 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_14_get_medias_without_pool_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%s&limit=%d" % (self.path, 'null', 0), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_15_get_medias_without_pool_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%s&limit=%d" % (self.path, 'null', -82), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_16_get_medias_without_pool_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%s&offset=%d" % (self.path, 'null', -82), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_17_get_medias_without_pool_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%s" % (self.path, 'null'), headers=headers)
        res = conn.getresponse()
        medias = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(medias)
        self.assertIsInstance(medias['medias'], list)

    def test_18_get_medias_without_pool_by_mediaformat_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?pool=%s&mediaformat=%d" % (self.path, 'null', 1), headers=headers)
        res = conn.getresponse()
        medias = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(medias)
        self.assertIsInstance(medias['medias'], list)

    def test_19_get_medias_by_poolgroup_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', "%smedia/" % (self.path))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_20_get_medias_by_poolgroup_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?limit=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_21_get_medias_by_poolgroup_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?limit=%d" % (self.path, 0), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_22_get_medias_by_poolgroup_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?limit=%d" % (self.path, -82), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_23_get_medias_by_poolgroup_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/?offset=%d" % (self.path, -82), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_24_get_medias_by_poolgroup_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%smedia/" % (self.path), headers=headers)
        res = conn.getresponse()
        medias = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(medias)
        self.assertIsInstance(medias['medias'], list)

    def test_25_put_not_logged(self):
        conn = self.newConnection()
        conn.request('PUT', "%smedia/" % (self.path))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_26_put_basic_user_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('PUT', "%smedia/" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_27_put_media_wrong_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = { 'content-type': 'application/json'}
        headers.update(cookie)
        media = { 'id': True }
        conn.request('PUT', "%smedia/" % (self.path), body=json.dumps(media), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_28_put_media_not_found(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = { 'content-type': 'application/json'}
        headers.update(cookie)
        media = { 'id': 36000 }
        conn.request('PUT', "%smedia/" % (self.path), body=json.dumps(media), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_29_put_media_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = { 'content-type': 'application/json'}
        headers.update(cookie)
        media = {
            'id': 3,
            'name': 'testeu',
            'label': 'labl',
        }
        conn.request('PUT', "%smedia/" % (self.path), body=json.dumps(media), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_30_put_media_without_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = { 'content-type': 'application/json'}
        headers.update(cookie)
        media = { 'name': 'bidon' }
        conn.request('PUT', "%smedia/" % (self.path), body=json.dumps(media), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)
