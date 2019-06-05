from common_test import CommonTest
from io import StringIO
import json

class ArchiveCheckTest(CommonTest):
    def test_01_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'archive/check/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_post_admin_user_with_no_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/check/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_post_admin_user_with_wrong_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        check = json.dumps({
            'archive': 'toto'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/check/', body=check, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_post_archiver_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        check = json.dumps({
            'archive': 47
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/check/', body=check, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_05_post_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        check = json.dumps({
            'name': '',
            'archive': "gygfaye",
            'options': {'quick_mode': 5}
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/check/', body=check, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_06_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        check = json.dumps({
            'name': 'ArchiveCheckTest',
            'archive': 2,
            'options': {'quick_mode': True}
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/check/', body=check, headers=headers)
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
        self.assertEqual(job['job']['name'], 'ArchiveCheckTest')
        self.assertEqual(job['job']['archive'], 2)
        self.assertEqual(job['job']['options'], {'quick_mode': True})

    def test_07_post_admin_user_with_right_params2(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        check = json.dumps({
            'name': None,
            'archive': 2,
            'nextstart': '2000-05-05 05:05:05+02',
            'options': {'thorough_mode': True}
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/check/', body=check, headers=headers)
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
        self.assertEqual(job['job']['name'], 'check_Mes photos')
        self.assertEqual(job['job']['archive'], 2)
        # self.assertEqual(job['job']['nextstart'], '2000-05-05 03:05:05+0000')
        self.assertEqual(job['job']['options'], {'quick_mode': False})
