if [ -e /tmp/work_eve ]
then
   while read -r line;
   do
      grep -r $line /tmp/work_eve >> /etc/persistFindEve
   done < /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt

   while read -r line;
   do
      grep -r $line /tmp/work_eve >> /etc/persistFindEve
   done < /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt
   #uniq /etc/persistFindEve > /etc/persistFindEve.tmp && mv /etc/persistFindEve.tmp /etc/persistFindEve
fi