#! /usr/bin/python3
# Copy task creation : authenticate and create a copy task

import getpass, http.client, json, sys
from optparse import OptionParser, OptionGroup
from datetime import datetime

parser = OptionParser()

group = OptionGroup(parser, "copy archive options");
group.add_option("-A", "--archive-id", dest="archiveId", type="int", help="Specify archive id")
group.add_option("-c", "--quick-check", action="store_true", dest="quickCheck", default=False, help="Optionnal archive quick check mode")
group.add_option("-C", "--thorough-check", action="store_true", dest="thoroughCheck", default=False, help="Optionnal archive thorough check mode")
group.add_option("-d", "--next-start", dest="nextStart", default=None, help="Optionnal next start date")
group.add_option("-j", "--job-name", dest="jobName", default=None, help="Optionnal job name")
group.add_option("-p", "--pool-id", dest="poolId", type="int", help="Specify pool id")
parser.add_option_group(group)

group = OptionGroup(parser, "options for authenticate");
group.add_option("-H", "--host", dest="host", default="localhost", help="Specify host name")
group.add_option("-P", "--pwprompt", action="store_true", dest="promptPassword", default=False, help="If given, restore-archive will issue a prompt for the password")
group.add_option("-U", "--username", dest="userName", default=None, help="Connect to api as the user username")
group.add_option("-W", "--password", dest="password", help="Specify user password")
parser.add_option_group(group)

group = OptionGroup(parser, "verbose mode");
group.add_option("-v", "--verbose", action="store_true", dest="verbose", default=False, help="Explain what is going on")

(options, args) = parser.parse_args()

ok = True
if options.archiveId is None:
    print("You should specify an archive id")
    ok = False

if options.poolId is None:
    print("You should specify a pool id")
    ok = False

if options.nextStart is not None:
    formats = ['%Y-%m-%dT%H:%M:%S%z', '%Y-%m-%d %H:%M:%S%z', '%Y-%m-%dT%H:%M:%S%Z', '%Y-%m-%d %H:%M:%S%Z', '%Y-%m-%dT%H:%M:%S', '%Y-%m-%d %H:%M:%S', '%x %X']
    for format in formats:
        try:
            date = None
            date = datetime.strptime(options.nextStart, format)
        except ValueError:
            pass
        if date is not None:
            options.nextStart = date.isoformat()
            break
    if date is None:
        print("Failed to parse next start date parameter")
        ok = False

if not ok:
    sys.exit(1)

params = {
    'archive': options.archiveId,
    'pool': options.poolId,
    'name': options.jobName,
    'nextstart': options.nextStart,
    'options': {}
}

if options.thoroughCheck:
    params['options']['thorough_mode'] = True

if options.quickCheck:
    params['options']['quick_mode'] = True

if options.userName is None:
    print("User name: ", end="", flush=True)
    options.userName = sys.stdin.readline().splitlines()[0]

if options.promptPassword or options.password is None:
    options.password = getpass.getpass()

# authentication
conn = http.client.HTTPConnection(options.host)

credentials = json.dumps({'login': options.userName, 'password': options.password})
headers = {"Content-type": "application/json"}
conn.request('POST', '/storiqone-backend/api/v1/auth/', credentials, headers)
res = conn.getresponse()
contentType = res.getheader('Content-type').split(';', 1)[0]
if contentType is None or contentType != "application/json" or res.status != 201:
    conn.close()
    print ("Access denied")
    sys.exit(2)

print ("Access granted")
conn.close()

# copy archive
cookie = {'Cookie': res.getheader('Set-Cookie').split(';')[0]}
headers.update(cookie)
conn = http.client.HTTPConnection(options.host)
conn.request('POST', '/storiqone-backend/api/v1/archive/copy/', json.dumps(params), headers)
res = conn.getresponse()
contentType = res.getheader('Content-type').split(';', 1)[0]
if contentType is None or contentType != "application/json" or res.status != 201:
    message = res.read().decode("utf-8")
    conn.close()
    if options.verbose:
        print ("Copy task creation has failed, response code : %d, params : %s, message: %s" % (res.status, params, message))
    else:
        print ("Copy task creation has failed")
    sys.exit(3)

message = json.loads(res.read().decode("utf-8"))
conn.close()

print("Copy task creation has succeeded, job id: %d" % (message['job_id']))