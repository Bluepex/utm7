#!/usr/local/bin/python
#  -*- coding: UTF-8 -*-
#
#  Copyright (C) 2019 BluePex Security Company (R)
#  Write Marcos Claudiano <marcos.claudiano@bluepex.com> 2019
#  Rewrite Guilherme R Brechot <guilherme.brechot@bluepex.com> 2023
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
#import ConfigParser
try:
    import configparser
except:
    from six.moves import configparser
import logging
import subprocess 
import xmltodict
from datetime import datetime
import json
import requests
import urllib.request
import urllib.parse
from multiprocessing import Pool
import redis
from operator import itemgetter
import maxminddb

platform = open("/etc/platform", "r").read().rstrip()

with open('/cf/conf/config.xml') as fd:
    xmldoc = xmltodict.parse(fd.read())

pattern = re.compile(r"(.*\/)(.*[%|\'|\"|\?])")
wf_instances = xmldoc['bluepex']['system']['webfilter']['instance']['info']['wf_instances']
wf_instances_interface = xmldoc['bluepex']['system']['webfilter']['instance']['info']['wf_instances_interface']
db_geoip_file = '/var/db/GeoLite2/GeoLite2-City.mmdb'
file_filter_log = '/var/log/filter.log'

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

def connect_redis():
	try:
		return redis.Redis(host='127.0.0.1', port=6379, db=0, socket_connect_timeout=3)
	except Exception as error:
		error_message.info("CONNECT DATABASE: {}".format(error))
	return False

def exec_geo(tail_limit):
	if not os.path.exists("{}".format(db_geoip_file)) or not os.path.exists("{}".format(file_filter_log)):
		return

	gateways = subprocess.check_output("/usr/local/bin/php -f /etc/inc/rc.list_gateways", shell=True).decode('utf-8')
	gateways_json = json.loads(gateways)

	for interface in gateways_json:
		file_filter_action = "{}.{}".format(file_filter_log, interface)
		file_filter_action_tmp = "{}.tmp".format(file_filter_action)

		subprocess.call(["tail", "-n", "{}".format(tail_limit), "{}".format(file_filter_log)], stdout=open("{}".format(file_filter_action), "w"))
		subprocess.call(["grep", ",{},".format(interface), "{}".format(file_filter_action)], stdout=open("{}".format(file_filter_action_tmp), "w"))
		subprocess.call(["mv", "{}".format(file_filter_action_tmp), "{}".format(file_filter_action)])

		with open(file_filter_action, 'r') as file_infos:
			lines = array_uniq_lines(file_infos.readlines())

		pool = Pool(processes=4)
		pool.map(validade_status_ip_mysql, lines)
		pool.close()

		file_infos.close()
		subprocess.call(["rm", "{}".format(file_filter_action)])

def validade_status_ip_redis(lines):
	try:
		response = reader.city(lines)
		code = response.country.iso_code
		pais = response.country.name
		time = datetime.now()
		time = time.strftime("%Y-%m-%d %H:%M:%S")

		try:
			if code is not None and pais is not None:
				redis_connection = connect_redis()
				redis_insert_dicty = {
					"date":time.rstrip(),
					"ip":lines,
					"country":pais.rstrip(),
					"code":code.lower()
				}
				redis_connection.rpush('block_country', json.dumps(redis_insert_dicty))
		except:
			pass
	except:
		pass

def validade_status_ip_mysql(lines):
	if (lines == ""):
		return

	try:
		conn = connect_db()
	except Exception as error:
		print("An exception occurred: No connection DB GeoIP", error)

	try:
		reader = maxminddb.open_database(db_geoip_file)
		counter = reader.get(lines)['country']['names']['en']
		code = reader.get(lines)['country']['iso_code']
		reader.close()

		if len(counter) == 0 or len(code) == 0:
			return

		try:
			insert = conn.cursor()
			sql = "INSERT INTO block_country(date, ip, country, longitude, latitude, code) VALUES ('{}','{}','{}','{}','{}','{}')".format("2023-10-06 00:00:00", lines, counter, '', '',code.lower())
			insert.execute(sql)
			conn.commit()
		except Exception as error:
			print("An exception occurred: No insert values to IP {} in DB: {}".format(lines, error))
	except Exception as error:
		print("An exception occurred: No get values to IP {}: {}".format(lines, error))

def array_uniq_lines(lines):
	arrayReturn = []
	for line in lines:
		try:
			ip = re.findall(r"\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}", line)[0]
			if ip not in arrayReturn and ip != "":
				arrayReturn.append(ip)
		except Exception:
			pass
	return arrayReturn

def exec_dados(seq, data):

	try:
		redis_connection = connect_redis()
	except:
		redis_connection = False

	try:
		conn = connect_db()
	except:
		conn = False

	if (redis_connection):
		if data == '21':
			try:
				if redis_connection.exists('block_country'):
					return_connection = redis_connection.lrange('block_country', 0, -1)
					code_arrys = []
					if (len(return_connection) > 0):
						for line in return_connection:
							code_arrys.append(json.loads(line.decode())['code'])
			
					only_codes = []
					for code_now in code_arrys:
						if code_now not in only_codes:
							only_codes.append(code_now)

					clean_array = []
					for code_now in only_codes:
						clean_array.append({'name':code_now, 'value':code_arrys.count(code_now)})

					clean_array = sorted(clean_array, key=itemgetter('value'), reverse=True) 

					return_clean = []
					for i in clean_array[0:3]:
						return_clean.append(i['name'].upper())
					
					site = ' '.join(return_clean)
					print(' '.join(return_clean))

			except Exception:
				pass
		if data == '22':
			try:
				if redis_connection.exists('block_country'):
					return_connection = redis_connection.lrange('block_country', 0, -1)
					code_arrys = []
					if (len(return_connection) > 0):
						for line in return_connection:
							code_arrys.append(json.loads(line.decode())['code'])
			
					only_codes = []
					for code_now in code_arrys:
						if code_now not in only_codes:
							only_codes.append(code_now)

					clean_array = []
					for code_now in only_codes:
						clean_array.append(code_arrys.count(code_now))

					clean_array.sort(reverse=True)
					clean_array = clean_array[0:3]
					
					site = ' '.join(str(value) for value in clean_array)
					print(site)

			except Exception:
				pass
		if data == '23':
			try:
				if redis_connection.exists('block_country'):
					site = len(redis_connection.lrange('block_country', 0, -1))
					print(site)

			except Exception:
				pass
		if data == '25':
			try:
				if redis_connection.exists('block_country'):
					return_connection = redis_connection.lrange('block_country', -1, -1)
					if (len(return_connection) > 0):
						for line in return_connection:
							ip = json.loads(line.decode())['ip']
							if (len(ip) > 0):
								site = ip
								print(site)
			except Exception:
				pass
		if data == '27':
			try:
				if redis_connection.exists('block_country'):
					return_connection = redis_connection.lrange('block_country', 0, -1)
					code_arrys = []
					if (len(return_connection) > 0):
						for line in return_connection:
							code_arrys.append(json.loads(line.decode())['country'])

					clean_array = []
					for line in code_arrys:
						if line not in clean_array:
							clean_array.append(line)

					site = len(clean_array)
					print(len(clean_array))

			except Exception:
				pass
		if data == '28':
			try:
				if redis_connection.exists('block_country'):
					return_connection = redis_connection.lrange('block_country', -5000, -1)
					code_arrys = []
					if (len(return_connection) > 0):
						for line in return_connection:
							code_arrys.append(json.loads(line.decode())['country'])
			
					only_codes = []
					for code_now in code_arrys:
						if code_now not in only_codes:
							only_codes.append(code_now)

					arquivo = open('/usr/local/www/active_protection/tentativas_invasao', 'w')

					data = "{\"data\":["
					for code_now in only_codes:
						data += "{{\"name\": \"{0}\", \"value\": {1}}},".format(code_now, code_arrys.count(code_now))
					data = data.rstrip(',')
					data += "]}"
				
					print(data)
					arquivo.write(str(data))
					arquivo.close()

			except Exception:
				pass
		if data == '29':
			try:
				if redis_connection.exists('block_country'):
					redis_connection.delete('block_country')
			except Exception:
				pass

	if (conn):
		if data == '1':
			try:			
				data_cursor = conn.cursor()
				sql = "SELECT UPPER(code) from (select code, count(code) as qtd  from block_country group by code)x ORDER BY qtd DESC LIMIT 3"
				data_cursor.execute(sql)
				site = ""
				if data_cursor.rowcount > 0:
					site = data_cursor.fetchall()[int(seq)][0]
				print(site.rstrip())
			except Exception:
				pass
		if data == '2':
			try:
				data_cursor = conn.cursor()
				sql = "SELECT qtd from (select code, count(code) as qtd  from block_country group by code)x ORDER BY qtd DESC LIMIT 3"
				data_cursor.execute(sql)
				site = ""
				if data_cursor.rowcount > 0:
					site = data_cursor.fetchall()[int(seq)][0]
				print(site)
			except Exception:
				pass
		if data == '3':
			try:
				data_cursor = conn.cursor()
				sql = "select count(id) as qtd from block_country"
				data_cursor.execute(sql)
				site = data_cursor.fetchall()[int(seq)][0]
				print(site)
			except Exception:
				pass
		if data == '4':
			try:
				data_cursor = conn.cursor()
				sql = "select count(qtd) as qtd from (select COUNT(id) as qtd from alerts_dash where rule like 'A%' group by rule LIMIT 100)x"
				data_cursor.execute(sql)
				site = data_cursor.fetchall()[int(seq)][0]
				print(site)
			except Exception:
				pass
		if data == '5':
			try:
				data_cursor = conn.cursor()
				sql = "select ip from block_country order by id DESC LIMIT 1"
				data_cursor.execute(sql)
				site = ""
				if data_cursor.rowcount > 0:
					site = data_cursor.fetchall()[int(seq)][0]
				print(site)
			except Exception:
				pass
		if data == '6':
			try:
				data_cursor = conn.cursor()
				sql = "select rule from alerts where rule like '%ET%' LIMIT 1"
				data_cursor.execute(sql)
				site = ""
				if data_cursor.rowcount > 0:
					site = data_cursor.fetchall()[int(seq)][0]
				print(site)
			except Exception:
				pass
		if data == '7':
			try:
				data_cursor = conn.cursor()
				sql = "select count(qtd) as qtd from (select country, count(country) as qtd  from block_country group by country)x"
				data_cursor.execute(sql)
				site = data_cursor.fetchall()[int(seq)][0]
				print(site)
			except Exception:
				pass
		if data == '8':
			try:
				arquivo = open('/usr/local/www/active_protection/tentativas_invasao', 'w')
				data_cursor = conn.cursor()
				sql = "select count(country) as qtd  from block_country"
				data_cursor.execute(sql)
				results = data_cursor.fetchall()
				if results[0][0] < 5000:
						sql = "select country, count(country) as qtd from block_country group by code"
						data_cursor.execute(sql)
				else:
						sql = "select country, count(country) as qtd from block_country where id > (select (id - 5000) from block_country order by id desc limit 1) group by code"
						data_cursor.execute(sql)
				results = data_cursor.fetchall()
				data = "{\"data\":["
				for row in results:
					data += "{{\"name\": \"{0}\", \"value\": {1}}},".format(str(row[0]), str(row[1]))
				data = data.rstrip(",")
				data += "]}"
				print(data)
				arquivo.write(str(data))
				arquivo.close()
			except Exception:
				pass
		if data == '9':
			try:
				exec_geo(5000)
			except Exception:
				pass
		if data == '10':
			try:
				delete = conn.cursor()
				sql = "delete from block_country"
				delete.execute(sql)
				conn.commit()
			except Exception:
				pass
		if data == '11':
			try:
				exec_geo(10000)
			except Exception:
				pass
		if data == '14' and subprocess.check_output(["/bin/pgrep -l -f suricata;exit 0"], shell=True) != "":
			try:
				list_top5 = open('/usr/local/www/list_top5', 'w')
				list_val_top5 = open('/usr/local/www/list_top_val_top5', 'w')
				name = []
				value = []
				data_cursor = conn.cursor()
				sql = "SELECT * FROM (SELECT a.rule, count(a.id_rule) AS total FROM alerts_dash a GROUP BY a.rule LIMIT 100)as i ORDER by total DESC limit 5;"
				data_cursor.execute(sql)
				top = data_cursor.fetchall()
				if len(top) > 0:
					for item in top:
						name.append(item[0].rstrip())
						value.append(str(item[1]))
				if len(name) < 5:
					for counter in range(len(name), 5):
						name.append('www')
						value.append('0')
				name_adj = ",".join(name)
				#print(name_adj)
				list_top5.write(name_adj)
				list_top5.close()
				value_adj = ",".join(value)
				#print(value_adj)
				list_val_top5.write(value_adj)
				list_val_top5.close()
			except Exception:
				pass
	else:
		site = ""

#arg1 = nome pais, arq2 = qtd
exec_dados(sys.argv[1],sys.argv[2])
