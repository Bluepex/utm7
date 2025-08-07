#!/usr/local/bin/python
#  -*- coding: UTF-8 -*-
#
#  Copyright (C) 2015-2022 BluePex Security Company (R)
#  Wesley Peres <desenvolvimento@bluepex.com>
#  All rights reserved.
#

import os
import re
import csv
import sys
import time
import smtplib
import MySQLdb
import sqlite3
from datetime import datetime
import email.utils
import configparser
import logging
from xml_to_dict import return_xml_dict
from logging import config
from threading import Thread
from tldextract import extract
from email.mime.text import MIMEText
from itertools import groupby
from subprocess import check_output, call, run
from random import randint

platform = open("/etc/platform", "rb").read().rstrip()
xmldoc = return_xml_dict().run()
pattern = re.compile(r"^(.*)?(\/.*[%|\'|\"|\?])?")

wf_instances = ""
wf_instances_interface = ""

if xmldoc['system']['webfilter']['instance']['info']['wf_instances']:
	wf_instances = xmldoc['system']['webfilter']['instance']['info']['wf_instances']
	wf_instances_interface = xmldoc['system']['webfilter']['instance']['info']['wf_instances_interface']

status_services = {
	"wfrotate": "alert",
	"mysql": "alert"
}

log_referer = xmldoc['system']['webfilter']['nf_reports_settings']['element0']['remote_reports']

def check_syslogd():

    get_time = time.time()

    while (get_syslogd_processes() > 1 or time.time() - get_time > 300):
        call(['/bin/pkill -f "syslogd -s"'], shell=True)

    if (get_syslogd_processes() != 1):
        call(['service syslogd restart'], shell=True)

def get_syslogd_processes():

	return len(list(filter(None, check_output(['pgrep -f "syslogd -s"; exit 0'], shell=True).decode('utf-8').split('\n'))))

LOGGING = {
	'version': 1,
	'disable_existing_loggers': False,
	'formatters': {
		'verbose': {
			'format': 'WFMonitor: %(message)s'
		},
	},
	'handlers': {
		'stdout': {
			'class': 'logging.StreamHandler',
			'stream': sys.stdout,
			'formatter': 'verbose',
		},
		'sys-logger6': {
			'class': 'logging.handlers.SysLogHandler',
			'address': '/var/run/log',
			'facility': "local6",
			'formatter': 'verbose',
		},
	},
	'loggers': {
		'wf-logger': {
			'handlers': ['sys-logger6', 'stdout'],
			'level': logging.INFO,
			'propagate': True,
		},
	}
}

check_syslogd()

config.dictConfig(LOGGING)
error_message = logging.getLogger("wf-logger")


def get_config(config_to_read):

	try:
		config = configparser.ConfigParser()

		if not os.path.exists('/usr/local/etc/webfilter/wf_monitor.cfg'):
			'''
			print >> open(
				'/usr/local/etc/webfilter/wf_monitor.cfg',
				'w'), """[main]
time_process =        2

[email_users]
contatos =            suporte@bluepex.com

[time_mysql]
check_mysql_entry =    off
time_mysql_entry =    1800

[wf_logs]
lines_log_update =  1000"""
			'''

		config.read('/usr/local/etc/webfilter/wf_monitor.cfg')
		if config_to_read == 'time_process':
			if config.get('main', 'time_process') != "":
				return float(config.get('main', 'time_process'))
			else:
				return 2.0
		if config_to_read == 'contatos':
			return config.get('email_users', 'contatos').split(',')
		if config_to_read == 'time_mysql_entry':
			return int(config.get('time_mysql', 'time_mysql_entry'))
		if config_to_read == 'check_mysql_entry':
			return config.get('time_mysql', 'check_mysql_entry')
		if config_to_read == 'lines_log_update':
			return int(config.get('wf_logs', 'lines_log_update'))
	except Exception as error:
		error_message.info("{} | {}".format(error, sys.exc_info()[0]))


def log_process():

	w_data = csv.writer(open('/usr/local/etc/webfilter/wf_monitor_services', 'w'))
	for k, v in status_services.items():
		w_data.writerow([k, str(v).replace("\"", "")])

def check_mysql_connect():

	if not connect_db():
		if platform != "nanobsd":
			status_services['mysql'] = "off"
			error_message.info("WFMonitor: Process mysqld is not running...")
			log_process()
			try_cont = 0
			while try_cont < 5:
				try_cont = try_cont + 1
				#os.system('/usr/local/etc/rc.d/mysql_server restart; exit 0')
				os.system('/usr/local/etc/rc.d/mysql-server restart; exit 0')
				time.sleep(1)
				if connect_db():
					status_services['mysql'] = "ok"
					error_message.info("Process mysqld is running...")
					break
	else:
		status_services['mysql'] = "ok"

	log_process()


class check_process(Thread):

	global status_services
	global log_process

	def __init__(self, cp_service):

		Thread.__init__(self)
		self.process_ck = cp_service[1]
		self.process_rc = cp_service[2]
		self.process_name = cp_service[0]
		self.process_t = time.time()

	def run(self):
		os.system(self.process_rc)

		try:
			#debug_tmp = run(["/usr/local/sbin/wfrotated"], capture_output=True, stdin=sys.stdin)
			debug_tmp = run(["service", "wfrotated", "restart"], capture_output=True, stdin=sys.stdin)
			print(debug_tmp, file=open('/tmp/debug_threads_python.log', 'w'))

		except Exception as error:
			debug_tmp = error
			print(debug_tmp, file=open('/tmp/debug_threads_python.log', 'w'))


		run(["/usr/local/sbin/wfrotated"])

		if self.process_ck == "wfrotated":
			if check_output(["/bin/pgrep -l -f /usr/local/sbin/{};exit 0".format(self.process_name)], shell=True).decode('utf-8') == "":
				os.system("/etc/rc.d/syslogd restart")

		if check_output(["/bin/pgrep -f {}; exit 0".format(self.process_ck)], shell=True).decode('utf-8') != "":
			status_services[self.process_name] = "ok"
			log_process()
			return
		else:
			error_message.info("WFMonitor: Process {} is not running...".format(self.process_name))

		while time.time() - self.process_t < 5:
			error_message.info("WFMonitor: Process {} is not running. Trying to start...".format(
				self.process_name))

			os.system(self.process_rc)

			if check_output(["/bin/pgrep -l -f {}; exit 0".format(
			   self.process_ck)], shell=True).decode('utf-8') != "":
				log_process()
				return
			else:
				error_message.info("WFMonitor: Process {} is not running...".format(self.process_name))

				os.system(self.process_rc)

				time.sleep(1)

			status_services[self.process_name] = "off"

		log_process()


sqd = " ".join([
	'/usr/local/sbin/squid -s -f /usr/local/etc/squid/squid0.conf;',
	'/usr/local/sbin/squid -k reconfigure -f /usr/local/etc/squid/squid0.conf;',
	'exit 0'])


def check_var_folder():

	global log_process

	df_output = check_output(['/bin/df -k /var; exit 0'], shell=True).decode('utf-8').split()
	capacity_u = int(df_output[-2].replace("%", ""))
	capacity_t = int(df_output[-5])
	status_services["diskusage"] = capacity_u
	log_process()
	if capacity_u > 85:
		var_squid = int(check_output(
			['/usr/bin/du -s /var/squid/logs; exit 0'], shell=True).decode('utf-8').split()[0])
		if var_squid > (capacity_t / 2):
			os.system('/bin/rm -rf /var/squid/logs/*; exit 0')
			os.system(sqd)


def check_pipe_process():

	pipe_processes = len(filter(None, check_output(
		['/bin/pgrep -f "cat -u"; exit 0'], shell=True).decode('utf-8').split()))
	if pipe_processes > 5:
		os.system('/bin/pkill -f "cat -u"; exit 0')


def connect_db():

	report_settings = xmldoc['system']['webfilter']['nf_reports_settings']
	if report_settings:
		#mysqlUser = report_settings['element0']['reports_user']
		#mysqlIP = report_settings['element0']['reports_ip']
		#mysqlPass = report_settings['element0']['reports_password']
		#mysqlDb = report_settings['element0']['reports_db']
		mysqlUser = "root"
		mysqlIP = "127.0.0.1"
		mysqlPass = "123"
		mysqlDb = "webfilter"
		try:
			return MySQLdb.connect(mysqlIP, mysqlUser, mysqlPass, mysqlDb, connect_timeout=3)
		except Exception as error:
			error_message.info("CONNECT DATABASE: {}".format(error))
			return False
	else:
		error_message.info("CONNECT DATABASE: Report settings not configured")
		return False


def categorize_netfilter(netfilter_list, scheme):

	_new_logs = []

	'''for log in netfilter_list:
		if log[3] == '-':
			parse_log = extract(log[1])
			parsed_domain = ".".join([parse_log.domain, parse_log.suffix])
			if not pattern.match(parsed_domain):
				categories = ['99']
			else:
				get_categories = check_output([
					'/usr/local/bin/wf_get_url_categories.py -c -u {}://{}'.format(scheme, parsed_domain)],
					shell=True).decode('utf-8')
				categories = filter(None, sorted(set(get_categories.rstrip().split(','))))
			if len(categories) == 1 and categories[0] == '0':
				categories = re.sub(r'^0$', '99', categories[0])
			else:
				categories = ",".join(categories)
			_new_logs.append((log[0], log[1], log[2], re.sub(r'^0,', '', categories), log[4], log[5], log[6]))
		else:
			_new_logs.append(log)'''

	categories = ['99']
	_new_logs.append((log[0], log[1], log[2], re.sub(r'^0,', '', categories), log[4], log[5], log[6]))

	return _new_logs


class process_data(Thread):

	global pattern
	global platform
	global categorize_netfilter

	def __init__(self, logs):

		Thread.__init__(self)

		self.pattern = re.compile(r".*(squid|redirector).*\:\ ")
		self.logs = list(set([self.pattern.split(log)[-1] for log in logs]))
		self.access = [log.split() for log in set(
			[log for log in self.logs if len(log.split()) == 11])]
		self.netfilter = [log.split() for log in set(
			[log for log in self.logs if len(log.split()) == 8 if not re.match(r'^https.*', log.split()[1])])]
		self.https = [log.split() for log in set(
			[log for log in self.logs if len(log.split()) == 8 if re.match(r'^https.*', log.split()[1])])]
		self.log = []
		self.send_data = []
		self.netfilter = categorize_netfilter(self.netfilter, "http")
		self.https = categorize_netfilter(self.https, "https")

	def run(self):

		try:
			for a_line in self.access:
				for idx, n_line in enumerate(self.netfilter):
					if(a_line[6].split('?') == n_line[1].split('?') and
					   a_line[0][0:8] == n_line[0][0:8] and
					   a_line[2] == n_line[4]):
						self.log.append((
							a_line[0], a_line[2], n_line[5], n_line[6],
							filter(None, n_line[1]), a_line[4], a_line[1], n_line[2],
							n_line[3], a_line[-5]))
						del self.netfilter[idx]
						break

			if(os.path.exists('/var/squid/logs/backup_data') and not
			   os.path.exists('/var/tmp/wfsendbkp.lock')):
				call([
					'/usr/local/bin/python -u /usr/local/bin/wfsendbkp.py'],
					shell=True)

			for log in sorted(self.https, key=lambda log: (log[0][0:8], log[1], log[4].split('?')[0])):
				self.log.append((log[0], log[4], log[5], log[6], filter(
					None, log[1]), "{}".format(randint(1024, 1048576)), "{}".format(randint(1024, 1048576)), log[2], log[3], "https"))

			start_insert_data = insert_data(self.log)
			start_insert_data.start()

		except Exception as error:
			error_message.info("PROCESS DATA: {}".format(error))
			if(not os.path.exists('/var/squid/logs/backup_data') or
					int(os.path.getsize('/var/squid/logs/backup_data') < 5242880 or platform != "nanobsd")):
				for log in sorted(set(self.send_data)):
					print >> open(
						'/var/squid/logs/backup_data', 'a', 0), " ".join(log)


class insert_data(Thread):

	global log_referer
	global garbage

	def __init__(self, insert_logs):

		Thread.__init__(self)
		self.insert_logs = insert_logs
		self.conn = connect_db()
		self.insert = self.conn.cursor()
		self.s_insert = "insert into accesses(time_date, ip, username, groupname, url_str, size_bytes, elapsed_ms, \
			blocked, url_no_qry, url_path, categories) values ('{}','{}','{}','{}','{}','{}','{}', '{}', '', '{}', '')"

	def run(self):

		try:

			url_no_referers = [
				log for log in self.insert_logs if re.match(r"-|https", log[-1])]

			groups = [{url: list(group)} for url, group in groupby(sorted(
				self.insert_logs, key=lambda log: (log[0][0:8], log[2], log[4])), lambda log: log[4])]

			for group in groups:
				for idx, log in enumerate(group.get(group.keys()[0])):
					if not re.match(r"-|https", log[-1]):
						self.time = datetime.fromtimestamp(
							int(log[0].replace(",", ".").split('.')[0])).strftime('%Y-%m-%d %H:%M:%S')
						if re.match(r'^[0-9]{4}', log[7]):
							self.blocked = 1
						else:
							self.blocked = 0
						if idx == 0 or log[-1] != self.insert_logs[idx - 1][-1]:
							self.domain = extract(log[-1])
							self.insert.execute(self.s_insert.format(
								self.time,
								log[1],
								log[2],
								log[3],
								re.sub(r"^(\.)(.*)", "\g<2>", ".".join([
									self.domain.subdomain, self.domain.domain, self.domain.suffix])),
								log[6],
								log[7],
								self.blocked,
								log[-1]))
							lastid = int(self.insert.lastrowid)
							for id_categories in re.split(',', log[8]):
								self.insert.execute(
									"insert into access_categories (accesses_id,categories_id) values ('{}', \
									'{}')".format(lastid, int(re.sub('-', '99', id_categories))))
								self.conn.commit()
						else:
							if log_referer == 'on' and log[4] != '':
								self.insert.execute("insert into referers (id_referer,url_referer) values (\
									'{}', '{}')".format(lastid, log[4]))
							self.conn.commit()
			for log in url_no_referers:
				self.time = datetime.fromtimestamp(
					int(log[0].replace(",", ".").split('.')[0])).strftime('%Y-%m-%d %H:%M:%S')
				if re.match(r'^[0-9]{4}', log[7]):
					self.blocked = 1
				else:
					self.blocked = 0
				self.domain = extract(log[4])
				self.insert.execute(self.s_insert.format(
					self.time,
					log[1],
					log[2],
					log[3],
					re.sub(r"^(\.)(.*)", "\g<2>", ".".join([
						self.domain.subdomain, self.domain.domain, self.domain.suffix])),
					log[6],
					log[7],
					self.blocked,
					log[4]))
				self.conn.commit()
				lastid = int(self.insert.lastrowid)
				for id_categories in re.split(',', log[8]):
					self.insert.execute(
						"insert into access_categories (accesses_id,categories_id) values ('{}','{}')\
						".format(lastid, int(re.sub('-', '99', id_categories))))
					self.conn.commit()
			self.conn.commit()
			self.insert.close()
			self.conn.close()
		except Exception as error:
			error_message.info("INSERT DATA: {}".format(error))

##################################
# FIREWALLAPP
##################################

def categorize_netfilter_fapp(netfilter_list, scheme):

	_new_logs = []

	for log in netfilter_list:
		parse_log = extract(log[1])
		parsed_domain = ".".join([parse_log.domain, parse_log.suffix])

		is_ip = re.match(r"^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})?$", log[2])

		"""if is_ip:
			src_ip = log[2]
			get_categories = check_output([
				'/usr/local/bin/wf_get_url_categories.py -c -u {}://{}'.format(scheme, parsed_domain)],
				shell=True)
			categories = filter(None, sorted(set(get_categories.rstrip().split(','))))
			if len(categories) == 1 and categories[0] == '0':
				categories = re.sub(r'^0$', '99', categories[0])
			else:
				categories = ",".join(categories)
		else:
			src_ip = log[4]
			categories = '99'
		"""
		src_ip = "123"
		categories = '99'

	_new_logs.append((log[0], log[1], src_ip, log[3], (re.sub(r'^0,', '', categories))))

	return _new_logs

class process_data_fapp(Thread):

	global pattern
	global platform
	global categorize_netfilter

	def __init__(self, logs, log_type):

		Thread.__init__(self)

		self.log_type = log_type
		self.netfilter = []
		self.log_http = []
		self.log_https = []
		self.log_alerts = []
		self.log_sshd = []
		self.lines_log_http = []
		self.lines_log_https = []
		self.lines_log_alerts = []
		self.lines_log_sshd = []

		#print(log_type)

		if log_type == "http":
			for line in logs:
				#print("http: {}".format(line))
				pattern = re.findall(r"(\d{2}\/\d{2}\/\d{4}-\d{2}:\d{2}:\d{2}).\d{1,}\s([?!:\/\/a-zA-Z0-9-_.|\<hostname unknown\>]+)\[\*\*\]([?!:\/\/a-zA-Z0-9-_.=&]+)", line)
				
				if len(pattern) == 0:
					continue

				if pattern[0][1] != "<hostname unknown>":
					line_timestamp = pattern[0][0]
					date_time = datetime.strptime(line_timestamp, '%m/%d/%Y-%H:%M:%S')
					time_stamp = int(time.mktime(date_time.timetuple()))
					time_db = datetime.fromtimestamp(int(time_stamp)).strftime('%Y-%m-%d %H:%M:%S')

					pattern_status = line.split("[**]")[6]

					src_ip = re.findall(r"(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})", line.split("[**]")[8])[0]

					if len(pattern_status) > 0:
						if pattern_status == "<no status>":
							status = 0
						else:
							status = pattern_status[0:3]
					else:
						status = ''

					pattern_size_bytes = line.split("[**]")[7]

					if len(pattern_size_bytes) > 0:
						size_bytes = pattern_size_bytes.replace(" bytes", "")
					else:
						size_bytes = 0

					line_log = "{0} {1} {2} {3} {4}".format(time_stamp, pattern[0][1], status, size_bytes, src_ip)
					self.lines_log_http.append(line_log)



		if log_type == "https":
			for line in logs:
				#print("https: {}".format(line))
				line_timestamp = re.match(r"(\d{2}\/\d{2}\/\d{4}-\d{2}:\d{2}:\d{2})", line).group()

				date_time = datetime.strptime(line_timestamp, '%m/%d/%Y-%H:%M:%S')
				time_stamp = int(time.mktime(date_time.timetuple()))
				time_db = datetime.fromtimestamp(int(time_stamp)).strftime('%Y-%m-%d %H:%M:%S')

				line_url = re.findall(r"(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}).*SNI='([?!:\/\/a-zA-Z0-9-_.=&]+)'", line.rstrip())

				if len(line_url) > 0:
					url = line_url[0][1]

					is_ip = re.match(r"^(?:http|ftp)s?:\/\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\:\d{1,5})?(\/)?$", url)

					if not is_ip:
						try:
							size_byte = subprocess.check_output(["curl", "-o", "/dev/null", "-s",  "-w", "%{size_download}", hostname])
						except Exception as error:
							size_byte = randint(1024, 1048576)
					else:
						size_byte = randint(1024, 1048576)

					if size_byte == 0:
						size_byte = randint(1024, 1048576)

					src_ip = line_url[0][0]

					line_log = "{0} {1} {2} {3}".format(time_stamp, url, src_ip, size_byte)
					#print (line_log)
					self.lines_log_https.append(line_log)


		if log_type == "alerts":
			for line in logs:
				alert = re.findall(r"(\d{2}\/\d{2}\/\d{4}-\d{2}:\d{2}:\d{2}).\d{6}\s\s(\[Drop\]\s|)\[(\*{2})\]\s\[(\d+):(\d+):(\d+)\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s\{([a-zA-Z]*)}\s(\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}:\d{1,})\s(->|<-)\s(\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}:\d{1,})", line.rstrip())

				if len(alert) > 0:
					line_timestamp = alert[0][0]
					date_time = datetime.strptime(line_timestamp, '%m/%d/%Y-%H:%M:%S')
					time_stamp = int(time.mktime(date_time.timetuple()))

					id_rule = alert[0][4]

					if (alert[0][1] == "[Drop] "):
						action = "drop"
					else:
						action = "alert"

					rule = alert[0][6]

					group_r = alert[0][7]
					group_rule = group_r.replace(" ","-")
					classification = alert[0][8]
					protocol = alert[0][9]
					src_ip_port = alert[0][10]
					direction = alert[0][11]
					dst_ip_port = alert[0][12]

					line_log = "{0} {1} {2} {3} {4} {5} {6} {7} {8} {9}".format(time_stamp, id_rule, action, rule, group_rule, classification, protocol, src_ip_port, direction, dst_ip_port)
					
					self.lines_log_alerts.append(line_log)

		if log_type == "sshd":
			for line in logs:

				action = ""

				#print line

				sshd = re.findall(r"([a-zA-Z]*\s{1,2}\d{1,2}\s\d{2}:\d{2}:\d{2})\s([_a-zA-Z]*)\s([a-zA-Z\[\d{5}\]]*):\s([a-zA-Z]*\s[a-zA-Z- \/]*)\s([a-zA-Z]*)\s([a-zA-Z]*)\s(([A-Za-z]*)|(\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}))\s([a-zA-Z]*)\s(\d{5})", line.rstrip(), re.MULTILINE)

				#print sshd

				if len(sshd) > 0:

					line_timestamp = datetime.now()
					date_time = line_timestamp.strftime("%m/%d/%Y-%H:%M:%S")
					date_time2 = datetime.strptime(date_time, '%m/%d/%Y-%H:%M:%S')
					time_stamp = int(time.mktime(date_time2.timetuple()))

					if sshd[0][3] == "Accepted keyboard-interactive/pam for":
						action = "connect"
						user = sshd[0][4]
						src_ip = sshd[0][6]
						port = sshd[0][10]
						date = sshd[0][0]
						date = date.replace(" ","-")
						#date = date+"-"+y

					elif sshd[0][3] == "Disconnected from":
						action = "disconnect"
						user = sshd[0][5]
						src_ip = sshd[0][6]
						port = sshd[0][10]
						date = sshd[0][0]
						date = date.replace(" ","-")
						#date = date+"-"+y			
					
					line_log = "{0} {1} {2} {3} {4} {5}".format(time_stamp, action, user, src_ip, port, date)
					self.lines_log_sshd.append(line_log)

	def run(self):
		#try:
		if self.log_type == 'http':
			lines_http = []
			for line in self.lines_log_http:
				if len(line.split()) == 5:
					lines_http.append(line.split())
			#self.log_http = categorize_netfilter_fapp(lines_http, "http")
			start_insert_data_fapp_http = insert_data_fapp(self.log_http, self.log_type)
			start_insert_data_fapp_http.start()
		if self.log_type == "https":
			lines_https = []
			for line in self.lines_log_https:
				if len(line.split()) == 4:
					lines_https.append(line.split())
			self.log_https = categorize_netfilter_fapp(lines_https, "https")
			print (self.log_https)
			start_insert_data_fapp_https = insert_data_fapp(self.log_https, self.log_type)
			start_insert_data_fapp_https.start()
		if self.log_type == "alerts":
			lines_alerts = []
			for line in self.lines_log_alerts:
				lines_alerts.append(line.split())
			self.log_alerts = lines_alerts
			#print(lines_alerts)
			start_insert_data_fapp_alerts = insert_data_fapp(self.log_alerts, self.log_type)
			start_insert_data_fapp_alerts.start()
		if self.log_type == "sshd":
			lines_sshd = []
			for line in self.lines_log_sshd:
				lines_sshd.append(line.split())
			self.log_sshd = lines_sshd
			#print self.log_sshd
			start_insert_data_fapp_sshd = insert_data_fapp(self.log_sshd, self.log_type)
			start_insert_data_fapp_sshd.start()

		#except Exception as error:
		#	error_message.info("PROCESS DATA: {}".format(error))

class insert_data_fapp(Thread):

	global log_referer
	global garbage

	def __init__(self, insert_logs, log_type):

		Thread.__init__(self)
		self.log_type = log_type
		self.insert_logs = insert_logs
		self.time = ""
		self.host = ""

		self.conn = connect_db()
		self.insert = self.conn.cursor()

		if log_type == 'http':
			self.s_insert = "insert into http(time_date, ip, username, groupname, url_str, size_bytes, elapsed_ms, \
				blocked, url_no_qry, url_path, categories) values ('{}','{}','{}','{}','{}','{}','{}', '{}', '', '{}', '')"

		if log_type == 'https':
			self.s_insert = "insert into https(time_date, ip, username, groupname, url_str, size_bytes, elapsed_ms, \
				blocked, url_no_qry, url_path, categories) values ('{}','{}','{}','{}','{}','{}','{}', '{}', '', '{}', '')"

		if log_type == 'alerts': 
			self.s_insert = "insert into alerts(time_date, id_rule, action, rule, classification, priority, \
				protocol, src_ip_port, dir, dst_ip_port) values ('{}','{}','{}','{}','{}','{}','{}', '{}', '{}', '{}')"

		if log_type == 'sshd': 
			self.s_insert = "insert into sshd(time_date, action, user, src_ip, port, date) values ('{}','{}','{}','{}','{}','{}')"

	def run(self):

		try:
			logs = [log for log in self.insert_logs]

			for log in logs:
				self.time = datetime.fromtimestamp(int(log[0])).strftime('%Y-%m-%d %H:%M:%S')

				if xmldoc['system']['firewallapp']['type'] == 1:
					conn = sqlite3.connect('/var/db/captiveportalfirewallapp_lan.db')
					cursor = conn.cursor()

					cursor.execute("SELECT username FROM captiveportal where ip='{}'".format(log[2]))

					row = cursor.fetchone()[0]

					conn.close()

					if row != "":
						self.host = row
				else:
					self.host = ""

				if self.log_type == 'alerts':
					self.insert.execute(self.s_insert.format(
						self.time,
						log[1],
						log[2],
						log[3],
						log[4],
						log[5],
						log[6],
						log[7],
						log[8],
						log[9]
					))

				elif self.log_type == 'sshd':
					self.insert.execute(self.s_insert.format(
						self.time,
						log[1],
						log[2],
						log[3],
						log[4],
						log[5]
					))

				else:
					self.insert.execute(self.s_insert.format(
						self.time,
						log[2],
						self.host,
						'',
						log[1],
						log[3],
						0,
						0,
						'',
						'',
						''
					))

				self.conn.commit()

				lastid = int(self.insert.lastrowid)

				if len(log) > 4:
					for id_categories in re.split(',', log[4]):
						if self.log_type == "http":
							self.insert.execute(
								"insert into access_categories (accesses_id, accesses_id_http, categories_id) values ('{}', \
								'{}', '{}')".format(0, lastid, int(re.sub('-', '99', id_categories))))
							self.conn.commit()
						if self.log_type == "https":
							self.insert.execute(
								"insert into access_categories (accesses_id, accesses_id_https, categories_id) values ('{}', \
								'{}', '{}')".format(0, lastid, int(re.sub('-', '99', id_categories))))
							self.conn.commit()
						if self.log_type == "alerts":
							log[3]
						if self.log_type == "sshd":
							log[3]

			self.conn.commit()
			self.insert.close()
			self.conn.close()

		except Exception as error:
			error_message.info("INSERT DATA: {}".format(error))

def set_prestart():

	if os.path.exists('/var/run/wfrotated.pid'):
		os.unlink('/var/run/wfrotated.pid')
	#print >> open('/var/run/wfrotated.pid', 'w'), str(os.getpid())
	print(str(os.getpid()), file=open('/var/run/wfrotated.pid', 'w'))
	time.sleep(1)

def create_wft(wft_host):

	if os.path.exists('/usr/local/bin/wft_log.sh'):
		os.unlink('/usr/local/bin/wft_log.sh')

	data_wft = """#!/bin/sh
#
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# <desenvolvimento@bluepex.com>, 2015
# ====================================================================
#

while read LINE; do
   echo "${{LINE}}" | nc -w 1 -U {} &
done""".format(wft_host)

	#print >> open('/usr/local/bin/wft_log.sh', 'a'), data_wft
	print(data_wft, file=open('/usr/local/bin/wft_log.sh', 'a'))
	os.system('/bin/chmod +x /usr/local/bin/wft_log.sh; exit 0')


def send_mail():

	c_email = "gnteste@gmail.com"
	user = "gnteste"
	passwd = "Gn.teste."
	if os.path.exists('/etc/serial'):
		serial = check_output(['/bin/cat /etc/serial'], shell=True).decode('utf-8').rstrip()
	else:
		serial = ''
	wan_ip = check_output(["echo 'cat //interfaces/wan/ipaddr' | /usr/local/bin/xmllint --shell /cf/conf/config.xml | \
		sed '/^\/ >/d' | sed 's/<[^>]*.//g'"], shell=True).decode('utf-8')
	host = check_output(['/bin/hostname'], shell=True).decode('utf-8').rstrip()
	msg = MIMEText("""
Rotacionamento de logs parado!
Hostname:   {}
Serial:     {}
WAN Ip:     {}
""".format(host, serial, wan_ip))
	msg['Subject'] = "{}: Rotacionamento de logs parado! [TESTE]".format(host)
	msg['From'] = email.utils.formataddr((host, c_email))

	try:
		server = smtplib.SMTP("smtp.gmail.com:587")
		server.starttls()
		server.login(user, passwd)
		for cont in get_config('contatos'):
			msg['To'] = email.utils.formataddr(("BP Analyst", cont))
			server.sendmail(c_email, cont, msg.as_string())
		server.quit()
		error_message.info("WFMonitor: Email enviado...")
	except Exception as error:
		error_message.info("Email não enviado..")
		error_message.info("SendMail: {}".format(error))


def check_syslogd():

	get_time = time.time()

	while (get_syslogd_processes() > 1 or time.time() - get_time > 300):
		call(['/bin/pkill -f "syslogd -s"'], shell=True)

	if (get_syslogd_processes() != 1):
		call(['service syslogd restart'], shell=True)


def check_mysql_entry():

	check_time = get_config('time_mysql_entry')
	conn = connect_db()
	if (conn):
		cur = conn.cursor()
		cur.execute(
			'select time_date from accesses order by id desc limit 1')

		if len(cur.fetchall()) > 0:
			get_entry_time = time.mktime(cur.fetchall()[0][0].timetuple())
		else:
			error_message.info("WFMonitor: Nao houve entrada no banco a mais de {} segundos...".format(check_time))
			return True

		if time.time() - get_entry_time > check_time:
			error_message.info("WFMonitor: Ultima entrada no banco a mais de {} segundos...".format(check_time))
			return False
		else:
			error_message.info("WFMonitor: Ultima entrada no banco em menos de {} segundos...".format(check_time))
			return True
	else:
		error_message.info("WF_MONITOR: Não foi possivel conectar ao banco.")
		return False


class check_reverse_proxy_log(Thread):

	def __init__(self):
		Thread.__init__(self)
		self.log_file = "/var/log/reverse_proxy.log"

	def run(self):
		while True:
			if os.path.exists(self.log_file):
				log_size = os.path.getsize(self.log_file)
				if log_size > 1024000:
					os.system('/bin/mv {} {}.1'.format(self.log_file, self.log_file))
			time.sleep(60)
