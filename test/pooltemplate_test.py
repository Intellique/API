from common_test import CommonTest
import json

class PoolTemplate(CommonTest):
    def test_01_post_pooltemplate_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'name': 'test',
            'autocheck': 'none',
            'lockcheck': True,
            'growable': True,
            'unbreakablelevel': 'none',
            'rewritable': False,
            'createproxy' : True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pooltemplate/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 201)

    def test_02_post_pooltemplate_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'name': 'test2',
            'autocheck': 'none',
            'lockcheck': True,
            'growable': True,
            'unbreakablelevel': 'none',
            'rewritable': False,
            'createproxy' : True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pooltemplate/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 201)

    def test_03_post_pooltemplate_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'name': 'test3',
            'autocheck': 'none',
            'lockcheck': True,
            'growable': True,
            'unbreakablelevel': 'none',
            'rewritable': False,
            'createproxy' : True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pooltemplate/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 201)

    def test_02_post_pooltemplate_not_permitted(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data = json.dumps({
            'name': 'test',
            'autocheck': 'none',
            'lockcheck': True,
            'growable': True,
            'unbreakablelevel': 'none',
            'rewritable': False,
            'createproxy' : True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pooltemplate/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_03_post_pooltemplate_wrong_input(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'foo': 'test',
            'foo': 'none',
            'lockcheck': True,
            'growable': True,
            'unbreakablelevel': 'none',
            'rewritable': False,
            'createproxy' : True
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'pooltemplate/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_put_admin_name_already_existing(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':3,
            'name' :'test3'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pooltemplate/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_put_basic_not_permitted(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data=json.dumps({
            'id':2,
            'name' :'foo'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pooltemplate/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_06_put_admin_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'id':2,
            'name' : 'foo',
            'growable' : False,
            'unbreakablelevel' : 'archive'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'pooltemplate/', body=data, headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        print(message)
        self.assertEqual(res.status, 200)

    def test_07_get_pooltemplate_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spooltemplate/?id=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_08_get_list_pooltemplate_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spooltemplate/" % (self.path), headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        print(message)
        self.assertEqual(res.status, 200)

    def test_09_delete_pooltemplate_admin_with_wrong_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pooltemplate/?id=test', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_delete_pooltemplate_basic_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE', self.path + 'pooltemplate/?id=test', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_11_delete_pooltemplate_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pooltemplate/?id=1', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_12_delete_pooltemplate_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'pooltemplate/?id=50', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)
