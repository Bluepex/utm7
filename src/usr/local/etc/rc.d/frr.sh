#!/bin/sh
# This file was automatically generated
# by the BluePexUTM service handler.

rc_start() {
	/bin/mkdir -p /var/run/frr
	/bin/mkdir -p /var/log/frr

	if [ -s /var/etc/frr/frr_md5pw_del.conf ]; then
		/sbin/setkey -f /var/etc/frr/frr_md5pw_del.conf
	fi

	if [ -s /var/etc/frr/frr_md5pw_add.conf ]; then
		/sbin/setkey -f /var/etc/frr/frr_md5pw_add.conf
	fi

	/usr/sbin/chown -R frr:frr /var/etc/frr
	/usr/sbin/chown -R frr:frr /var/run/frr
	/usr/sbin/chown -R frr:frr /var/log/frr
	
	# Perform configuration check
	echo "Performing intergrated config test"
	/usr/local/bin/vtysh -C

	# Start Daemons
	echo "Starting FRR"
	/usr/local/etc/rc.d/frr start

	# Start watchfrr
	echo "Starting watchfrr"
	/usr/local/sbin/watchfrr -d -r /usr/local/etc/rc.d/frrbBrestartbB%s -s /usr/local/etc/rc.d/frrbBstartbB%s -k /usr/local/etc/rc.d/frrbBstopbB%s -b bB -t 30 zebra staticd bgpd
}

rc_stop() {
	echo "Stopping FRR"
	/usr/local/etc/rc.d/frr stop

	if [ -s /var/etc/frr/frr_md5pw_del.conf ]; then
		/sbin/setkey -f /var/etc/frr/frr_md5pw_del.conf
	fi

	if [ -e /var/run/frr/\watchfrr.pid ]; then
	echo "Stopping watchfrr"
	/bin/pkill -F /var/run/frr/watchfrr.pid
	/bin/rm -f /var/run/frr/watchfrr.pid
	fi
}

rc_restart() {
	rc_stop
	rc_start

}

case $1 in
	start)
		rc_start
		;;
	stop)
		rc_stop
		;;
	restart)
		rc_restart
		;;
esac

