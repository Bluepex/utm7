tail -n${2} /tmp/inspect_ssl | grep "\"interface\":\"${1}" | jq -r -f /usr/local/share/netifyd/json-socket-filter.jq | sort -r > /tmp/filterNet.tmp
cp /tmp/filterNet.tmp /tmp/filterNet