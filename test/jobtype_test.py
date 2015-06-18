from common_test import CommonTest

class JobTypeTest(CommonTest):
    def test_01_get_jobtype(self):
        conn = self.newConnection()
        conn.request('GET', "%sjobtype/" % (self.path))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)