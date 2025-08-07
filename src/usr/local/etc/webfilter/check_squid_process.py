#!/usr/local/bin/python3.8
# -*- coding: UTF-8 -*-
#
#  Copyright (C) 2017 BluePex Security Company (R)
#  Silvio Giunge <desenvolvimento@bluepex.com>
#  All rights reserved.
#


import sys
import time
from threading import Thread, Timer
from subprocess import call, check_output, CalledProcessError, Popen
from webfilter import error_message, wf_instances, xmldoc, log_process, status_services

class check_squid_process(Thread):

    global status_services
    global log_process

    def kill(self, p):
        try:
            p.kill()
        except OSError:
            pass # ignore

    def __init__(self):
        Thread.__init__(self)
        self.squid_cmd_check = "/usr/local/sbin/squid -k check -f /usr/local/etc/squid/squid{}.conf"
        self.squid_cmd_start = "/usr/local/sbin/squid -f /usr/local/etc/squid/squid{}.conf"
        self.smb_rc_cmd = "/usr/local/etc/rc.d/samba_server {}"
        self.ntlm_cmd = "/usr/local/bin/ntlm_auth --username='{}' --password='{}'"
        self.kinit_cmd = "echo '{}' | /usr/local/bin/kinit '{}'@'{}'"
        self.ads_cmd = "/usr/local/bin/net rpc testjoin"
        self.ads_join_cmd = "/usr/local/bin/net ads join -U {}%{}"
        self.klist_cmd = "/usr/bin/klist -c /tmp/krb5cc_0"
        self.num_squid_error_init = 0
        self.num_squid_error_now = 0
        self.status_instance = ""

    def squid_check(self, _ins):
        try:
            if check_output(["/bin/pgrep -l -f /usr/local/sbin/squid;exit 0"], shell=True).decode('utf-8') == "":
                self.status_instance = "off"
                check_output([self.squid_cmd_start.format(_ins)], shell=True)
                time.sleep(2)
                if check_output(["/bin/pgrep -l -f /usr/local/sbin/squid;exit 0"], shell=True).decode('utf-8') != "":
                    self.status_instance = "ok"
            else:
                self.status_instance = "ok"
        except CalledProcessError:
            self.status_instance = "off"
            call([self.squid_cmd_start.format(_ins)], shell=True)
            error_message.info("Squid is not running! [{}]".format(sys.exc_info()[1]))

    def check_ntlm_auth(self, _auth):
        try:
            check_output([self.ntlm_cmd.format(_auth['ntlm_user'], _auth['ntlm_password'])], shell=True)
            self.status_instance += ",ntlm_auth,ok"
        except CalledProcessError:
            self.status_instance += ",ntlm_auth,off"
            call([self.smb_rc_cmd.format("restart")], shell=True)
            error_message.info("NTLM Auth is not OK! [{}]".format(sys.exc_info()[1]))

    def check_ads_join(self, _auth):
        try:
            p = Popen([self.ads_cmd], shell=True)
            t = Timer(2, self.kill, [p])
            t.start()
            p.wait()
            t.cancel()
            self.status_instance += ",ads_join,ok"
        except CalledProcessError:
            self.status_instance += ",ads_join,off"
            call([self.ads_join_cmd.format(_auth['ntlm_user'], _auth['ntlm_password'])], shell=True)
            call([self.smb_rc_cmd.format("restart")], shell=True)
            error_message.info("Join on ADS Server is not OK! [{}]".format(sys.exc_info()[1]))

    def check_ticket_klist(self, _auth):
        try:
            p = Popen([self.klist_cmd], shell=True)
            t = Timer(2, self.kill, [p])
            t.start()
            p.wait()
            t.cancel()
            self.status_instance += ",kinit_ticket,ok"
        except CalledProcessError:
            call([self.kinit_cmd.format(_auth['ntlm_password'], _auth['ntlm_user'], _auth['auth_ntdomain'])], shell=True)
            self.status_instance += ",kinit_ticket,off"
            error_message.info("No ticket created [{}]".format(sys.exc_info()[1]))

    def return_auth_settings(self, _ins):
        try:
            return xmldoc['system']['webfilter']['instance']['config'][int(_ins)]['server']['authsettings']
        except:
            try:
                return xmldoc['system']['webfilter']['instance']['config']['server']['authsettings']
            except:
                pass

    def return_enabled_instance(self, _ins):
        try:
            return xmldoc['system']['webfilter']['instance']['config'][int(_ins)]['server']['enable_squid']
        except:
            return xmldoc['system']['webfilter']['instance']['config']['server']['enable_squid']
    
    def restart_devfs(self):
        if check_output(["sed -nr '/^own.*pf.*root:squid/,/^perm.*pf.*0640/p' /etc/devfs.conf"], shell=True) == "":
            print >> open("/etc/devfs.conf", "a"), "\nown\tpf\troot:squid\nperm\tpf\t0640\n"
        call(["/etc/rc.d/devfs restart"], shell=True)

    def check_transparent_proxy(self, _ins):
        try:
            if xmldoc['system']['webfilter']['instance']['config'][int(_ins)]['server']['transparent_proxy'] == "on":
                self.restart_devfs
        except:
            if xmldoc['system']['webfilter']['instance']['config']['server']['transparent_proxy'] == "on":
                self.restart_devfs

    def check_single_sign_on(self, i):
        _i = int(i.split(":")[0])
        authmode_type = xmldoc['system']['webgui'].get('authmode', None)
        try: 
            ntlm_enable = xmldoc['system']['webfilter']['instance']['config']['server']['authsettings']['auth_method']
        except Exception:
            ntlm_enable = xmldoc['system']['webfilter']['instance']['config'][_i]['server']['authsettings']['auth_method']
        if authmode_type != "Local Database" and ntlm_enable == 'ntlm':
            try:
                ip_ntp_server = xmldoc['system']['webfilter']['instance']['config']['server']['authsettings']['auth_server']
            except Exception:
                ip_ntp_server = xmldoc['system']['webfilter']['instance']['config'][_i]['server']['authsettings']['auth_server']

            call(["ntpdate -u {}".format(ip_ntp_server)], shell=True)

            call(["/etc/rc.squid_resync_check"], shell=True)

    def run(self):
        if wf_instances:
            self.num_squid_error_init = check_output("cat /var/squid/logs/cache0.log | grep -i \"Service Name: squid\"  | wc -l", shell=True).decode("utf-8")

            while True:
                self.num_squid_error_now = check_output("cat /var/squid/logs/cache0.log | grep -i \"Service Name: squid\" | wc -l", shell=True).decode("utf-8")

                for i in wf_instances.split("|"):
                    _i = i.split(":")[0]
                    self.check_transparent_proxy(_i)
                    if self.return_enabled_instance(_i):
                        self.squid_check(_i)
                        _auth_settings = self.return_auth_settings(_i)
                        if _auth_settings and _auth_settings['auth_method'] == "ntlm":
                            self.check_ntlm_auth(_auth_settings)
                            self.check_ticket_klist(_auth_settings)
                            self.check_ads_join(_auth_settings)

                    if int(self.num_squid_error_now) > int(self.num_squid_error_init):
                        self.check_single_sign_on(i)
                        self.num_squid_error_init = self.num_squid_error_now

                    status_services["squid_{}".format(i.split(":")[1])] = self.status_instance
                    log_process()

                time.sleep(25)
