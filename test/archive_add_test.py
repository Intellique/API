from common_test import CommonTest
from io import StringIO
import json

class ArchiveAddTest(CommonTest):
    def test_01_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'archive/add/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_post_admin_user_with_no_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_post_admin_user_with_wrong_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'archive': 'toto'
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_post_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        io = StringIO()
        json.dump({
            'archive': 2
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_05_post_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'name': '',
            'files': ["/mnt/raid/rcarchives/PRODUITS_DE_DIFFUSION/CEI_DIFFUSION/20081207_MCEI_DON_CARLO_SCALA"],
            'archive': 3
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_06_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'name': 'ArchiveAddTest',
            'files': ["/mnt/raid/shared/partage/5a7-resto/130012/mnt/raid/TV RENNES/TVR-01 13/TVR-METROPOLITAIN-2008-04-04-S1.mov"],
            'archive': 2
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        location = res.getheader('location')
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)
        self.assertIsNotNone(location)
        self.assertIsNotNone(message)
        conn = self.newConnection()
        conn.request('GET', location, headers=cookie)
        res = conn.getresponse()
        job = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(job)
        self.assertEqual(job['job']['id'], message['job_id'])
        self.assertEqual(job['job']['name'], 'ArchiveAddTest')
        self.assertEqual(job['job']['archive'], 2)

    def test_07_post_admin_user_with_right_params2(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'name': 'ArchiveAddTest2',
            'files': ["/mnt/raid/shared/partage/5a7-resto/130012/mnt/raid/TV RENNES/TVR-01 13/TVR-METROPOLITAIN-2008-04-04-S1.mov"],
            'archive': 2,
            'nextstart': '2020-02-20 22:22:22+02'
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        location = res.getheader('location')
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)
        self.assertIsNotNone(location)
        self.assertIsNotNone(message)
        conn = self.newConnection()
        conn.request('GET', location, headers=cookie)
        res = conn.getresponse()
        job = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(job)
        self.assertEqual(job['job']['id'], message['job_id'])
        self.assertEqual(job['job']['name'], 'ArchiveAddTest2')
        self.assertEqual(job['job']['archive'], 2)
        self.assertEqual(job['job']['nextstart'], '2020-02-20T20:22:22+0000')