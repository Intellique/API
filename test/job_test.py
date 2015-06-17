from common_test import CommonTest
import unittest

class JobTest(CommonTest):
    def test_01_get_job_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', "%sjob/?id=%d" % (self.path, 4))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    @unittest.skip('Not yet implemented')
    def test_02_get_job_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_job_success_logged_as_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/?id=%d" % (self.path, 4), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_job_success_logged_as_basic(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', "%sjob/?id=%d" % (self.path, 4), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_get_job_logged_as_archiver(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%sjob/?id=%d" % (self.path, 4), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)