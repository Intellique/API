from common_test import CommonTest
from io import StringIO
import json

class ArchiveFileSearchTest(CommonTest):
    def test_01_get_archivefile_given_metadata(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/search/?meta=(observation=youpi)', headers=headers)
        res = conn.getresponse()
        archives = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_get_archivefile_given_metadata(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/search/?meta=(observation=abc)', headers=headers)
        res = conn.getresponse()
        archives = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 404)

    def test_03_get_archivefile_given_metadata_query(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/search/?meta=(observation=youpi|observation=hoy)', headers=headers)
        res = conn.getresponse()
        archives = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_archivefile_given_metadata_query(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/search/?meta=(observation=youpi,observation=hoy)', headers=headers)
        res = conn.getresponse()
        archives = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 404)

    def test_05_get_archivefile_given_metadata_query(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/search/?meta=(observation=youpi&observation=hoy)', headers=headers)
        res = conn.getresponse()
        archives = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 500)
