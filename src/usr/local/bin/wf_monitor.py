#!/usr/local/bin/python3.8
# -*- encoding: UTF-8 -*-
#
#  Copyright (C) 2015-2022 BluePex Security Company (R)
#  Wesley Peres <desenvolvimento@bluepex.com>
#  All rights reserved.
#


import sys


sys.path.append('/usr/local/etc/webfilter')


import os
import time
from pwd import getpwnam
#from daemon import DaemonContext
#import daemon
from daemonize import Daemonize
from argparse import ArgumentParser
from webfilter import platform
from webfilter import send_mail
from webfilter import error_message
from webfilter import get_config
from webfilter import check_process
from webfilter import check_syslogd
from webfilter import check_var_folder
from webfilter import check_pipe_process
from webfilter import check_mysql_entry
from webfilter import check_mysql_connect
from webfilter import check_reverse_proxy_log
from webfilter import wf_instances, wf_instances_interface, wf_instances_interface_enabled
from check_gateway_status import check_gateway_status
from subprocess import check_output, run,  CalledProcessError, call
from check_unused_file import check_unused_file
from check_squid_process import check_squid_process


t_process = get_config('time_process')


def set_parser():

    parser = ArgumentParser()
    parser.add_argument(
        "-o", dest="output", action="store", default="files",
        help="Choose metod to output data.",
        choices=('file', 'socket')
    )
    parser.add_argument(
        "-c", dest="check", action="store_true", default=False,
        help="Check if services is running."
    )
    parser.add_argument(
        "-a", dest="all_services", action="store_true", default=False,
        help="Check is all services is runnig."
    )
    choice_services = ['wfrotate', 'mysql']
    if wf_instances != "":
        for i in wf_instances.split("|"):
            instance = i.split(":")
            for x in wf_instances_interface.split("|"):
                if x == i:
                    choice_services.append('interface_' + instance[1])
            choice_services.append('squid_' + instance[1])
    parser.add_argument(
        "-s", dest="l_service", action="store",
        help="Choice services to check.",
        choices=choice_services
    )
    return parser.parse_args()

list_services = {
   "wfrotate": ["wfrotate", "wfrotated", "service wfrotated restart"]
}

# Verify wfrotated service
result = run(['ps aux | grep wfrotated | grep -v grep -c'], shell=True, capture_output=True)
p = result.stdout.decode('utf-8')
if (int(p) == 0):
    os.system("service wfrotated restart")

if wf_instances != "":
    for i in wf_instances.split("|"):
        instance = i.split(":")
        if instance[1] in wf_instances_interface_enabled:
            if (instance[1]) != "":
                for x in wf_instances_interface.split("|"):
                    if x == i:
                        list_services["interface_" + instance[1]] = [
                            'interface_' + instance[1], 'interface' + instance[0] + '.pid',
                            '/usr/local/etc/rc.d/interface restart -i ' + instance[0]]

if platform != "nanobsd":
    list_services["mysql"] = ["mysql", "mysqld", "/usr/local/etc/rc.d/mysql-server restart"]


def main():
    # Check gateway status
    s_check_gateway_status = check_gateway_status()
    s_check_gateway_status.start()

    # Check WFT file in the syslog
    s_check_unused_file = check_unused_file()
    s_check_unused_file.start()

    # Check squid service
    s_squid_check = check_squid_process()
    s_squid_check.setDaemon(True)
    s_squid_check.start()

    # Check reverse proxy
    s_check_reverse_proxy_log = check_reverse_proxy_log()
    s_check_reverse_proxy_log.start()

    cont_check_entry = 0

    while True:
        for service in list_services:
	    # Check if services is up
            s_service = check_process(list_services[service])
            s_service.setDaemon(True)
            s_service.start()
            s_service.join()
            time.sleep(t_process)

	# Check syslogd service
        check_syslogd()
        time.sleep(t_process)

	# Check size var folder
        check_var_folder()
        time.sleep(t_process)

        if get_config('check_mysql_entry') == "on":
	    # Check mysql conection
            check_mysql_connect()
            time.sleep(t_process)
	    # Check last insert data in mysql
            if not check_mysql_entry():
                cont_check_entry = cont_check_entry + 1
                if cont_check_entry in range(1, 4):
		    # Send mail notificating mysql service down
                    send_mail()
            else:
                cont_check_entry = 0

if __name__ == "__main__":

    opts = set_parser()

    if(opts.check):
        if(opts.all_services):
            for service in list_services:
                if service == "mysql":
                    if platform != "nanobsd":
                        s_service = check_process(list_services[service])
                        s_service.start()
                        s_service.join()
                        time.sleep(t_process)
                    else:
                        check_var_folder()
                        time.sleep(t_process)
                else:
                    s_service = check_process(list_services[service])
                    s_service.start()
                    s_service.join()
                    time.sleep(t_process)
        elif(opts.l_service is not None):
            s_service = check_process(list_services[opts.l_service])
            s_service.start()
            s_service.join()
            time.sleep(t_process)
    else:
        if check_output(['/bin/pgrep -f wf_monitor; exit 0'], shell=True).decode('utf-8'):
            sys.exit(0)

        if not os.path.exists('/usr/local/etc/netfilter/rules0.conf'):
            rules = """#
# Regra 1
#
rule {
    all_allowed = true
}"""

            print(rules, end="\n", file=open('/usr/local/etc/netfilter/rules0.conf', 'w'))

        if not os.path.exists('/usr/local/etc/netfilter/groups.conf'):
            os.system('/usr/bin/touch /usr/local/etc/netfilter/groups.conf; exit 0')

        if not os.path.isdir('/var/run/squid'):
            os.mkdir('/var/run/squid', 0x755)
            os.chown('/var/run/squid', getpwnam('squid').pw_uid, getpwnam('squid').pw_gid)

        #comment to solutions in restart squid in 15 minuts - 29/08/2023
        #os.system('/bin/pkill -9 -af squid; exit 0')

        #with daemon.DaemonContext():
        pid = "/var/run/wfmonitor.pid"
        daemon = Daemonize(app="monitor", pid=pid, action=main)
        daemon.start()
