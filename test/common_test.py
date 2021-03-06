import json, unittest
import http.client
import ssl

class CommonTest(unittest.TestCase):
    scheme = 'http'
    host = 'veenai'
    path = '/API/api/v1/'
    users = {
        'admin': {
            'login': 'storiq',
            'password': 'spider77'
        }
    }
    parsed = False
    apikey = "d017552c-e005-4bc7-86bc-5e3e8b3ade2b"
    allowAutoSigned = True

    def newConnection(self):
        if (self.scheme == 'http'):
            return http.client.HTTPConnection(self.host)
        elif (self.allowAutoSigned):
            ssl_context = ssl.SSLContext()
            ssl_context.verify_mode = False
            ssl_context.check_hostname = False
            return http.client.HTTPSConnection(self.host, context = ssl_context)
        else:
            return http.client.HTTPSConnection(self.host)

    def newLoggedConnection(self, user):
        if (user not in self.users):
            self.fail("user < %s > not found is config" % (user))

        conn = self.newConnection()
        params = json.dumps({'login': self.users[user]['login'], 'password': self.users[user]['password'], 'apikey': self.apikey})
        headers = {"Content-type": "application/json"}

        conn.request('POST', self.path + 'auth/', params, headers)
        res = conn.getresponse()
        message = json.loads(res.read().decode("utf-8"))
        conn.close()

        self.assertEqual(res.status, 201)
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
        if ('apikey' in config):
            self.apikey = config['apikey']
