#!/usr/local/bin/python3.8
# -*- coding: utf-8 -*-
#
#  Copyright (C) 2014-2019 BluePex Security Company (R)
#  Wesley Peres <wesley.peres@bluepex.com
#  All rights reserved.
#

import sys

sys.path.append('/usr/local/etc/webfilter/')

import signal
import syslog
import re
import os
from datetime import datetime
import time
import daemon
import socket
import subprocess
from webfilter import process_data
from webfilter import process_data_fapp
from webfilter import set_prestart
from webfilter import create_wft
from webfilter import error_message
import xmltodict
from threading import Thread

class http_process(Thread):
	def __init__(self, file_http):
		Thread.__init__(self)
		self.fd = file_http
		self.http_logs = []

	def tail(self, fd, callback, delay=.1):
		fd.seek(0, 2) # seek to the end
		while 1:
			where = fd.tell()
			line = fd.readline()
			if line:
				if callback(line) is False:
					break
			else:
				time.sleep(delay)
				fd.seek(where)

	def init_process_data(self, line):
		self.http_logs.append(line)

		if len(self.http_logs) > 500:
			self.start_process_data_http_fapp = process_data_fapp(self.http_logs, 'http')
			self.start_process_data_http_fapp.start()
			self.http_logs = []

	def run(self):
		self.tail(self.fd, self.init_process_data)

