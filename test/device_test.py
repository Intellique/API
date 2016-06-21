from common_test import CommonTest
from io import StringIO
import json

class DeviceTest(CommonTest):

    def test_01_get_device_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', "%sdevice/?id=%d" % (self.path, 1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_02_get_devices_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sdevice/" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_device_not_exists(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sdevice/?id=%s" % (self.path, '10'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_04_get_devices(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sdevice/?id=%s" % (self.path, '1'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)