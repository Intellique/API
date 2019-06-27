from common_test import CommonTest
from io import StringIO
import json
from urllib.parse import quote

class ArchiveFileTest(CommonTest):

    def test_01_get_archivefile_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/?id=%s" % (self.path, 'foo'), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_02_get_archivefile_not_found(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/?id=%d" % (self.path, 47910), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_03_get_archivefile_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/?id=%d" % (self.path, 8), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_archivefile_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('g')
        conn.request('GET', "%sarchivefile/?id=%d" % (self.path, 28), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_05_get_archivefiles_success(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', "%sarchivefile/?archive=%d" % (self.path, 2), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_list_archivefile_admin_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&order_by=foo' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_07_get_list_archivefile_admin_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&order_by=id&order_asc=bar' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_08_get_list_archivefile_admin_wrong_limit_string(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&limit=foo' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_09_get_list_archivefile_admin_wrong_limit_zero(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&limit=0' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_archivefile_admin_wrong_limit_negative(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&limit=-82' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_get_list_archivefile_admin_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'archivefile/?archive=%d&offset=-82' % (1), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_12_delete_metadata(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE',"%sarchivefile/metadata/?id=39"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_13_delete_metadata_with_wrong_id(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE',"%sarchivefile/metadata/?id=hgurghrgh"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_14_delete_metadata_with_wrong_userId(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE',"%sarchivefile/metadata/?id=hgurghrgh"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_15_delete_metadata_with_id_not_found(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE',"%sarchivefile/metadata/?id=460"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_16_delete_metadata_with_type_not_found(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE',"%sarchivefile/metadata/?id=460"% (self.path),headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_17_delete_metadata_correct_permission(self):
        conn, headers, message = self.newLoggedConnection('archiver')
        conn.request('DELETE', "%sarchivefile/metadata/?id=8" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_18_delete_metadata_correct_permission_not_exist(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sarchive/metadata/?id=2015" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_19_delete_metadata_wrong_permission(self):
        conn, headers, message = self.newLoggedConnection('g')
        conn.request('DELETE', "%sarchivefile/metadata/?id=23" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_20_delete_metadata_with_key(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sarchivefile/metadata/?id=35&key=name,format,%s" % (self.path, quote('conserver jusqu\'au')), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_21_delete_metadata_with_key_not_exist(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', "%sarchivefile/metadata/?id=14&key=uhgiurg" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_22_delete_metadata_with_key_not_permitted(self):
        conn, headers, message = self.newLoggedConnection('g')
        conn.request('DELETE', "%sarchivefile/metadata/?id=23&key=format" % (self.path), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)
