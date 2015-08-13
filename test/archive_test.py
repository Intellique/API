from common_test import CommonTest
from io import StringIO
import json

class ArchiveTest(CommonTest):
    def test_01_get_archive_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_get_archive_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_03_get_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_list_archive_user_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_05_get_list_archive_admin_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?order_by=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_06_get_list_archive_admin_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?order_by=id&order_asc=bar', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_get_list_archive_admin_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_list_archive_admin_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_list_archive_admin_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_archive_admin_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?offset=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_12_post_admin_user_with_no_pool_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_13_post_admin_user_with_wrong_pool_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'pool': 'toto'
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_14_post_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        io = StringIO()
        json.dump({
            'pool': 3
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_15_post_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'name': '',
            'files': ["/mnt/raid/rcarchives/PRODUITS_DE_DIFFUSION/CEI_DIFFUSION/20081207_MCEI_DON_CARLO_SCALA"],
            'pool': 3,
            'metadata': {},
            'options': {}
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_16_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'name': 'ArchiveTest',
            'files': ["/mnt/raid/shared/partage/5a7-resto/130007/mnt/raid/VERS LTO DOSSIER SUJET C000/TVR1050-TRANSPORT CIRCULATION-S10.mov"],
            'pool': 3,
            'metadata': {},
            'options': {}
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', body=io.getvalue(), headers=headers)
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
        self.assertEqual(job['job']['name'], 'ArchiveTest')
        self.assertEqual(job['job']['pool'], 3)

    def test_17_put_not_logged(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_18_put_admin_user_with_no_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_19_put_admin_user_with_wrong_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'id': 'toto'
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_20_put_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        io = StringIO()
        json.dump({
            'id': 2
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_21_put_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'id': '',
            'name': 'ArchiveModifTest',
            'owner': 3,
            'metadata': {},
            'canappend': False
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_22_put_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'id': 2,
            'name': 'ArchiveModifTest',
            'owner': 3,
            'metadata': {},
            'canappend': False,
            'deleted': False
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
