from common_test import CommonTest
from io import StringIO
import copy, hashlib, json, unittest

class UserTest(CommonTest):
    def test_01_get_user_not_logged(self):
        conn = self.newConnection()
        userId = self.users['basic']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId))
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_02_get_admin_user_logged(self):
        conn, headers, message = self.newLoggedConnection('admin')
        userId = message['user_id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_03_get_admin_user_logged(self):
        conn, headers, message = self.newLoggedConnection('admin')
        userId = self.users['basic']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_04_get_basic_user_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        userId = message['user_id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_05_get_basic_user_partially_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        userId = self.users['admin']['id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_06_get_list_of_users_not_logged(self):
        conn = self.newConnection()
        conn.request('GET', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_07_get_list_of_users_logged_as_admin(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['users']), message['total_rows'])

    def test_08_get_list_of_users_basic_user_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('GET', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_09_get_list_of_users_logged_as_admin_with_wrong_order_by(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?order_by=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_10_get_list_of_users_logged_as_admin_with_right_order_by_and_wrong_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?order_by=id&order_asc=bar', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_11_get_list_of_users_logged_as_admin_with_right_order_by_and_right_order_asc(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?order_by=id&order_asc=0', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['users']), message['total_rows'])

    def test_12_get_list_of_users_logged_as_admin_with_right_limit_and_right_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?limit=1&offset=1', headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertLessEqual(len(message['users']), message['total_rows'])

    def test_13_get_list_of_users_logged_as_admin_with_wrong_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?limit=0', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_14_get_list_of_users_logged_as_admin_with_wrong_limit(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?limit=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_15_get_list_of_users_logged_as_admin_with_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?offset=-3', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_16_get_list_of_users_logged_as_admin_with_wrong_offset(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('GET', self.path + 'user/?offset=foo', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_17_post_not_logged(self):
        conn = self.newConnection()
        conn.request('POST', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_18_post_basic_user_not_allowed(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('POST', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_19_post_admin_user_without_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_20_post_admin_user_with_wrong_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        io = StringIO()
        json.dump({
            'login': 'toto',
            'password': 'toto79',
            'fullname': 'la tête à toto'
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_21_post_admin_user_with_right_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        meta = {'Description': 'Toto est super content', 'Format': 'totomobile'}
        io = StringIO()
        json.dump({
            'login': 'toto',
            'password': 'toto79',
            'fullname': 'la tête à toto',
            'email': 'toto@toto.com',
            'homedirectory': '/mnt/raid',
            'isadmin': False,
            'canarchive': True,
            'canrestore': True,
            'meta': meta,
            'poolgroup': 1,
            'disabled': False
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        location = res.getheader('location')
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)
        self.assertIsNotNone(location)
        self.assertIsNotNone(message)
        conn = self.newConnection()
        conn.request('GET', location + '?id=' + str(message['user_id']), headers=cookie)
        res = conn.getresponse()
        user = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(user)
        self.assertEqual(user['user']['id'], message['user_id'])
        self.assertEqual(user['user']['login'], 'toto')
        pwdlenh = int(len('toto79') / 2)
        pwd = 'toto79'
        hashpwd = hashlib.sha1((pwd[:pwdlenh] + user['user']['salt'] + pwd[pwdlenh:]).encode('utf-8')).hexdigest()
        self.assertEqual(user['user']['password'], hashpwd)
        self.assertEqual(user['user']['fullname'], 'la tête à toto')
        self.assertEqual(user['user']['email'], 'toto@toto.com')
        self.assertEqual(user['user']['homedirectory'], '/mnt/raid')
        self.assertEqual(user['user']['isadmin'], False)
        self.assertEqual(user['user']['canarchive'], True)
        self.assertEqual(user['user']['canrestore'], True)
        for k in meta:
            self.assertIn(k, user['user']['meta'])
            self.assertEqual(meta[k], user['user']['meta'][k])
        self.assertEqual(user['user']['poolgroup'], 1)
        self.assertEqual(user['user']['disabled'], False)
        self.assertIn('user_id', message)
        last_user_created = message['user_id']
        conn = self.newConnection()
        conn.request('DELETE', "%suser/?id=%d" % (self.path, last_user_created), headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
        conn = self.newConnection()
        conn.request('DELETE', "%suser/?id=%d" % (self.path, last_user_created), headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)

    def test_22_post_admin_user_with_right_params_and_poolgroup_is_null(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        meta = {'Description': 'Kiki est super content', 'Voiture': 'kikimobile'}
        io = StringIO()
        json.dump({
            'login': 'kiki',
            'password': 'kiki91',
            'fullname': 'la tête à kiki',
            'email': 'kiki@kiki.com',
            'homedirectory': '/mnt/raid',
            'isadmin': False,
            'canarchive': True,
            'canrestore': True,
            'meta': meta,
            'poolgroup': None,
            'disabled': False
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        location = res.getheader('location')
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)
        self.assertIsNotNone(location)
        self.assertIsNotNone(message)
        conn = self.newConnection()
        conn.request('GET', location + '?id=' + str(message['user_id']), headers=cookie)
        res = conn.getresponse()
        user = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        self.assertIsNotNone(user)
        self.assertEqual(user['user']['id'], message['user_id'])
        self.assertEqual(user['user']['login'], 'kiki')
        pwdlenh = int(len('kiki91') / 2)
        pwd = 'kiki91'
        hashpwd = hashlib.sha1((pwd[:pwdlenh] + user['user']['salt'] + pwd[pwdlenh:]).encode('utf-8')).hexdigest()
        self.assertEqual(user['user']['password'], hashpwd)
        self.assertEqual(user['user']['fullname'], 'la tête à kiki')
        self.assertEqual(user['user']['email'], 'kiki@kiki.com')
        self.assertEqual(user['user']['homedirectory'], '/mnt/raid')
        self.assertEqual(user['user']['isadmin'], False)
        self.assertEqual(user['user']['canarchive'], True)
        self.assertEqual(user['user']['canrestore'], True)
        for k in meta:
            self.assertIn(k, user['user']['meta'])
            self.assertEqual(meta[k], user['user']['meta'][k])
        self.assertEqual(user['user']['poolgroup'], None)
        self.assertEqual(user['user']['disabled'], False)
        last_user_created = message['user_id']
        conn = self.newConnection()
        conn.request('DELETE', "%suser/?id=%d" % (self.path, last_user_created), headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_23_put_user_not_logged(self):
        conn = self.newConnection()
        conn.request('PUT', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_24_put_user_logged_as_admin_without_params(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_25_put_user_logged_as_basic_update_admin(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        io = StringIO()
        json.dump({
            'id': 1,
            'login': 'toto',
            'password': 'toto79',
            'fullname': 'la tête à toto',
            'email': 'toto@toto.com',
            'homedirectory': '/mnt/raid',
            'isadmin': False,
            'canarchive': True,
            'canrestore': True,
            'meta': {'Description': 'Toto est super content', 'Format': 'totomobile'},
            'poolgroup': 3,
            'disabled': False
        }, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('PUT', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_26_put_user_logged_as_basic_update_itself(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        userId = message['user_id']
        conn.request('GET', "%suser/?id=%d" % (self.path, userId), headers=cookie)
        res = conn.getresponse()
        returned = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        user = returned['user']
        user['fullname'] = 'bozo'
        user['email'] = 'bozo@bozo.com'
        user['meta'].update({'Description': 'Bozo est super content', 'Rôle': 'Bozo le clown'})
        io = StringIO()
        json.dump(user, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn = self.newConnection()
        conn.request('PUT', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_27_put_user_logged_as_admin_update_archiver_poolgroup_is_null(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/?id=%d" % (self.path, self.users['archiver']['id']), headers=cookie)
        res = conn.getresponse()
        returned = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        user = returned['user']
        copy_user = copy.deepcopy(user)
        user['fullname'] = 'archiver1'
        user['homedirectory'] = '/mnt/nas'
        user['poolgroup'] = None
        io = StringIO()
        json.dump(user, io);
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn = self.newConnection()
        conn.request('PUT', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
        io = StringIO()
        json.dump(copy_user, io);
        conn = self.newConnection()
        conn.request('PUT', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_28_put_user_logged_as_admin_update_archiver_new_password(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        conn.request('GET', "%suser/?id=%d" % (self.path, self.users['archiver']['id']), headers=cookie)
        res = conn.getresponse()
        returned = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 200)
        user = returned['user']
        user['password'] = 'archiver19'
        io = StringIO()
        json.dump(user, io)
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn = self.newConnection()
        conn.request('PUT', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
        user['password'] = self.users['archiver']['password']
        io = StringIO()
        json.dump(user, io)
        conn = self.newConnection()
        conn.request('PUT', self.path + 'user/', body=io.getvalue(), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)

    def test_29_delete_user_not_logged(self):
        conn = self.newConnection()
        conn.request('DELETE', self.path + 'user/')
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 401)

    def test_30_delete_user_logged_as_admin_without_params(self):
        conn, headers, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_31_delete_user_logged_suicide(self):
        conn, headers, message = self.newLoggedConnection('admin')
        userId = message['user_id']
        conn.request('DELETE', "%suser/?id=%d" % (self.path, userId), headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 400)

    def test_32_delete_user_logged_as_basic(self):
        conn, headers, message = self.newLoggedConnection('basic')
        conn.request('DELETE', self.path + 'user/', headers=headers)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_33_delete_user_who_had_created_an_archive(self):
        conn, cookie, message = self.newLoggedConnection('basic')
        conn.request('DELETE', self.path + 'user/?id=3', headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 403)

    def test_34_delete_user_not_found(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        conn.request('DELETE', self.path + 'user/?id=256', headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 404)


    def test_35_post_user_created(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'login': 'tati',
            'password': 'tati79',
            'fullname': 'la tête à tati',
            'email': 'tati@tati.com',
            'homedirectory': '/mnt/raid',
            'isadmin': False,
            'canarchive': True,
            'canrestore': True,
            'meta': {
                'Description': 'tati est super content',
                'Format': 'tatimobile'
            },
            'poolgroup': 1,
            'disabled': False
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'user/', body=data, headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)


    def test_36_post_user_created_and_deleted(self):
        conn, cookie, message = self.newLoggedConnection('admin')
        data = json.dumps({
            'login': 'titi',
            'password': 'titi79',
            'fullname': 'la tête à titi',
            'email': 'titi@titi.com',
            'homedirectory': '/mnt/raid',
            'isadmin': False,
            'canarchive': True,
            'canrestore': True,
            'meta': {
                'Description': 'Titi est super content',
                'Format': 'titimobile'
            },
            'poolgroup': 1,
            'disabled': False
        });
        headers = {"Content-type": "application/json"}
        headers.update(cookie)
        conn.request('POST', self.path + 'user/', body=data, headers=headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode('utf-8'))
        conn.close()
        self.assertEqual(res.status, 201)

        last_user_created = message['user_id']
        conn = self.newConnection()
        conn.request('DELETE', "%suser/?id=%d" % (self.path, last_user_created), headers=cookie)
        res = conn.getresponse()
        conn.close()
        self.assertEqual(res.status, 200)
