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
        conn.request('GET', "%spool/?id=%d" % (self.path, 5), headers=headers)
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
            'uuid': 'b2719811-bad0-466a-8c00-7e7a51c7f475',
            'name' :'EXPORT_PROVISOIRE_RUS',
            'archiveformat':1,
            'mediaformat' :2
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
            'uuid': 'ef8d47f2-a6d3-468d-89e9-f961e6c39cec',
            'name': 'EXPORT_PROVISOIRE_RUSH',
            'archiveformat': 1,
            'mediaformat': 2
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
        conn.request('GET', location, headers=cookie)
        res = conn.getresponse()
        response = res.read()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_27_post_pool_admin_using_pooltemplate(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'pooltemplate' : 1,
            'name' :'foo',
            'archiveformat':1,
            'mediaformat' :2,
            'backuppool' : False,
            'deleted' : True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 201)

    def test_28_post_pool_admin_using_pooltemplate_not_found(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'pooltemplate' : 100,
            'name' :'foo',
            'archiveformat':1,
            'mediaformat' :2,
            'backuppool' : False,
            'deleted' : True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 404)

    def test_29_put_user_not_logged(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'pool/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_30_put_basic_user_not_allowed(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data = json.dumps({
            'id':5,
            'uuid': 'b2719811-bad0-466a-8c00-7e7a51c7f475',
            'name' :'EXPORT_PROVISOIRE_RUSHS',
            'archiveformat':1,
            'mediaformat' :2
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_31_put_admin_user_with_wrong_uuid(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':5,
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_32_put_admin_user_with_no_name(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':5,
            'uuid': '42',
            'name' : None,
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_33_put_admin_user_with_wrong_name(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':5,
            'uuid': '42',
            'name' : 50,
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_34_put_admin_user_with_no_archiveformat(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':5,
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_35_put_admin_user_with_wrong_archiveformat(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':5,
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :3
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_36_put_admin_user_with_no_mediaformat(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':5,
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' : None
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_37_put_admin_user_with_wrong_mediaformat(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':5,
            'uuid': '42',
            'name' :'foo',
            'archiveformat':2,
            'mediaformat' :'foo'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_38_put_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'id': 6,
            'name': 'EXPORT_PROVISOIRE_RUSHS',
            'archiveformat': 1,
            'mediaformat': 2
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pool/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_39_basic_user_tries_to_get_deleted_pool(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'pool/?deleted=yes', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_40_basic_user_tries_to_get_only_deleted_pool(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'pool/?deleted=only', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_41_admin_user_tries_to_get_deleted_pool(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?deleted=yes', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_42_admin_user_tries_to_get_only_deleted_pool(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'pool/?deleted=only', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

