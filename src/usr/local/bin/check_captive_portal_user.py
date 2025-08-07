#!/usr/local/bin/python3.8

import sys
from glob import glob
from subprocess import check_output

line = sys.stdin.readline().strip()
db_files = glob("/var/db/captiveportal*.db")
sqlite_cmd = '/usr/local/bin/sqlite3 {} "SELECT username FROM captiveportal WHERE ip=\'{}\'"'

def return_user(r_db, r_ip):
	return check_output([sqlite_cmd.format(r_db, r_ip)], shell=True).strip()

user = filter(None, [ return_user(db,ip) for db, ip in zip(db_files, [line] * len(db_files)) ])

if user:
	sys.stdout.write("OK user={}\n".format(user[0]))
else:
	sys.stdout.write("ERR\n")
