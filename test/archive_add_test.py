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
        add = json.dumps({
            'archive': 'toto'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=add, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_post_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        add = json.dumps({
            'archive': 2
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=add, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_post_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        add = json.dumps({
            'name': '',
            'files': ["/mnt/raid/rcarchives/PRODUITS_DE_DIFFUSION/CEI_DIFFUSION/20081207_MCEI_DON_CARLO_SCALA"],
            'archive': 3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=add, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_06_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        add = json.dumps({
            'name': 'ArchiveAddTest',
            'files': ["/var/www/nextcloud/data/emmanuel/files/Photos"],
            'archive': 57
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=add, headers=headers)
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
        self.assertEqual(job['job']['archive'], 57)

    def test_07_post_admin_user_with_right_params2(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        add = json.dumps({
            'name': 'ArchiveAddTest2',
            'files': ["/var/www/nextcloud/data/emmanuel/files/Photos"],
            'archive': 57,
            'nextstart': '2020-02-20 22:22:22+02'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=add, headers=headers)
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
        self.assertEqual(job['job']['archive'], 57)
        self.assertEqual(job['job']['nextstart'], '2020-02-20T20:22:22+0000')

    def test_08_post_member_tries_to_add_files_to__synchronized_archive(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        add = json.dumps({
            'files': ["/var/www/nextcloud/data/emmanuel/files/Photos"],
            'archive': 57
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/add/', body=add, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 201)

#    def test_09_post_member_tries_to_add_files_to_not_synchronized_archive(self):
#        conn, cookie, message = self.newLoggedConnection('admin')
#        add = json.dumps({
#            'files': ["/mnt/raid/shared/partage/Anime/Sintel.2010.1080p.mkv"],
#            'archive': 5
#        });
#        headers = {"Content-type": "application/json"}
#        headers.update(cookie)
#        conn.request('POST', self.path + 'archive/add/', body=add, headers=headers)
#        res = conn.getresponse()
#        conn.close()
#        self.assertEqual(res.status, 409)
