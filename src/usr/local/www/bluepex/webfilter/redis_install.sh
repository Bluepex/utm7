dir_update="/usr/local/share/BluePexUTM/tmp_pack/update_6.0.0-RELEASE"
version=$(cat /etc/version)
dir="/etc/bkp"-"${version}"

# Install redis-server
pkg install -f -y redis

# Install redis python package
python3.8 -m pip install redis

# Stop redis-server
service redis stop

# Fetch and unzip dump.rdb redis-server database
/usr/bin/fetch http://wsutm.bluepex.com/lists/redis_urls_list.zip -o /tmp/redis_urls_list.zip > /dev/null
/usr/local/bin/7z x -o/var/db/redis/ -p478C7C98392F80D6854BF037BA7073C15D10E41DD65DA3D240C27EAD565E7A8D /tmp/redis_urls_list.zip -aoa > /dev/null

# Remove redis dump database tmp file
rm /tmp/redis_urls_list.zip

# Start redis-server
service redis onestart

