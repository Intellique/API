#! /usr/bin/python3
# Restoration task creation : authenticate and create a restoration task

import getpass, http.client, json, sys
from optparse import OptionParser, OptionGroup
from datetime import datetime
import ssl

parser = OptionParser()

group = OptionGroup(parser, "restore archive options");
group.add_option("-A", "--archive-id", dest="archiveId", type="int", help="Specify archive id")
group.add_option("-d", "--next-start", dest="nextStart", default=None, help="Optionnal next start date")
group.add_option("-D", "--destination", dest="destination", default=None, help="Optionnal archive restoration destination")
group.add_option("-f", "--file", action="append", dest="files", default=[], help="Specify file to restore, nothing for the whole archive")
group.add_option("-j", "--job-name", dest="jobName", default=None, help="Optionnal job name")
parser.add_option_group(group)

group = OptionGroup(parser, "options for authenticate");
group.add_option("-H", "--host", dest="host", default="localhost", help="Specify host name")
group.add_option("-P", "--pwprompt", action="store_true", dest="promptPassword", default=False, help="If given, restore-archive will issue a prompt for the password")
group.add_option("-U", "--username", dest="userName", default=None, help="Connect to api as the user username")
group.add_option("-W", "--password", dest="password", help="Specify user password")
group.add_option("-k", "--api-key", dest="api_key", default=None, help="Specify API key")
parser.add_option_group(group)

group = OptionGroup(parser, "verbose mode");
group.add_option("-v", "--verbose", action="store_true", dest="verbose", default=False, help="Explain what is going on")

(options, args) = parser.parse_args()

ok = True
if options.archiveId is None:
    print("You should specify an archive id")
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

if options.api_key is None:
    print("You should specify an API key")
    ok = False

options.files.extend(args)

if not ok:
    sys.exit(1)

params = {
    'archive': options.archiveId,
    'name': options.jobName,
    'files': options.files,
    'nextstart': options.nextStart,
    'destination': options.destination
}

if options.userName is None:
    print("User name: ", end="", flush=True)
    options.userName = sys.stdin.readline().splitlines()[0]

if options.promptPassword or options.password is None:
    options.password = getpass.getpass()

# authentication
conn = http.client.HTTPSConnection(options.host, context=ssl._create_unverified_context())

credentials = json.dumps({'login': options.userName, 'password': options.password, 'apikey': options.api_key})
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

# restore archive
cookie = {'Cookie': res.getheader('Set-Cookie').split(';')[0]}
headers.update(cookie)
conn = http.client.HTTPSConnection(options.host, context=ssl._create_unverified_context())
conn.request('POST', '/storiqone-backend/api/v1/archive/restore/', json.dumps(params), headers)
res = conn.getresponse()
contentType = res.getheader('Content-type').split(';', 1)[0]
if contentType is None or contentType != "application/json" or res.status != 201:
    message = res.read().decode("utf-8")
    conn.close()
    if options.verbose:
        print ("Restoration task creation has failed, response code : %d, params : %s, message: %s" % (res.status, params, message))
    else:
        print ("Restoration task creation has failed")
    sys.exit(3)

message = json.loads(res.read().decode("utf-8"))
conn.close()

print("Restoration task creation has succeeded, job id: %d" % (message['job_id']))
