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
from xml_to_dict import return_xml_dict
from threading import Thread
import logging

class https_process(Thread):
	def __init__(self, file_https):
		Thread.__init__(self)
		self.fd = file_https
		self.https_logs = []

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
		self.https_logs.append(line)

		if len(self.https_logs) > 500:
			self.start_process_data_https_fapp = process_data_fapp(self.https_logs, 'https')
			self.start_process_data_https_fapp.start()
			self.https_logs = []

	def run(self):
		self.tail(self.fd, self.init_process_data)
