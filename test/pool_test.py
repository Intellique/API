from common_test import CommonTest
from io import StringIO
import json

class PoolTest(CommonTest):
    def test_01_get_pool_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/', headers=headers)
        res = conn.getresponse()
        pools = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(pools)
        self.assertIsInstance(pools['pools'], list)

    def test_02_get_pool_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%spool/?id=%d" % (self.path, 3), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_03_get_pool_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_04_get_pool_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/?id=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_get_pool_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spool/?id=%d" % (self.path, 3), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_list_pool_user_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'pool/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_07_get_list_pool_admin_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?limit=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_list_pool_admin_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_list_pool_admin_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?limit=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_pool_admin_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?offset=-82', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_delete_user_logged_as_admin_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pool/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_12_delete_admin_with_wrong_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pool/?id=test', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_13_delete_user_not_logged(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'pool/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_14_delete_user_not_admin(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE', self.path + 'pool/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status,403)

    def test_15_pool_deleted_by_admin_successfully(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pool/?id=3', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_16_admin_tries_to_delete_nonexistent_pool(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pool/?id=47', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_17_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'pool/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_18_post_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data = json.dumps({
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_19_post_admin_user_with_wrong_uuid(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_20_post_admin_user_with_no_name(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': '42',
            'name' : None,
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_21_post_admin_user_with_wrong_name(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': '42',
            'name' : 50,
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_22_post_admin_user_with_no_archiveformat(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_23_post_admin_user_with_wrong_archiveformat(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_24_post_admin_user_with_no_mediaformat(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' : None
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_25_post_admin_user_with_wrong_mediaformat(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :'foo'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_26_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': 'b2719811-bad0-466a-8c00-7e7a51c7f475',
            'name' :'EXPORT_PROVISOIRE_RUS',
            'archiveformat':1,
            'mediaformat' :2
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        location = res.getheader('location')
        conn.close()
        self.assertEqual(res.status, 201)
        self.assertIsNotNone(location)
        conn = self.newConnection()
        #conn.set_debuglevel(1)
        conn.request('GET', location, headers=headers)
        res = conn.getresponse()
        response = res.read()
        conn.close()
        print(response)
        self.assertEqual(res.status, 200)


