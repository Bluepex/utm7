#!/bin/sh
########
# This file was automatically generated
# by the pfSense service handler.
######## Start of main suricata.sh

rc_start() {
	### Make sure libraries path cache is up2date
	/etc/rc.d/ldconfig start

	### Lock out other start signals until we are done
	/usr/bin/touch /var/run/suricata_pkg_starting.lck
	
	## Start suricata on WAN (em0) ##
	if [ ! -f /var/run/suricata_em053201.pid ]; then
		pid=`/bin/pgrep -fn "suricata --netmap -D -c /usr/local/etc/suricata/suricata_53201_em0/suricata.yaml "`
	else
		pid=`/bin/pgrep -F /var/run/suricata_em053201.pid`
	fi

	if [ -z $pid ]; then
		#/bin/cp /dev/null /var/log/suricata/suricata_em053201/suricata.log
		#/usr/bin/logger -p daemon.info -i -t SuricataStartup "Suricata START for WAN(53201_em0)..."
		#/usr/local/bin/suricata --netmap -D -c /usr/local/etc/suricata/suricata_53201_em0/suricata.yaml --pidfile /var/run/suricata_em053201.pid  > /dev/null 2>&1
	fi

	sleep 1

	### Remove the lock since we have started all interfaces
	if [ -f /var/run/suricata_pkg_starting.lck ]; then
		/bin/rm /var/run/suricata_pkg_starting.lck
	fi
}

rc_stop() {
	
	if [ -f /var/run/suricata_em053201.pid ]; then
		pid=`/bin/pgrep -F /var/run/suricata_em053201.pid`
		/usr/bin/logger -p daemon.info -i -t SuricataStartup "Suricata STOP for WAN(53201_em0)..."
		/bin/pkill -TERM -F /var/run/suricata_em053201.pid
		time=0 timeout=30
		while /bin/kill -TERM $pid 2>/dev/null; do
			sleep 1
			time=$((time+1))
			if [ $time -gt $timeout ]; then
				break
			fi
		done
		if [ -f /var/run/suricata_em053201.pid ]; then
			/bin/rm /var/run/suricata_em053201.pid
		fi
	else
		pid=`/bin/pgrep -fn "suricata --netmap -D -c /usr/local/etc/suricata/suricata_53201_em0/suricata.yaml "`
		if [ ! -z $pid ]; then
			/usr/bin/logger -p daemon.info -i -t SuricataStartup "Suricata STOP for WAN(53201_em0)..."
			/bin/pkill -TERM -fn "suricata -i em0 "
			time=0 timeout=30
			while /bin/kill -TERM $pid 2>/dev/null; do
				sleep 1
				time=$((time+1))
				if [ $time -gt $timeout ]; then
					break
				fi
			done
		fi
	fi

	sleep 1
}

case $1 in
	start)
		if [ ! -f /var/run/suricata_pkg_starting.lck ]; then
			rc_start
		else
			/usr/bin/logger -p daemon.info -i -t SuricataStartup "Ignoring additional START command since Suricata is already starting..."
		fi
		;;
	stop)
		rc_stop
		;;
	restart)
		rc_stop
		sleep 5
		rc_start
		;;
esac
