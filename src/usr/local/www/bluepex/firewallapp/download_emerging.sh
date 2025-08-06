#!/bin/sh

rules_dir=/usr/local/share/suricata/rules
tmp_dir=${rules_dir}/tmp

if [ -d ${tmp_dir} ]; then
	rm -rf ${tmp_dir}/*
else
	mkdir -p ${tmp_dir}
fi

trap "rm -rf ${tmp_dir}" 1 2 15 EXIT

suricata_version=$(pkg query %v suricata 2>/dev/null | sed 's/_.*//; s/,.*//')

if [ -z "${suricata_version}" ]; then
	echo "Suricata not found"
	exit 1
fi

base_url=http://rules.emergingthreats.net/open/suricata-${suricata_version}

fetch -o ${tmp_dir}/version.txt ${base_url}/version.txt

if [ $? -ne 0 ]; then
	echo "Error fetching version.txt"
	exit 1
fi

if [ -f ${rules_dir}/version.txt ]; then
	cur_version=$(cat ${rules_dir}/version.txt)
	new_version=$(cat ${tmp_dir}/version.txt)

	if [ "${cur_version}" = "${new_version}" ]; then
		echo "Nothing to do here"
		exit 0
	fi
fi

rules_tarball=${tmp_rules}/rules.tar.gz
fetch -o ${rules_tarball} ${base_url}/emerging.rules.tar.gz

if [ $? -ne 0 ]; then
	echo "Error fetching emergingthreats rules"
	exit 1
fi

if ! tar -C ${tmp_dir} -xzf ${rules_tarball}; then
	echo "Error uncompressing rules tarball"
	exit 1
fi

for f in ${tmp_dir}/rules/*.rules; do
	sed -i '' -E "/2024234|2002945|2015050|2010602|2002371|2014913|2015695|2019728/d" ${f}
	filename=$(basename ${f})
	mv ${f} ${rules_dir}/_${filename}
done

exit 0
