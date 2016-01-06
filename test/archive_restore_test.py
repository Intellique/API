from common_test import CommonTest
from io import StringIO
import json

class ArchiveRestoreTest(CommonTest):
    def test_01_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'archive/restore/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_post_admin_user_with_no_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/restore/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_post_admin_user_with_wrong_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        restore = json.dumps({
            'archive': 'toto'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/restore/', body=restore, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_post_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        restore = json.dumps({
            'archive': 2
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/restore/', body=restore, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_05_post_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        restore = json.dumps({
            'name': '',
            'files': ["/mnt/raid/rcarchives/PRODUITS_DE_DIFFUSION/CEI_DIFFUSION/20081207_MCEI_DON_CARLO_SCALA"],
            'archive': 3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/restore/', body=restore, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_06_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        restore = json.dumps({
            'name': 'ArchiveRestoreTest',
            'files': ["/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD"],
            'archive': 2
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/restore/', body=restore, headers=headers)
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
        self.assertEqual(job['job']['name'], 'ArchiveRestoreTest')
        self.assertEqual(job['job']['archive'], 2)

    def test_07_post_admin_user_with_right_params2(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        restore = json.dumps({
            'name': 'ArchiveRestoreTest2',
            'files': ["/mnt/raid/rcarchives/Archives_Audiovisuels/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN/20060614_083_OESC_AMON_LE_VICTORIEUX_C_BARBOTIN_DVD"],
            'archive': 2,
            'destination': '/mnt/raid/backup/',
            'nextstart': '2016-01-01 11:09:09+02'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/restore/', body=restore, headers=headers)
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
        self.assertEqual(job['job']['name'], 'ArchiveRestoreTest2')
        self.assertEqual(job['job']['archive'], 2)
        self.assertEqual(job['job']['nextstart'], '2016-01-01T09:09:09+0000')
