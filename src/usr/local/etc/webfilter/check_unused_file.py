#!/usr/local/bin/python3.8
# -*- coding: utf-8 -*-
#
#  Copyright (C) 2017 BluePex Security Company (R)
#  Silvio Giunge <desenvolvimento@bluepex.com>
#  All rights reserved.
#


import re
import time
from threading import Thread
from webfilter import error_message
from subprocess import call, check_output


class check_unused_file(Thread):

    def __init__(self):
        Thread.__init__(self)

    def run(self):
        while True:
            get_time = time.ctime(time.time() - 300).split()
            h, m, s = get_time[3].split(":")
            pattern_time = "({}:({}))".format(
                    h, "|".join(["{:2d}".format(x).replace(" ", "0") for x in range(int(m), int(m) + 1)]))
            if(int(m) - 5 >= 0):
                f_h = int(h)
                f_m = int(m) - 5
                pattern_time = "({}:({}))".format(
                        h, "|".join(["{:2d}".format(x).replace(" ", "0") for x in range(f_m, int(m) + 1)]))
            else:
                f_m = int(m) - 5 + 60
                f_h = int(h) - 1
            if(f_h - 1 < 0):
                f_h = 23
                pattern_time = "({:2d}:({})|{}:({}))".format(
                    f_h,
                    "|".join(["{:2d}".format(x).replace(" ", "0") for x in range(f_m, 60)]),
                    h,
                    "|".join(["{:2d}".format(x).replace(" ", "0") for x in range(0, int(m) + 1)]))
            pattern = r"{} {} {}.*wft_log.*Resource temporarily unavailable".format(
                    get_time[1], get_time[2], pattern_time)
            get_logs = filter(None, check_output(['cat /var/log/system.log | tail -n 10'], shell=True).decode('utf-8').split('\n'))
            if(len(list(filter(None, [x for x in get_logs if re.match(pattern, x)]))) != 0):
                error_message.info("UNUSED_FILE_WFT: Reloading syslogd.")
                call(['service syslogd reload'], shell=True)
            time.sleep(300)
