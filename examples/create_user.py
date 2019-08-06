#! /usr/bin/python3

import argparse
from getpass import getpass
import http.client
import json
import pprint
import ssl
import sys
from urllib.parse import quote as urlquote
from urllib.parse import urlparse

parser = argparse.ArgumentParser()
sub_parser = parser.add_subparsers(title='actions')

# common options
parser.add_argument('-c', '--config', default='api.conf', help='Specify which contains api key (format json)')
parser.add_argument('-K', '--insecure', action='store_true', default=False, help='Do not check hostname when using https')
parser.add_argument('-k', '--api-key', default=None, help='Specify the api key')
parser.add_argument('--url', default=None, help='specify url')
parser.add_argument('-l', '--admin', default=None, help='Specify the login of admin user')
parser.add_argument('--admin_password', default=None, help='Specify the admin\'s password')
parser.add_argument('-P', '--pwprompt', action='store_true', default=False, help='Prompt for password')

# sub parser for create_config command
parser_cf = sub_parser.add_parser('create_config', aliases=['cf'], help='Create a config file')
parser_cf.set_defaults(action='create_config')

# sub parser for create_user command
parser_cu = sub_parser.add_parser('create_user', aliases=['cu'], help='Create a user')
parser_cu.add_argument('--homedirectory', default='/var/www/nextcloud/data/<login>/files/', help='specify a homedirectory ("<login>" will be replaced by login)')
parser_cu.add_argument('--pool_template', help='specify a pool template')
parser_cu.add_argument('--vtl_id', help='specify vtl id')
parser_cu.add_argument('login', help='specify login of new user')
parser_cu.add_argument('password', nargs='?', default=None, help='specify password of new user')
parser_cu.set_defaults(action='create_user')

# sub parser for enable_user command
parser_eu = sub_parser.add_parser('enable_user', aliases=['eu'], help='enable a user')
parser_eu.add_argument('key', help='specify key')
parser_eu.set_defaults(action='enable_user')

# sub parser for disable_user command
parser_du = sub_parser.add_parser('disable_user', aliases=['du'], help='disable a user')
parser_du.add_argument('--key', help='specify key')
parser_du.add_argument('login', help='specify login of user')
parser_du.set_defaults(action='disable_user')

# sub parser for list_pool command
parser_lp = sub_parser.add_parser('list_pool', aliases=['lp'], help='List pools')
parser_lp.set_defaults(action='list_pool')

# sub parser for list_user command
parser_lu = sub_parser.add_parser('list_user', aliases=['lu'], help='List users')
parser_lu.set_defaults(action='list_user')

args = parser.parse_args()


# check default options
base_url = None
if args.url is not None:
    base_url = args.url
api_key = None
if args.api_key is not None:
    api_key = args.api_key
admin_user = None
if args.admin is not None:
    admin_user = args.admin
admin_password = None
if args.admin_password is not None:
    admin_password = args.admin_password

# open config file if needed
if None in [base_url, api_key, admin_user, admin_password]:
    try:
        fd = open(args.config)
        config = json.load(fd)
        fd.close()

        if 'api key' in config:
            api_key = config['api key']
        if 'base url' in config:
            base_url = config['base url']
        if 'login' in config:
            admin_user = config['login']
        if 'password' in config:
            admin_password = config['password']
    except OSError as err:
        print('Error occured while reading file "%s" because %s' % (args.config, err), file=sys.stderr)
        sys.exit(1)

if admin_password is None and args.pwprompt:
    try:
        admin_password = getpass('Admin\'s password: ')
    except:
        pass

if api_key is None:
    print('Error, you should specify an api key', file=sys.stderr)
if base_url is None:
    print('Error, you should specify an url', file=sys.stderr)
if admin_user is None:
    print('Error, you should specify a login for admin user', file=sys.stderr)
if admin_password is None:
    print('Error, you should specify a password for admin userl', file=sys.stderr)
if None in [base_url, api_key, admin_user, admin_password]:
    sys.exit(1)


def authentication(info):
    print('Login to "%s"... ' % base_url, end='', flush=True)
    connection = newHttpConnection(info)
    headers = {"Content-type": "application/json"}
    params = json.dumps({'login': admin_user, 'password': admin_password, 'apikey': api_key})
    connection.request('POST', base_url + '/auth/', params, headers)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    if response.status == 201:
        print('connected')
        return (connection, {'Cookie': response.getheader('Set-Cookie').split(';')[0]})
    else:
        print('connection failure because %s' % message)
        connection.close()
        sys.exit(2)

def newHttpConnection(info):
    try:
        if info.scheme == 'http':
            return http.client.HTTPConnection(info.hostname, info.port)
        elif info.scheme == 'https' and args.insecure:
            ssl_context = ssl.SSLContext() # older python (< v3.5.3) need ssl.PROTOCOL_TLSv1 as parameter
            ssl_context.verify_mode = False
            ssl_context.check_hostname = False
            return http.client.HTTPSConnection(info.hostname, info.port, context=ssl_context)
        elif info.scheme == 'https':
            return http.client.HTTPSConnection(info.hostname, info.port)
    except Exception as err:
        print('Error occured while creating http(s) connection because %s' % (err), file=sys.stderr)
        sys.exit(1)

pp = pprint.PrettyPrinter(indent=4)

if args.action == 'create_config':
    try:
        fd = open(args.config, 'w')
        json.dump({'api key': api_key, 'base url': base_url, 'login': admin_user, 'password': admin_password}, fd, sort_keys=True)
        fd.close()
    except OSError as err:
        print('Error occured while writing info file "%s" because %s' % (args.config, err), file=sys.stderr)
        sys.exit(1)
elif args.action == 'create_user':
    user_password = args.password
    if user_password is None and args.pwprompt:
        try:
            user_password = getpass('User\'s password: ')
        except:
            pass

    if user_password is None:
        print('Error, you should specify a password for user or use option --pwprompt', file=sys.stderr)
        sys.exit(1)

    url_info = urlparse(base_url)
    (connection, cookie) = authentication(url_info)

    # search user
    print('Search user "%s"... ' % args.login, end='', flush=True)
    connection.request('GET', '%s/user/search/?login=%s' % (base_url, args.login), headers=cookie)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    if response.status == 404:
        print('not found')
    else:
        print('found, user id: %d' % message['users'][0])

    # search archive format
    print('Search archive format "Storiq One (TAR)"... ', end='', flush=True)
    connection.request('GET', '%s/archiveformat/?name=%s' % (base_url, urlquote('Storiq One (TAR)')), headers=cookie)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    archive_format_id = None
    if response.status == 200:
        archive_format_id = message['archiveformat id']
        print('found, id: %d' % archive_format_id)
    else:
        print('not found')
        sys.exit(1)

    vtl_id = args.vtl_id
    if vtl_id is None:
        # search vtl
        print('Search vtl "%s"... ' % args.login, end='', flush=True)
        connection.request('GET', '%s/vtl/' % (base_url), headers=cookie)

        response = connection.getresponse()
        message = json.loads(response.read().decode("utf-8"))

        if message['total_rows'] == 1:
            vtl_id = int(message['vtls'][0]['vtlid'])
            print('found, select vtl (id: %d)' % vtl_id)
        elif message['total_rows'] > 1:
            print('found, there is more than one vtl, you should specify vtl_id')
            connection.close()
            sys.exit(2)
        else:
            print('no vtl found')
            connection.close()
            sys.exit(2)

    # get vtl information
    print('Get vtl information (id: %d)... ' % vtl_id, end='', flush=True)
    connection.request('GET', '%s/vtl/?id=%d' % (base_url, vtl_id), headers=cookie)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    mediaformat_id = None
    if response.status == 200:
        mediaformat_id = message['vtl']['mediaformat']
        print('ok, will use mediaformat(%d)' % mediaformat_id)
    else:
        print('failed, no vtl found with id = %d' % vtl_id)
        connection.close()
        sys.exit(2)

    # find blank media
    print('Find blank media (mediaformat: %d)... ' % mediaformat_id, end='', flush=True)
    connection.request('GET', '%s/media/search/?status=new&mediaformat=%d&order_by=id' % (base_url, mediaformat_id), headers=cookie)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    media_id = None
    if response.status == 200:
        if message['total_rows'] > 0:
            media_id = message['medias'][0]
            print('found %d media(s), will use media (id: %d)' % (message['total_rows'], media_id))
        else:
            print('no medias found')
            connection.close()
            sys.exit(2)
    else:
        print('error while finding blank media because %s' % message)
        connection.close()
        sys.exit(2)

    # creating pool
    pool = {
        'name': 'pool_' + args.login,
        'archiveformat': archive_format_id,
        'mediaformat': mediaformat_id
    }

    if args.pool_template is not None:
        pool['pooltemplate'] = args.pool_template

    pool_header = { 'Content-type': 'application/json' }
    pool_header.update(cookie)

    print('Create new pool (name: %s)... ' % pool['name'], end='', flush=True)
    connection.request('POST', '%s/pool/' % (base_url), json.dumps(pool), headers=pool_header)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    pool_id = None
    if response.status == 201:
        pool_id = message['pool_id']
        print('created, new id: %d' % pool_id)
    else:
        print('failed because (%d, %s)' % (response.status, message['message']))
        connection.close()
        sys.exit(2)

    # creating poolgroup
    poolgroup = {
        'name': 'poolgroup_' + args.login,
        'pools': [pool_id]
    }

    print('Create new pool group (name: %s)... ' % poolgroup['name'], end='', flush=True)
    connection.request('POST', '%s/poolgroup/' % (base_url), json.dumps(poolgroup), headers=pool_header)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    poolgroup_id = None
    if response.status == 201:
        poolgroup_id = message['poolgroup_id']
        print('created, new id: %d' % poolgroup_id)
    else:
        print('failed because (%d, %s)' % (response.status, message['message']))
        connection.close()
        sys.exit(2)

    # creating user
    homedirectory = args.homedirectory
    if homedirectory.find('<login>') > -1:
        homedirectory = homedirectory.replace('<login>', args.login)

    user = {
        'login': args.login,
        'password': user_password,
        'fullname': args.login,
        'email': args.login,
        'homedirectory': homedirectory,
        'isadmin': False,
        'canarchive': True,
        'canrestore': True,
        'poolgroup': poolgroup_id,
        'disabled': False
    }

    print('Create new user (name: %s)... ' % args.login, end='', flush=True)
    connection.request('POST', '%s/user/' % (base_url), json.dumps(user), headers=pool_header)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    user_id = None
    if response.status == 201:
        user_id = message['user_id']
        print('created, new id: %d' % user_id)
    else:
        print('failed because (%d, %s)' % (response.status, message['message']))
        connection.close()
        sys.exit(2)

    # format media
    task_info = {
        'media': media_id,
        'pool': pool_id
    }

    print('Create formatting task (media: %d, pool: %d)... ' % (media_id, pool_id), end='', flush=True)
    connection.request('POST', '%s/media/format/' % (base_url), json.dumps(task_info), headers=pool_header)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    task_id = None
    if response.status == 201:
        task_id = message['job_id']
        print('created, new task: %d' % task_id)
        connection.close()
        sys.exit(0)
    else:
        print('failed because (%d, %s)' % (response.status, message['message']))
        connection.close()
        sys.exit(2)



elif args.action == 'list_pool':
    url_info = urlparse(base_url)
    (connection, cookie) = authentication(url_info)

    print('Getting pool list... ', end='', flush=True)
    connection.request('GET', base_url + '/pool/', headers=cookie)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    print(message['total_rows'])

    pools = message['pools']
    for pool_id in pools:
        connection.request('GET', '%s/pool/?id=%d' % (base_url, pool_id), headers=cookie)

        response = connection.getresponse()
        sub_message = json.loads(response.read().decode("utf-8"))

        pool = sub_message['pool']
        pp.pprint(pool)


    connection.close()

elif args.action == 'list_user':
    url_info = urlparse(base_url)
    (connection, cookie) = authentication(url_info)

    print('Getting user list... ', end='', flush=True)
    connection.request('GET', base_url + '/user/', headers=cookie)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    print(message['total_rows'])

    users = message['users']
    for user_id in users:
        connection.request('GET', '%s/user/?id=%d' % (base_url, user_id), headers=cookie)

        response = connection.getresponse()
        sub_message = json.loads(response.read().decode("utf-8"))

        user = sub_message['user']
        pp.pprint(user)


    connection.close()

elif args.action == 'enable_user':
    key = args.key
    print('enable user : %s' % key)

    url_info = urlparse(base_url)
    (connection, cookie) = authentication(url_info)

    print('Activating user... ', end='', flush=True)
    connection.request('GET', base_url + '/user/update/?action=activate&key=' + key, headers=cookie)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    if response.status == 200:
        print('activated')
        connection.close()
        sys.exit(0)

    else:
        print('not activated')
        connection.close()
        sys.exit(1)


elif args.action == 'disable_user':
    print('disable user : %s' % args.login)

    url_info = urlparse(base_url)
    (connection, cookie) = authentication(url_info)

    print('Deactivating user... ', end='', flush=True)
    connection.request('GET', base_url + '/user/update/?action=deactivate&login=' + args.login, headers=cookie)

    response = connection.getresponse()
    message = json.loads(response.read().decode("utf-8"))

    if response.status == 200:
        print('deactivated')
    else:
        print('not deactivated')

    if args.key is not None:
        url_info = urlparse(base_url)
        (connection, cookie) = authentication(url_info)

        print('Adding key... ', end='', flush=True)
        connection.request('GET', base_url + '/user/update/?action=key&login=' + args.login + '&key=' + args.key, headers=cookie)

        response = connection.getresponse()
        message = json.loads(response.read().decode("utf-8"))

        if response.status == 200:
            print('added')
        else:
            print('not added')

    connection.close()

else:
    print('Error, you should specify one action from ("create_config", "create_user")', file=sys.stderr)
    sys.exit(1)
