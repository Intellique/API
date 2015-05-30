import json, unittest
import http.client, urllib.parse

class CommonTest(unittest.TestCase):
    scheme = 'http'
    host = 'taiko'
    path = '/storiqone-backend/api/v1/'
    login = 'storiq'
    password = '<password>'
    parsed = False

    def newConnection(self):
        if (self.scheme == 'http'):
            return http.client.HTTPConnection(self.host)
        else:
            return http.client.HTTPSConnection(self.host)

    def newLoggedConnection(self):
        conn = self.newConnection()
        params = urllib.parse.urlencode({'login': self.login, 'password': self.password});
        headers = {"Content-type": "application/x-www-form-urlencoded"}
        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        if (res.status == 200):
            return conn, res
        else:
            conn.close()
            return None, res

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
        if ('login' in config):
            self.login = config['login']
        if ('password' in config):
            self.password = config['password']

