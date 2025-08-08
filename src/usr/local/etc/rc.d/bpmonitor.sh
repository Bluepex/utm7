#!/bin/sh
# BluePexUTM SIM Agent
# 
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# <desenvolvimento@bluepex.com>, 2017
# ====================================================================
#


rc_start() {
	/usr/local/bin/python3.8 /usr/local/bin/bp_monitor_agent
}

rc_stop() {
	pkill -f bp_monitor_agent >/dev/null 2>&1
	time=0
	while pgrep -q -f bp_monitor_agent && test $time -gt 30; do
		pkill -f bp_monitor_agent >/dev/null 2>&1
		sleep 1
		time=$((time+1))
	done
}

case $1 in
	start)
		rc_start
		;;
	stop)
		rc_stop
		;;
	restart)
		rc_stop
		rc_start
		;;
esac
