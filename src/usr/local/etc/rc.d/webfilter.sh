#!/bin/sh
# BluePex WebFilter ckeck install and start the webfilter monitor
# 
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# <desenvolvimento@bluepex.com>, 2015
# ====================================================================
#

wf_info=$(/usr/local/sbin/read_xml_tag.sh string system/webfilter/info/version)

rc_start() {
	if [ "$wf_info" != "1.4.97" ]; then
		/usr/local/bin/php -f /usr/local/pkg/webfilter_post_install.php
	fi
	[ -f /usr/local/bin/wf_monitor.py ] && /usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py
	/usr/local/bin/php -f /usr/local/bin/update_content_lists.php &
	/etc/rc.d/devfs restart
}

rc_stop() {
	/bin/pkill -f wf_monitor
	/bin/pkill -f wfrotated
}

case $1 in
	start)
		rc_start
		;;
	restart)
		rc_stop
		rc_start
		;;
	stop)
		rc_stop
		;;
	*)
		rc_start
		;;
esac
