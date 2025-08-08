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

class webfilter_process(Thread):
	def __init__(self):
		Thread.__init__(self)

		self.host = "/var/run/wfrotated.sock"

		if os.path.exists(self.host):
			os.remove(self.host)

		self.l_logs = []
		self.wfs = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
		self.wfs.bind(self.host)
		self.wfs.listen(10000)
		create_wft(self.host)

	def run(self):
		syslog.openlog('redirector', logoption=syslog.LOG_INFO, facility=syslog.LOG_LOCAL6)

		while True:
			try:
				client, addr = self.wfs.accept()

				self.log = client.recv(4096).decode('utf-8')

				line = self.log.rstrip()

				if "redirector" in line and len(line.split()[5:]) == 8:
					if re.match(r"(https?:\/\/)", line.split()[6]):
						self.l_logs.append(line)
						syslog.syslog(" ".join(line.split()[5:]))

				if re.match(r".*[redirector|squid].*", line):
					if "ROUNDROBIN_PARENT" in line:
						print >> open('/var/log/reverse_proxy.log', 'a'), line
						client.close()

				if len(self.l_logs) > 500:
					start_process_data = process_data(self.l_logs)
					start_process_data.start()
					self.l_logs = []

			except Exception as error:
				error_message.info("{}\n{}\n".format(error, sys.exc_info()))

