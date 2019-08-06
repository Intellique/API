from common_test import CommonTest
from io import StringIO
import copy, hashlib, json, unittest

class UserTest(CommonTest):
    def test_01_get_no_admin(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%suser/update/?action=deactivate&login=thierry" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_02_get_deactivate_user_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/update/?action=deactivate&login=thierry" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_deactivate_user_not_found_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/update/?action=deactivate&login=moi" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_04_get_deactivate_user_wrong_params_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/update/?action=deactivate" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_05_get_add_key_not_admin(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%suser/update/?action=key&login=key&key=test" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_06_get_add_key_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/update/?action=key&login=thierry&key=ryhryhrhrhhryhr" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_07_get_add_key_user_not_found_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/update/?action=key&login=key&key=test" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_08_get_add_key_wrong_params_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/update/?action=key" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_add_key_no_admin(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%suser/update/?action=key&login=thierry&key=test" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_10_get_activate_wrong_params(self):
        conn = self.newConnection()
        conn.request('GET', "%suser/update/?action=activate" % (self.path))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_get_activate_wrong_params(self):
        conn = self.newConnection()
        conn.request('GET', "%suser/update/?action=activate&key=key" % (self.path))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_12_get_activate(self):
        conn = self.newConnection()
        conn.request('GET', "%suser/update/?action=activate&key=ryhryhrhrhhryhr" % (self.path))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_13_get_activate_no_key(self):
        conn = self.newConnection()
        conn.request('GET', "%suser/update/?action=activate&key=ryhryhrhrhhryhr" % (self.path))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)
