from common_test import CommonTest
from io import StringIO
import json

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
		self.assertIsNotNone(scripts)