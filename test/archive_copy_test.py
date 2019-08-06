from common_test import CommonTest
from io import StringIO
import json

class ArchiveCopyTest(CommonTest):
    def test_01_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'archive/copy/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_post_admin_user_with_no_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/copy/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_post_admin_user_with_wrong_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        copy = json.dumps({
            'archive': 'toto'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/copy/', body=copy, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_post_admin_user_with_wrong_pool_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        copy = json.dumps({
            'pool': 'toto'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/copy/', body=copy, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_post_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('archiver')
        copy = json.dumps({
            'archive': 35,
            'pool': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/copy/', body=copy, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_06_post_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        copy = json.dumps({
            'name': "",
            'archive': 57,
            'pool': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/copy/', body=copy, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        copy = json.dumps({
            'name': 'ArchiveCopyTest',
            'archive': 16,
            'pool': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/copy/', body=copy, headers=headers)
        res = conn.getresponse()
        location = res.getheader('location')
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)
        self.assertIsNotNone(location)
        self.assertIsNotNone(message)
        conn = self.newConnection()
        conn.request('GET', self.path + 'job/?id=' + str(message['job_id']), headers=cookie)
        res = conn.getresponse()
        job = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(job)
        self.assertEqual(job['job']['id'], message['job_id'])
        self.assertEqual(job['job']['name'], 'ArchiveCopyTest')
        self.assertEqual(job['job']['archive'], 16)
        self.assertEqual(job['job']['pool'], 1)

    def test_08_post_admin_user_with_right_params2(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        copy = json.dumps({
            'name': 'ArchiveCopyTest2',
            'archive': 16,
            'pool': 1,
            'nextstart': '2016-06-06 06:06:06+02'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/copy/', body=copy, headers=headers)
        res = conn.getresponse()
        location = res.getheader('location')
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)
        self.assertIsNotNone(location)
        self.assertIsNotNone(message)
        conn = self.newConnection()
        conn.request('GET', self.path + 'job/?id=' + str(message['job_id']), headers=cookie)
        res = conn.getresponse()
        job = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(job)
        self.assertEqual(job['job']['id'], message['job_id'])
        self.assertEqual(job['job']['name'], 'ArchiveCopyTest2')
        self.assertEqual(job['job']['archive'], 16)
        self.assertEqual(job['job']['pool'], 1)
        self.assertEqual(job['job']['nextstart'], '2016-06-06T04:06:06+0000')
        self.assertEqual(job['job']['options'], {})
