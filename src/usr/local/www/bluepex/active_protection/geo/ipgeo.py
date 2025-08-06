#!/usr/local/bin/python
#  -*- coding: UTF-8 -*-
#
#  Copyright (C) 2019 BluePex Security Company (R)
#  Marcos Claudiano <marcos.claudiano@bluepex.com>
#  All rights reserved.
#
import os
import re
import csv
import sys
import time
import smtplib
import MySQLdb
import datetime
import email.utils
import ConfigParser
import logging
import subprocess
import xmltodict
import json
from datetime import datetime

platform = open("/etc/platform", "r").read().rstrip()

with open('/cf/conf/config.xml') as fd:
	xmldoc = xmltodict.parse(fd.read())

pattern = re.compile(r"(.*\/)(.*[%|\'|\"|\?])")
wf_instances = xmldoc['bluepex']['system']['webfilter']['instance']['info']['wf_instances']
wf_instances_interface = xmldoc['bluepex']['system']['webfilter']['instance']['info']['wf_instances_interface']

def connect_db():

	report_settings = xmldoc['bluepex']['system']['webfilter']['nf_reports_settings']

	if report_settings:
		mysqlUser = report_settings['element0']['reports_user']
		mysqlIP = report_settings['element0']['reports_ip']
		mysqlPass = report_settings['element0']['reports_password']
		mysqlDb = report_settings['element0']['reports_db']
		try:
			return MySQLdb.connect(mysqlIP, mysqlUser, mysqlPass, mysqlDb, connect_timeout=3)
		except Exception as error:
			error_message.info("CONNECT DATABASE: {}".format(error))
			return False
	else:
		error_message.info("CONNECT DATABASE: Report settings not configured")
		return False

def exec_geo():

	gateways = subprocess.check_output("/usr/local/bin/php -f /etc/inc/rc.list_gateways", shell=True)

	gateways_json = json.loads(gateways)

	conn2 = connect_db()
	delete = conn2.cursor()
	sql = "delete from block_country"
	delete.execute(sql)
	conn2.commit()

	for interface in gateways_json:

		filter_logfile = "/var/log/filter.log"

		f = open(filter_logfile, "r")

		lines = f.readlines()

		for line in lines:

			pattern = re.findall(r"(?:" + interface + ").*,(?:udp|tcp),\d*,(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}),.*", line)

			ip = ""

			for ips in pattern:
				ip = ips

				if len(ip) > 0:
					hostname = "curl -s https://api.ip.sb/geoip/"
					sed = " | sed -n 's|.*\"country\":\"\([^\"]*\)\".*|\\1|p'"
					sed2 = " | sed -n 's|.*\"country_code\":\"\([^\"]*\)\".*|\\1|p'"

					url = "{0}{1}{2}".format(hostname, ip, sed)
					pais = subprocess.check_output(url,shell=True)
					pais = pais.rstrip()
					url = "{0}{1}{2}".format(hostname, ip, sed2)
					code = subprocess.check_output(url,shell=True)
					code = code.rstrip()
					time = datetime.now()

					conn = connect_db()

					inserir = conn.cursor()
					sql = "insert into block_country(date, ip, country, longitude, latitude, code) values ('{}','{}','{}','{}','{}','{}')".format(time, ip, pais, '', '',code.lower())
					#print(sql)
					inserir.execute(sql)
					conn.commit()

exec_geo()
