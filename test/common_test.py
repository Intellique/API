import json, unittest
import http.client, urllib.parse

class CommonTest(unittest.TestCase):
    scheme = 'http'
    host = 'taiko'
    path = '/storiqone-backend/api/v1/'
    users = {
        'admin': {
            'login': 'storiq',
            'password': '<password>'
        }
    }
    parsed = False

    def newConnection(self):
        if (self.scheme == 'http'):
            return http.client.HTTPConnection(self.host)
        else:
            return http.client.HTTPSConnection(self.host)

    def newLoggedConnection(self, user):
        if (user not in self.users):
            self.fail("user < %s > not found is config" % (user))
        conn = self.newConnection()
        params = urllib.parse.urlencode({'login': self.users[user]['login'], 'password': self.users[user]['password']})
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()
        self.assertEqual(res.status, 200)
        conn = self.newConnection()
        return conn, {'Cookie': res.getheader('Set-Cookie').split(';')[0]}, message

    def setUp(self):
        if (self.parsed):
            return
        f = open('config.json', 'r', encoding='utf-8')
        config = json.load(f)
        f.close()
        self.parsed = True
        if ('scheme' in config):
            self.scheme = config['scheme']
        if ('host' in config):
            self.host = config['host']
        if ('path' in config):
            self.path = config['path']
        if ('users' in config):
            self.users = config['users']

