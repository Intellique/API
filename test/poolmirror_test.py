from common_test import CommonTest
import json

class PoolMirrorTest(CommonTest):

    def test_01_get_poolmirror_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spoolmirror/?id=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_get_poolmirror_list_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spoolmirror/?limit=2" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_poolmirror_basic_user(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', "%spoolmirror/" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_04_delete_poolmirror_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%spoolmirror/?id=2" % (self.path), headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_delete_poolmirror_basic(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE', "%spoolmirror/?id=1" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_06_delete_poolmirror_wrong_input(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%spoolmirror/?id=p" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_post_poolmirror_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': '1682b37f-bbeb-41b3-bb10-a3b3aec630b9',
            'name' :'foo',
            'synchronized':True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'poolmirror/', body=data, headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 201)

    def test_08_post_poolmirror_basic_user(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data=json.dumps({
            'uuid': '1682b37f-bbeb-41b3-bb10-a3b3aec630b9',
            'name' :'foo',
            'synchronized':True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'poolmirror/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_09_post_poolmirror_wrong_input(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'uuid': 'yo',
            'name' :'foo',
            'synchronized':True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'poolmirror/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_put_poolmirror_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id' : 4,
            'name' :'bar',
            'synchronized':False
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolmirror/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_11_put_poolmirror_uuid_cannot_be_modified(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id' : 4,
            'uuid': 'yo',
            'name' :'bar',
            'synchronized':False
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolmirror/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_12_put_poolmirror_id_missing(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'name' :'bar',
            'synchronized':False
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolmirror/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_12_put_poolmirror_id_missing(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data=json.dumps({
            'name' :'bar',
            'synchronized':False
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolmirror/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_13_basic_user_tries_to_get_a_poolmirror_status_he_owns_no_pool_in(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'poolmirror/synchronize/?id=3', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_14_admin_user_gets_poolmirror_status(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'poolmirror/synchronize/?id=1', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_15_basic_user_tries_to_synchronize_a_poolmirror(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data = json.dumps({
            'id': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'poolmirror/synchronize/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_16_admin_user_synchronizes_a_poolmirror(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'id': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'poolmirror/synchronize/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 201)
