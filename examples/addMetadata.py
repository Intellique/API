#! /usr/bin/python3
# authenticate and add metadatas

import getpass, http.client, json, sys
from optparse import OptionParser, OptionGroup
from datetime import datetime
import time, ssl ,os

parser = OptionParser()

group = OptionGroup(parser, "create archive options");
group.add_option("-A", "--archivefile-id", dest="archivefileId", type="int", help="Specify archivefile id (to adding metadatas to archivefile)")
group.add_option("-f", "--metadata-file", dest="metadataFile", default=None, type="string", help="Specify metadata file name")
group.add_option("-t", "--type", dest="type", default=None, type="string", help="Specify type of file")

parser.add_option_group(group)

group = OptionGroup(parser, "options for authenticate");
group.add_option("-H", "--host", dest="host", default="localhost", help="Specify host name")
group.add_option("-P", "--pwprompt", action="store_true", dest="promptPassword", default=False, help="If given, create-archive will issue a prompt for the password")
group.add_option("-U", "--username", dest="userName", default=None, help="Connect to api as the user username")
group.add_option("-W", "--password", dest="password", help="Specify user password")
group.add_option("-k", "--api-key", dest="api_key", default=None, help="Specify API key")
parser.add_option_group(group)

group = OptionGroup(parser, "verbose mode");
group.add_option("-v", "--verbose", action="store_true", dest="verbose", default=False, help="Explain what is going on")

(options, args) = parser.parse_args()

ok = True
if options.archivefileId is None:
	print("You should specify an archivefile id")
	ok = False

if options.metadataFile is None:
	print("You should specify the file that contains the metadata")
	ok = False

if options.type is None:
	print("You should specify a type")
	ok = False

if options.api_key is None:
	print("You should specify an API key")
	ok = False

if not ok:
	sys.exit(1)

params = {
	'id': options.archivefileId,
	'metadata': {}
}

file = open(options.metadataFile, "r")
metadata = json.loads(file.read())
file.close()

params['metadata'] = metadata

#demander le username entrée
if options.userName is None:
	print("User name: ", end="", flush=True)
	options.userName = sys.stdin.readline().splitlines()[0]

if options.promptPassword or options.password is None:
	options.password = getpass.getpass()

# authentication
if hasattr(ssl, '_create_unverified_context'):
	conn = http.client.HTTPSConnection(options.host, context=ssl._create_unverified_context())
else:
	conn = http.client.HTTPSConnection(options.host)

credentials = json.dumps({'login': options.userName, 'password': options.password, 'apikey': options.api_key})
headers = {"Content-type": "application/json"}
conn.request('POST', '/storiqone-backend-paul/api/v1/auth/', credentials, headers)
res = conn.getresponse()
contentType = res.getheader('Content-type').split(';', 1)[0]
if contentType is None or contentType != "application/json" or res.status != 201:
	conn.close()
	print ("Access denied", contentType, "status ", res.status)
	sys.exit(2)

print ("Access granted")
conn.close()

if options.type == "archivefile":
	# create metadata of an archivefile
	cookie = {'Cookie': res.getheader('Set-Cookie').split(';')[0]}
	headers.update(cookie)
	if hasattr(ssl, '_create_unverified_context'):
		conn = http.client.HTTPSConnection(options.host, context=ssl._create_unverified_context())
	else:
		conn = http.client.HTTPSConnection(options.host)
	conn.request('POST', '/storiqone-backend-paul/api/v1/archivefile/metadata/', json.dumps(params), headers)
	res = conn.getresponse()
	contentType = res.getheader('Content-type').split(';', 1)[0]
	if contentType is None or contentType != "application/json" or res.status != 200:
		message = res.read().decode("utf-8")
		conn.close()
		if options.verbose:
			print ("Add metadata to the archivefile has failed, response code : %d, params : %s, message: %s" % (res.status, params, message))
		else:
			print ("Add metadata to the archivefile has failed")
	else:
		print("Add metadata to the archivefile has succeeded, response code : %d" % res.status)
	conn.close()

if options.type == "archive":
	# create metadata of an archive
	cookie = {'Cookie': res.getheader('Set-Cookie').split(';')[0]}
	headers.update(cookie)
	if hasattr(ssl, '_create_unverified_context'):
		conn = http.client.HTTPSConnection(options.host, context=ssl._create_unverified_context())
	else:
		conn = http.client.HTTPSConnection(options.host)
	conn.request('POST', '/storiqone-backend-paul/api/v1/archive/metadata/', json.dumps(params), headers)
	res = conn.getresponse()
	contentType = res.getheader('Content-type').split(';', 1)[0]
	if contentType is None or contentType != "application/json" or res.status != 200:
		message = res.read().decode("utf-8")
		conn.close()
		if options.verbose:
			print ("Add metadata to the archive has failed, response code : %d, params : %s, message: %s" % (res.status, params, message))
		else:
			print ("Add metadata to the archive has failed")
	else:
		print("Add metadata to the archive has succeeded, response code : %d" % res.status)
	conn.close()