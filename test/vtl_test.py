from common_test import CommonTest
import json

class VTLTest(CommonTest):
    def test_01_post_vtl_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'uuid': 'b2719811-bad0-466a-8c00-7e7a51c7f475',
            'path': '/mnt/vtl/VTL',
            'prefix': 'VTL',
            'nbslots': 8,
            'nbdrives': 2,
            'deleted': False,
            'mediaformat': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'vtl/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_post_vtl_wrong_input(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'uuid': 'b2719811-bad0-466a-8c00-7e7a51c7f475',
            'path': '/mnt/vtl/VTL',
            'prefix': 'VTL',
            'foo': 8,
            'nbdrives': 2,
            'deleted': False,
            'mediaformat': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'vtl/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_post_vtl_basic_user(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data = json.dumps({
            'uuid': 'b2719811-bad0-466a-8c00-7e7a51c7f475',
            'path': '/mnt/vtl/VTL',
            'prefix': 'VTL',
            'nbslots': 8,
            'nbdrives': 2,
            'deleted': False,
            'mediaformat': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'vtl/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_04_put_vtl_success(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'id':2,
            'path': '/mnt/vtl/VTLI',
            'prefix': 'VTLI',
            'nbslots': 20,
            'nbdrives': 3,
            'deleted': True,
            'mediaformat': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'vtl/', body=data, headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        print(message)
        self.assertEqual(res.status, 200)

    def test_05_put_vtl_basic_user(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        data = json.dumps({
            'id':2,
            'path': '/mnt/vtl/VTLI',
            'prefix': 'VTLI',
            'nbslots': 20,
            'nbdrives': 3,
            'deleted': True,
            'mediaformat': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'vtl/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_06_put_vtl_wrong_input(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'foo':2,
            'path': '/mnt/vtl/VTLI',
            'prefix': 'VTLI',
            'nbslots': 20,
            'nbdrives': 3,
            'deleted': True,
            'mediaformat': 1
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'vtl/', body=data, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_delete_vtl_wrong_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'vtl/?id=test', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_delete_vtl_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'vtl/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_delete_vtl_basic_user(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE', self.path + 'vtl/?id=1', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_10_delete_vtl_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'vtl/?id=1', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        print(message)
        self.assertEqual(res.status, 200)