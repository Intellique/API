from common_test import CommonTest
from io import StringIO
import json, unittest

class ScriptTest(CommonTest):

	def test_01_get_script_wrong_id(self):
		conn = self.newConnection()
		conn.request('GET', self.path + 'script/?id=a')
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 400)

	def test_02_get_script_not_found(self):
		conn = self.newConnection()
		conn.request('GET', self.path + 'script/?id=5')
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 404)

	def test_03_get_script_found(self):
		conn = self.newConnection()
		conn.request('GET', self.path + 'script/?id=2')
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 200)

	def test_04_get_script_without_param(self):
		conn = self.newConnection()
		conn.request('GET', self.path + 'script/')
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 200)

	def test_05_get_script_by_pool_id(self):
		conn = self.newConnection()
		conn.request('GET', self.path + 'script/?pool=1')
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 200)

	def test_06_get_script_by_pool_id(self):
		conn = self.newConnection()
		conn.request('GET', self.path + 'script/?pool=a')
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 400)

	@unittest.skip("demonstrating skipping")
	def test_07_get_script_by_pool_id(self):
		conn = self.newConnection()
		conn.request('GET', self.path + 'script/?pool=48')
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 404)

	def test_08_add_script(self):
		conn, headers, message = self.newLoggedConnection('admin')
		conn.request('GET', "%sscript/action/?action=add&script_id=2&pool=1&jobtype=7" % (self.path), headers=headers)
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 200)

	def test_09_add_script(self):
		conn, headers, message = self.newLoggedConnection('admin')
		conn.request('GET', "%sscript/action/?action=add&script_id=2&pool=1&jobtype=99" % (self.path), headers=headers)
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 400)

	def test_10_add_script(self):
		conn, headers, message = self.newLoggedConnection('admin')
		conn.request('GET', "%sscript/action/?action=add&script_id=2&pool=1&jobtype=7" % (self.path), headers=headers)
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 404)

	def test_11_delete_script(self):
		conn, headers, message = self.newLoggedConnection('admin')
		conn.request('GET', "%sscript/action/?action=delete&script_id=2&pool=1&jobtype=7" % (self.path), headers=headers)
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 200)

	def test_12_delete_script(self):
		conn, headers, message = self.newLoggedConnection('admin')
		conn.request('GET', "%sscript/action/?action=delete&script_id=2&pool=1&jobtype=70" % (self.path), headers=headers)
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 400)

	def test_13_delete_script(self):
		conn, headers, message = self.newLoggedConnection('admin')
		conn.request('GET', "%sscript/action/?action=delete&script_id=2&pool=1&jobtype=9" % (self.path), headers=headers)
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 404)

	def test_14_admin(self):
		conn = self.newConnection()
		conn.request('GET', self.path + 'script/action/?action=add&script_id=2&pool=1&jobtype=9')
		res = conn.getresponse()
		conn.close()
		self.assertEqual(res.status, 401)
