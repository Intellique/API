from common_test import CommonTest
import json, unittest
from urllib.parse import quote

class Archive_Metadata_Test(CommonTest):

    def test_01_delete_metadata(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE',"%sarchive/metadata/?id=28"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_02_delete_metadata_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE',"%sarchive/metadata/?id=gyhffby"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_03_delete_metadata_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE',"%sarchive/metadata/?id=gyhffby"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)


    def test_04_delete_metadata_id_not_found(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE',"%sarchive/metadata/?id=490"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_05_delete_metadata_userId_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE',"%sarchive/metadata/?id=28"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_6_delete_metadata_correct_permission(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sarchive/metadata/?id=12" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_7_delete_metadata_correct_permission_not_exist(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sarchive/metadata/?id=2015" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_8_delete_metadata_correct_permission(self):
        conn, headers, message = self.newLoggedConnection('g')
        conn.request('DELETE', "%sarchive/metadata/?id=29" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_9_delete_metadata_with_key(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sarchive/metadata/?id=14&key=%s" % (self.path, quote('conserver jusqu\'au')), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)


    def test_10_delete_metadata_with_key_not_exist(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sarchive/metadata/?id=140&key=name" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_11_delete_metadata_with_wrong_permission(self):
        conn, headers, message = self.newLoggedConnection('g')
        conn.request('DELETE', "%sarchive/metadata/?id=29&key=format,name" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)
