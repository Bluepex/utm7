#!/usr/local/bin/python3.8
# -*- coding: UTF-8 -*-
#
#  Copyright (C) 2017 BluePex Security Company (R)
#  Silvio Giunge <desenvolvimento@bluepex.com>
#  All rights reserved.
#


import sys
import time
from threading import Thread
from webfilter import xmldoc
from webfilter import error_message
from subprocess import call, check_output, CalledProcessError


class check_gateway_status(Thread):

    def __init__(self):

        Thread.__init__(self)
        self.command = "ping -t 2 -c 1 -S {} {}"

        self.instances = ""
        if xmldoc['system']['webfilter']['instance']['info']['wf_instances']:
            self.instances = xmldoc['system']['webfilter']['instance']['info']['wf_instances']

        self.reconfigure_proxy = "/usr/local/sbin/squid -k reconfigure -f /usr/local/etc/squid/squid{}.conf"
        self.sed_check_lb_config = "sed -rn '/^(#?tcp_outgoing_address.*)$/p' /usr/local/etc/squid/squid{}.conf"
        self.sed_diable_balance = "sed -r -i.bak 's/^(tcp_outgoing_address)(.*)/#tcp_outgoing_address\\2/g' \
            /usr/local/etc/squid/squid.conf"
        self.sed_ifaces_on = "sed -r -i.bak 's/^(#?tcp_outgoing_address.*{}.*)$/tcp_outgoing_address {} {}/g' \
            /usr/local/etc/squid/squid{}.conf"
        self.sed_get_ifaces_off = "sed -r -i.bak 's/^(tcp_outgoing_address.*{}.*)$/#tcp_outgoing_address {} {}/g' \
            /usr/local/etc/squid/squid{}.conf"
        self.sed_get_ifaces_on = "sed -r -i.bak 's/^(#tcp_outgoing_address.*{}.*)$/tcp_outgoing_address {} {}/g' \
            /usr/local/etc/squid/squid{}.conf"
        self.sed_iface_master = "sed -r -i.bak 's/^(#?tcp_outgoing_address.*{}.*)$/tcp_outgoing_address {}/g' \
            /usr/local/etc/squid/squid{}.conf"

    def disable_load_balance(self, lb_instance):

        check_output([self.sed_diable_balance], shell=True)
        call([self.reconfigure_proxy.format(lb_instance)], shell=True)

    def get_lb_instances(self):

        _instances = []

        if self.instances:
            if len(self.instances.split("|")) == 1:
                if xmldoc['system']['webfilter']['instance']['config']['server']['enable_squid']:
                    if xmldoc['system']['webfilter']['instance']['config'].get('squidtraffic', None):
                        _instances.append(["{}:{}".format(
                            self.instances,
                            xmldoc['system']['webfilter']['instance']['config']['squidtraffic']['enable_load_balance']
                        ), xmldoc['system']['webfilter']['instance']['config']['squidtraffic']['lb_gateways']])
                    else:
                        for idx, ins in enumerate(self.instances.split("|")):
                            try:
                                if xmldoc['system']['webfilter']['instance']['config'][idx]['server']['enable_squid']:
                                    if xmldoc['system']['webfilter']['instance']['config'][idx]['squidtraffic']:
                                        _instances.append(["{}:{}".format(
                                            ins,
                                            xmldoc['system']['webfilter']['instance']['config'][idx]['squidtraffic']['enable_load_balance']
                                        ), xmldoc['system']['webfilter']['instance']['config'][idx]['squidtraffic']['lb_gateways']])
                            except:
                                    if xmldoc['system']['webfilter']['instance']['config']['server']['enable_squid']:
                                        if xmldoc['system']['webfilter']['instance']['config'].get('squidtraffic', None):
                                            _instances.append(["{}:{}".format(
                                                ins,
                                                xmldoc['system']['webfilter']['instance']['config']['squidtraffic']['enable_load_balance']
                                            ), xmldoc['system']['webfilter']['instance']['config']['squidtraffic']['lb_gateways']])

            return [_instance for _instance in _instances if _instance[1]]
        else:
            return []

    def write_squid_conf(self, w_gws):

        lb_config = check_output([self.sed_check_lb_config.format(w_gws[0]['instance'])],
                                 shell=True).rstrip().split('\n')
        if lb_config != '':
            get_off = sorted(set(['off' for x in w_gws if x['status'] == 'off']))
            if len(get_off) == 0:
                for item in w_gws:
                    check_output([self.sed_ifaces_on.format(item['ip'], item['ip'], item['gateway_name'],
                                                            w_gws[0]['instance'])], shell=True)
            else:
                get_iface_off = [item for item in w_gws if item['status'] == 'off']
                get_iface_on = [item for item in w_gws if item['status'] == 'on']
                for item in get_iface_off:
                    check_output([self.sed_get_ifaces_off.format(item['ip'], item['ip'], item['gateway_name'],
                                                                 w_gws[0]['instance'])], shell=True)
                for item in get_iface_on[:-1]:
                    check_output([self.sed_get_ifaces_on.format(item['ip'], item['ip'], item['gateway_name'],
                                                                w_gws[0]['instance'])], shell=True)
                check_output([self.sed_iface_master.format(get_iface_on[-1]['ip'], get_iface_on[-1]['ip'],
                                                           w_gws[0]['instance'])], shell=True)
            call([self.reconfigure_proxy.format(w_gws[0]['instance'])], shell=True)

    def run(self):

        self.lb_instances = self.get_lb_instances()
        if len(self.lb_instances) != 0:
            while True:
                try:
                    for lb in self.lb_instances:
                        _w_gws = []
                        for i, x in enumerate(lb[1]['item']):
                            _tmp_w_gws = {}
                            try:
                                self.ping = filter(None, check_output([
                                    self.command.format(
                                        lb[1]['item'][i]['ip'],
                                        lb[1]['item'][i]['monitor'])], shell=True).split('\n'))
                                _tmp_w_gws['instance'] = lb[0].split(":")[0]
                                _tmp_w_gws['status'] = "on"
                                _tmp_w_gws['ip'] = lb[1]['item'][i]['ip']
                                _tmp_w_gws['gateway_name'] = lb[1]['item'][i]['gateway_name']
                            except CalledProcessError:
                                _tmp_w_gws['instance'] = lb[0].split(":")[0]
                                _tmp_w_gws['status'] = "off"
                                _tmp_w_gws['ip'] = lb[1]['item'][i]['ip']
                                _tmp_w_gws['gateway_name'] = lb[1]['item'][i]['gateway_name']
                            _w_gws.append(_tmp_w_gws)
                        self.write_squid_conf(_w_gws)
                except:
                    error_message.info("Check Gateway Status: {}".format(sys.exc_info()[1]))
            time.sleep(5)
