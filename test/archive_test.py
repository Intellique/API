from common_test import CommonTest
import json

class ArchiveTest(CommonTest):
    def test_01_get_archive_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        archives = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(archives)
        self.assertIsInstance(archives['archives'], list)

    def test_02_get_archive_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 57), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_03_get_archive_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 404), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_04_get_archive_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/?id=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_get_archive_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchive/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_list_archive_user_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_07_get_list_archive_admin_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?order_by=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_list_archive_admin_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?order_by=id&order_asc=bar', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_list_archive_admin_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_archive_admin_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_get_list_archive_admin_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?limit=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_12_get_list_archive_admin_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?offset=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_13_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_14_post_admin_user_with_no_pool_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_15_post_admin_user_with_wrong_pool_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        body = json.dumps({
            'pool': 'toto'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', body=body, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_16_post_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        body = json.dumps({
            'pool': 3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', body=body, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_17_post_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        body = json.dumps({
            'name': '',
            'files': ["/mnt/raid/rcarchives/PRODUITS_DE_DIFFUSION/CEI_DIFFUSION/20081207_MCEI_DON_CARLO_SCALA"],
            'pool': 3,
            'metadata': {},
            'options': {}
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', body=body, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_18_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        body = json.dumps({
            'name': 'ArchiveTest',
            'files': ["/var/www/nextcloud/data/emmanuel/files/NASA"],
            'pool': 3,
            'metadata': {},
            'options': {}
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'archive/', body=body, headers=headers)
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

    def test_19_put_not_logged(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_20_put_admin_user_with_no_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_21_put_admin_user_with_wrong_archive_id(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        body = json.dumps({
            'id': 'toto'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', body=body, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_22_put_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        body = json.dumps({
            'id': 57
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', body=body, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_23_put_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        body = json.dumps({
            'id': '',
            'name': 'ArchiveModifTest',
            'owner': 3,
            'metadata': {},
            'canappend': False
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', body=body, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_24_put_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        body = json.dumps({
            'id': 2,
            'name': 'ArchiveModifTest',
            'owner': 3,
            'metadata': {"foo": "bar"},
            'canappend': False,
            'deleted': False
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'archive/', body=body, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
 
    def test_25_delete_user_logged_as_admin_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_26_delete_admin_with_wrong_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'archive/?id=test', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_27_delete_user_not_logged(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'archive/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_28_delete_user_not_admin(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE', self.path + 'archive/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status,403)

    def test_29_archive_deleted_by_admin_successfully(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'archive/?id=4', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_30_admin_tries_to_delete_nonexistent_archive(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'archive/?id=404', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_31_basic_user_tries_to_get_deleted_archives(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'archive/?deleted=yes', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_32_basic_user_tries_to_get_only_deleted_archives(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'archive/?deleted=only', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_33_admin_user_tries_to_get_deleted_archives(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?deleted=yes', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_34_admin_user_tries_to_get_only_deleted_archives(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?deleted=only', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_35_get_archive_by_pool(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?pool=4', headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_36_get_archive_by_poolgroup(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archive/?poolgroup=4', headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)        