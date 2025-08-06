#!/bin/sh

dmesg | sed -E '/^arp: .*moved /!d; s/^arp: ([^ ]*) moved from ([^ ]*) to ([^ ]*) on (.*)/\1|\2|\3|\4/' | sort | uniq > /tmp/hosts_ip.txt
dmesg | sed -E '/^arp: .*using /!d; s/^arp: ([^ ]*) is using my IP address ([^ ]*) on (.*)/\1|\2|\3/' | sort | uniq > /tmp/utm_ip.txt
pciconf -lv > /tmp/vendor.txt
confirm_module=`/sbin/kldstat | /usr/bin/grep coretemp | /usr/bin/awk -F" " '{ print $5}' | /usr/bin/awk -F. '{ print $1 }'`
if [ "coretemp" != "$confirm_module" ]
then
    kldload coretemp
fi
sysctl -a | grep temperature > /tmp/coretemp.txt
/usr/sbin/arp -an | wc -l | sed 's/[[:space:]]//g' > /tmp/arp_hosts