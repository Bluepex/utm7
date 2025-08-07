#!/usr/local/bin/python3.8
# -*- coding: utf-8 -*-

import subprocess
import sys

sys.path.append('/usr/local/etc/webfilter/')

from xml_to_dict import return_xml_dict

xmldoc = return_xml_dict().run()

log_referer = xmldoc['system']['webfilter']['nf_reports_settings']['element0']['log_referer']

def main():
	try:
		if log_referer == 'on':
                    output = subprocess.check_output(["/usr/bin/egrep -a -i '^([0-9]*\|https?:\/\/[a-z.]*" + str(sys.argv[1]) + "[a-z0-9=%?\/]*)' /var/db/bp_category/urls_list.txt | awk 'BEGIN {FS=OFS=\"|\"} {print $1}'| uniq"],universal_newlines=True,shell=True).rstrip().replace("\n", ",")
		else:
			output = subprocess.check_output(["/usr/bin/egrep -a -i '^([0-9]*\|https?:\/\/www.?" + str(sys.argv[1]) + ")/$' /var/db/bp_category/urls_list.txt | awk 'BEGIN {FS=OFS=\"|\"} {print $1}'| uniq"],universal_newlines=True,shell=True).rstrip().replace("\n", ",")

		if not output:
			output = '99'
		else:
			output = output
	except:
		output = '99'

	output += ","
	print(output)

main()
