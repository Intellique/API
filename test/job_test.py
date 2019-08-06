from common_test import CommonTest
from io import StringIO
import json

class JobTest(CommonTest):
    def test_01_get_job_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', "%sjob/?id=%d" % (self.path, 4))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_get_job_success_logged_as_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sjob/?id=%d" % (self.path, 1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_job_success_logged_as_archiver(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%sjob/?id=%d" % (self.path, 1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_job_logged_as_archiver(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', "%sjob/?id=%d" % (self.path, 67), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_05_get_list_of_jobs_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'job/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_06_get_list_of_jobs_logged_as_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['jobs_id']), message['total_rows'])

    def test_07_get_list_of_jobs_logged_as_archiver(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', self.path + 'job/', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['jobs_id']), message['total_rows'])

    def test_08_get_list_of_jobs_logged_as_archiver(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('GET', self.path + 'job/', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['jobs_id']), message['total_rows'])

    def test_09_get_list_of_jobs_logged_as_admin_with_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?order_by=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_of_jobs_logged_as_admin_with_right_order_by_and_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?order_by=id&order_asc=bar', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_get_list_of_jobs_logged_as_admin_with_wrong_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?limit=-3', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_12_get_list_of_jobs_logged_as_admin_with_wrong_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_13_get_list_of_jobs_logged_as_admin_with_wrong_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?limit=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_14_get_list_of_jobs_logged_as_admin_with_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?offset=-3', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_15_get_list_of_jobs_logged_as_admin_with_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?offset=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_16_get_list_of_jobs_logged_as_admin_with_right_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?limit=1', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['jobs_id']), message['total_rows'])

    def test_17_get_list_of_jobs_logged_as_admin_with_right_limit_and_right_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?limit=1&offset=0', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['jobs_id']), message['total_rows'])

    def test_18_get_list_of_jobs_logged_as_admin_with_right_order_by_and_right_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'job/?order_by=id&order_asc=true', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['jobs_id']), message['total_rows'])

    def test_19_delete_job_not_logged(self):
        conn = self.newConnection()
        conn.request('DELETE', "%sjob/?id=%d" % (self.path, 4))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_20_delete_job_archiver_user_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('DELETE', "%sjob/?id=%d" % (self.path, 48), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_21_delete_job_logged_as_archiver_without_params(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('DELETE', "%sjob/" % self.path, headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_22_delete_job_logged_as_admin_with_wrong_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sjob/?id=%d" % (self.path, 0), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_23_delete_job_x2_logged_as_admin_with_right_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sjob/?id=%d" % (self.path, 3), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
        conn = self.newConnection()
        conn.request('DELETE', "%sjob/?id=%d" % (self.path, 3), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_24_put_job_not_logged(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'job/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_25_put_job_logged_as_admin_without_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'job/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_26_put_job_user_archiver_not_allowed_crafted_message(self):
        conn, cookie, message = self.newLoggedConnection('archiver')
        io = StringIO()
        json.dump({
            'id': 66,
            'login': 3
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'job/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_26_put_job_logged_as_admin_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'id': 5,
            'name': 'toto',
            'nextstart': '2014-10-24 17:21:38+06:30',
            'interval': -1,
            'repetition': 0,
            'status': 'finished',
            'update': '2014-11-12 19:01:19+06:30',
            'archive': None,
            'backup': None,
            'media': 1,
            'pool': 3,
            'host': 6,
            'login': 1,
            'metadata': {},
            'options': {}
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'job/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_27_put_job_logged_as_admin_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'id': 5,
            'name': 'NEW NAME TEST 27',
            'nextstart': '2014-10-24 17:21:38+06:30',
            'interval': None,
            'repetition': 0,
            'status': 'scheduled',
            'update': '2014-11-12 19:01:19+06:30',
            'archive': None,
            'backup': None,
            'media': 1,
            'pool': 3,
            'host': 6,
            'login': 1,
            'metadata': {},
            'options': {}
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'job/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
