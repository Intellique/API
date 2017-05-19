from common_test import CommonTest
from io import StringIO
import json

class PoolgroupTest(CommonTest):

    def test_01_get_poolgroup_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%spoolgroup/?id=%s" % (self.path, 3), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_02_get_poolgroup_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spoolgroup/?id=%s" % (self.path, 1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_poolgroup_wrong_input(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spoolgroup/?id=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_04_get_poolgroup_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%spoolgroup/" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    """def test_05_put_poolgroup_success_less_pools(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'poolgroup':1,
            'pools':'6'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolgroup/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)"""

    def test_06_put_poolgroup_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'poolgroup':1,
            'pools':'8,3,7,6'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolgroup/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_07_put_poolgroup_non_admin_user(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data=json.dumps({
            'poolgroup':1,
            'pools':'6,3,7,5'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolgroup/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_08_put_poolgroup_wrong_parameters(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'pooflgroup':1,
            'poolgs':'6,3,7,5'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolgroup/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_put_poolgroup_wrong_type(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'poolgroup':'1',
            'pools':'6,3,foo'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolgroup/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_put_poolgroup_pool_does_not_exist(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data=json.dumps({
            'poolgroup':'1',
            'pools':'6,3,15'
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'poolgroup/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)
