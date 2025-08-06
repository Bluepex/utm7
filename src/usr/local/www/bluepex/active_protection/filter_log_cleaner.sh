if [ -e /var/log/filter.log ]
then
    clog /var/log/filter.log > /tmp/filter_log_tmp
    echo '' > /tmp/filter_log_tmp_return
    
    for ipLine in `awk -F"," '{print $19}' /tmp/filter_log_tmp | egrep -v '^[a-zA-Z]' | sort | uniq`
    do
        grep -e ",$ipLine," /tmp/filter_log_tmp | head -n1 >> /tmp/filter_log_tmp_return
    done
    
    for ipLine in `awk -F"," '{print $20}' /tmp/filter_log_tmp | egrep -v '^[a-zA-Z]' | sort | uniq`
    do
        grep -e ",$ipLine," /tmp/filter_log_tmp | head -n1 >> /tmp/filter_log_tmp_return
    done
    
    for ipLine in `awk -F"," '{print $16}' /tmp/filter_log_tmp | grep ':' | sort | uniq`
    do
        grep -e ",$ipLine," /tmp/filter_log_tmp | head -n1 >> /tmp/filter_log_tmp_return
    done

    for ipLine in `awk -F"," '{print $17}' /tmp/filter_log_tmp | grep ':' | sort | uniq`
    do
        grep -e ",$ipLine," /tmp/filter_log_tmp | head -n1 >> /tmp/filter_log_tmp_return
    done

    cat /tmp/filter_log_tmp_return | sort | uniq  > /tmp/filter_log_tmp_return.tmp
    mv /tmp/filter_log_tmp_return.tmp /tmp/filter_log_tmp_return

fi