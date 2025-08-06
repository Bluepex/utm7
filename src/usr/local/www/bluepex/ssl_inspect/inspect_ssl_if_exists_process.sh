if [ `ps aux | grep '/usr/local/sbin/netifyd' | grep -v grep -c` -gt 0 ]
then
    /bin/sh /usr/local/www/ssl_inspect/generate_file_inspect_ssl.sh 2>&1 &
    if [ `ps aux | grep nfa_main | grep -v grep -c` -eq 0 ]
    then
        /bin/sh /usr/local/www/ssl_inspect/netify_fwa.sh 2>&1 &
    fi
else
    kill -9 `ps aux | grep '/usr/local/var/run/netifyd/netifyd.sock' | grep -v grep | awk -F" " '{print $2}'`
    kill -9 `ps aux | grep 'nfa_main' | grep -v grep | awk -F" " '{print $2}'`
fi