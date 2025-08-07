#!/usr/local/bin/python -u
# -*- coding: utf-8 -*-
#  
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# <desenvolvimento@bluepex.com>, 2015
# ====================================================================
#

import os
import re
import sys
import time
import string
import MySQLdb
import datetime
import subprocess
import threading
from xml.dom import minidom

xmldoc = minidom.parse('/cf/conf/config.xml')
log_referer = str(xmldoc.getElementsByTagName('log_referer')[0].firstChild.nodeValue)

def convert_categorie(list_categories):
	categories = list(open('/usr/local/etc/wf_categories','r'))
	return [strcat.split(',')[1].rstrip() for strcat in categories if strcat.split(',')[0] in list_categories.split(',')]

def error_message(message):
	print >> open('/var/log/rotate.log','a'), "{} | WFSendbkp | {}".format(datetime.datetime.now().strftime('%h %d %H:%M:%S'),message)

def connect_db():
	try:
		mysqlUser = str(xmldoc.getElementsByTagName('reports_user')[0].firstChild.nodeValue)
		mysqlIP = str(xmldoc.getElementsByTagName('reports_ip')[0].firstChild.nodeValue)
		mysqlPass = str(xmldoc.getElementsByTagName('reports_password')[0].firstChild.nodeValue)
		mysqlPort = str(xmldoc.getElementsByTagName('reports_port')[0].firstChild.nodeValue)
		mysqlDb = str(xmldoc.getElementsByTagName('reports_db')[0].firstChild.nodeValue)
		return MySQLdb.connect(mysqlIP,mysqlUser,mysqlPass,mysqlDb,connect_timeout=3)
	except Exception, error:
		error_message("CONNECT DATABASE: {}".format(error))

def insert_data(insert_logs):
	try:
		cont_insert = 0
		conn = connect_db()
		insert = conn.cursor()
		insert_logs = sorted(insert_logs, key=lambda log: (log[0][0:8], log[1], log[4]))
		for idx,log in enumerate(insert_logs):
			log_time = datetime.datetime.fromtimestamp(int(log[0].split('.')[0])).strftime('%Y-%m-%d %H:%M:%S')
			blocked = re.sub(r'^[0-9]{4}','1',log[9])
			categories = re.sub(r'-|0','99',log[8])
			if idx == 0 or log[4] != insert_logs[idx - 1][4]:
				insert.execute("insert into accesses(time_date,ip,username,groupname,url_str,size_bytes,elapsed_ms,blocked,url_path,categories,url_no_qry) values ('{}','{}','{}','{}','{}','{}','{}','{}','','','')".format(log_time,log[1],log[2],log[3],re.split(r'\'|\"',log[4])[0],log[6],log[7],blocked))
				cont_insert = cont_insert + 1
				lastid = int(insert.lastrowid)
				for id_categorie in re.split(',', log[8]):
					insert_categories = insert.execute("insert into access_categories (accesses_id,categories_id) values ('{}','{}')".format(lastid,int(re.sub('-', '99', id_categorie))))
					cont_insert = cont_insert + 1
			else:
				if log_referer == 'on' and log[5] != 'None':
					insert_referer = insert.execute("insert into referers (id_referer,url_referer) values ('{}','{}')".format(lastid,re.split(r'\'|\"',log[5])[0]))
					cont_insert = cont_insert + 1
			if(cont_insert > 10000):
				insert.execute('COMMIT')
				cont_insert = 0
		insert.execute('COMMIT')
		os.unlink('/var/squid/logs/backup_data')
		os.unlink('/var/tmp/wfsendbkp.lock')
	except Exception, error:
		error_message("INSERT DATA: {}".format(error))
		os.unlink('/var/tmp/wfsendbkp.lock')
		if(not os.path.exists('/var/tmp/wfsendmail.lock') or (int(time.time()) - (int(open('/var/tmp/wfsendmail.lock','r').read().rstrip())) > 14400)):
			print >> open('/var/tmp/wfsendmail.lock','w'), int(time.time())
			subprocess.call(['/usr/local/bin/python /usr/local/bin/wfsendmail.py'], shell=1)

def main():

	try:
		print >> open('/var/tmp/wfsendbkp.lock','w'), os.getpid()
		file_logs = list(open('/var/squid/logs/backup_data'))
		list_logs = []
		for line in file_logs:
			list_logs.append(line.split())
		insert_data(list_logs)
	except Exception, error:
		error_message("MAIN: {}".format(error))
		os.unlink('/var/tmp/wfsendbkp.lock')

if __name__ == "__main__":
	main()
