<?php
require("config.inc");
require_once("services.inc");
require_once("util.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("firewallapp_webservice.inc");

ini_set("memory_limit", "512M");

//simple___advanced
if (file_exists('/etc/stopFileGateway')) {
    if (file_exists('/etc/servicesStopFileGateway')) {
        $times = array_filter(explode("___", file_get_contents("/etc/stopFileGateway")));
    	$services = array_filter(explode("___", file_get_contents("/etc/servicesStopFileGateway")));
        if (in_array("advanced", $services)) {
            //same day
            if (intval(explode(":",$times[0])[0]) < intval(explode(":",$times[1])[0])) {
                $start = strtotime(date('Y-m-d' . $times[0]));
                $end = strtotime(date('Y-m-d' . $times[1]));
                $now = time();
                if ( $start <= $now && $now <= $end ) {
                    die;
                }
            }
            //Day and tomorrow
            if (intval(explode(":",$times[0])[0]) > intval(explode(":",$times[1])[0])) {
                $start = strtotime(date('Y-m-d ' . $times[0]));
                $end = strtotime(date('Y-m-d ' . $times[1]));
                $now = strtotime();
                if (intval(explode(":",$times[1])[0]) == 0) {
                    if ( $start <= $now && $now <= $end ) {
                        die;
                    }
                } else {
                    $end = strtotime(date('Y-m-d ' . $times[1]))+86400;
                    if ( $start <= $now && $now <= $end ) {
                        die;
                    }
                }
            }
        }
    }
}

//Don't continue process if exists is states
if (intval(getInterfaceNewAcp()) == 0) {
    file_put_contents("/etc/monitor_gateway_files_clamd", "false");
    die;
}

if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp')) {
    die;
}

if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp')) {
    die;
}

if (intval(trim(shell_exec("ps aux | grep fetch_different_files_clamav | grep -v grep | wc -l"))) > 1) {
    die;
}

if (intval(trim(shell_exec("ps aux | grep fetch_different_files_yara | grep -v grep | wc -l"))) > 0) {
    die;
}

if (intval(trim(shell_exec("ps aux | grep update_interfaces_hashs | grep -v grep | wc -l"))) > 0) {
    die;
}

if (intval(trim(shell_exec("ps aux | grep '/usr/local/bin/freshclam' | grep -v grep | wc -l"))) > 0) {
    die;
}

if (!isset($config['installedpackages']['suricata']['rule'])) {
    file_put_contents("/etc/monitor_gateway_files_clamd", "false");
    die;
}

if (count($config['installedpackages']['suricata']['rule']) == 0) {
    file_put_contents("/etc/monitor_gateway_files_clamd", "false");
    die;
}

$all_gtw = getInterfacesInGatewaysWithNoExceptions();
    
$interfacesIsRunning = false;
foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
    $if = get_real_interface($suricatacfg['interface']);
    $uuid = $suricatacfg['uuid'];
    if (in_array($if,$all_gtw,true)) {
        if (suricata_is_running($uuid, $if)) {
            $interfacesIsRunning = true;
            break;
        }
    }
}

if (!$interfacesIsRunning) {
    file_put_contents("/etc/monitor_gateway_files_clamd", "false");
    file_put_contents("/etc/monitor_gateway_files_yara", "false");
    die;
}

//Confirm interfaces
init_config_arr(array('installedpackages', 'suricata', 'rule'));

//If not exists more interfaces
if (intval(getInterfaceNewAcp()) == 0) {
    if (file_exists("/etc/monitor_gateway_files_clamd")) {
        if (trim(file_get_contents("/etc/monitor_gateway_files_clamd")) == "true") {
            file_put_contents("/etc/monitor_gateway_files_clamd", "false");
        }
    }
    if (file_exists("/var/log/clamav/clamd_custom.log")) {
        unlink("/var/log/clamav/clamd_custom.log");
        mwexec_bg("touch /var/log/clamav/clamd_custom.log");
    }
}

//Create a new file
if (!file_exists("/etc/monitor_gateway_files_clamd")) {
    file_put_contents("/etc/monitor_gateway_files_clamd", "false");
}

//Start
if (file_exists("/etc/monitor_gateway_files_clamd") && (trim(file_get_contents("/etc/monitor_gateway_files_clamd")) == "true")) {
    mwexec_bg("cp -f /usr/local/www/active_protection/av/clamd.conf /usr/local/etc/");
    mwexec_bg("cp -f /usr/local/www/active_protection/av/freshclam.conf /usr/local/etc/");
}

//Variables of create hash file
$start_construct_file_hashs = false;
if (!file_exists('/etc/list_hash_interfaces')) {
    $start_construct_file_hashs = true;
}

if (file_exists('/etc/list_hash_interfaces')) {
    if (date("d-m-Y") != date("d-m-Y", filemtime('/etc/list_hash_interfaces'))) {
        $start_construct_file_hashs = true; 
    }
}

//Confirm files exists
if (!file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt')) {
    mwexec_bg('touch /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt');
}

if (!file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt')) {
    mwexec_bg('touch /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt');
}

if (!file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt')) {
    mwexec_bg('touch /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt');
}

if (!file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt')) {
    mwexec_bg('touch /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt');
}

if (!file_exists('/etc/persistFindEve')) {
    mwexec_bg('touch /etc/persistFindEve');
}

if (!file_exists('/var/log/clamav/clamd_custom.log')) {
    mwexec_bg('touch /var/log/clamav/clamd_custom.log');
}

if (!file_exists('/var/log/yara_work.log')) {
    mwexec_bg('touch /var/log/yara_work.log');
}

//Create a hash file of values in file
$unique_sha256_hashs = [];
if ($start_construct_file_hashs) {
    if (file_exists('/etc/list_hash_interfaces')) {
        unlink('/etc/list_hash_interfaces');
    }
    $files_read_hash = [];
    foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
        if ($suricatacfg['enable'] == "on") {						
            $if = get_real_interface($suricatacfg['interface']);
            $uudi = $suricatacfg['uuid'];
            if (in_array($if,$all_gtw,true)) {
                if (suricata_is_running($uudi, $if)) {
                    foreach (explode("\n", shell_exec("find /usr/local/etc/suricata/suricata_{$uudi}_{$if}/rules/ | grep 256")) as $files_now) {
                        if (!is_dir($files_now)) {
                            $files_read_hash[] = $files_now;
                        }
                    }
                }
            }
        }
    }
    foreach($files_read_hash as $files_now) {
        foreach(explode("\n", file_get_contents($files_now)) as $hashs_now) {
            file_put_contents("/etc/list_hash_interfaces", "{$hashs_now}\n", FILE_APPEND);
            $unique_sha256_hashs[] = $hashs_now;
        }
    }
    unset($files_read_hash);
}

//If not exists db files clamav
$startDownloadFresh = false;
if (!is_dir('/var/db/clamav/')) {
    $startDownloadFresh = true;
} else {
    if (count(scandir("/var/db/clamav")) < 3) {
        $startDownloadFresh = true;
    }
}

if ($startDownloadFresh) {
    mwexec("/usr/local/bin/freshclam");
}

if (file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "true") {

    if (count($unique_sha256_hashs) == 0) {
        if (file_exists('/etc/list_hash_interfaces')) {
            foreach(explode("\n", file_get_contents("/etc/list_hash_interfaces")) as $line_sha256) {
                $unique_sha256_hashs[] = $line_sha256;
            }
        }
    }
    
    $unique_sha256_hashs = array_filter(array_unique($unique_sha256_hashs));

    //Clear old file operations clamscan
    if (file_exists("/tmp/find_threads_use")) {
        unlink("/tmp/find_threads_use");
    }

    //Get eve infofiles
    if (file_exists("/tmp/work_eve")) {
        unlink("/tmp/work_eve");
    }

    //Get hash files
    $all_values_now = [];
    foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
        if ($suricatacfg['enable'] == "on") {						
            $if = get_real_interface($suricatacfg['interface']);
            $uuid = $suricatacfg['uuid'];
            if (in_array($if,$all_gtw,true)) {
                if (suricata_is_running($uuid, $if)) {
                    foreach (explode("\n", shell_exec("find /var/log/suricata/suricata_{$if}{$uuid}/filestore/ -type f | grep -v '.json' | grep -v '/tmp/'")) as $files_now) {
                        if(!array_search(end(explode("/", $files_now)), $unique_sha256_hashs)) {
                            file_put_contents("/tmp/find_threads_use", "{$files_now}\n", FILE_APPEND);
                        }
                    }
                }
                mwexec("tail -n10000 /var/log/suricata/suricata_{$if}{$uuid}/eve.json | grep fileinfo | grep sha256 | grep -v 'tx_id\":0' >> /tmp/work_eve");
                #mwexec("/usr/local/bin/redis-cli lrange suricata{$if}{$uuid} -10000 -1 >> /tmp/work_eve");
            }
        }
    }

    //Get alreay exists rules
    $values_blacklist_file = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt'))));
    $values_whitelist_file = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt'))));
    $values_clamd_work = array_filter(array_unique(explode("\n", file_get_contents('/tmp/find_threads_use'))));
    
    //Delete old file
    if (file_exists('/tmp/find_threads_use')) {
        unlink('/tmp/find_threads_use');
    }
    if (file_exists('/tmp/find_threads_use_black')) {
        unlink('/tmp/find_threads_use_black');
    }
    if (file_exists('/tmp/find_threads_use_white')) {
        unlink('/tmp/find_threads_use_white');
    }

    //Values for work
    foreach($values_clamd_work as $values_clamd_work_now) {
        $values_clamd_work_now_line = $values_clamd_work_now;
        $values_clamd_work_now = end(explode("/", $values_clamd_work_now));
        if (!in_array($values_clamd_work_now, $values_whitelist_file)) {
            if (!in_array($values_clamd_work_now, $values_blacklist_file)) {
                if (file_exists($values_clamd_work_now_line)) {
                    file_put_contents("/tmp/find_threads_use", "{$values_clamd_work_now_line}\n", FILE_APPEND);
                }
            }
        }
    }
    unset($values_blacklist_file);
    unset($values_whitelist_file);
    unset($values_clamd_work);

    //Generated the tmp file for python to work with it and generate the already reliable clamd lists and whitelist lists
    if (file_exists("/tmp/find_threads_use") && strlen(file_get_contents("/tmp/find_threads_use")) > 0) {

        mwexec("clamscan --no-summary --file-list=/tmp/find_threads_use --log=/var/log/clamav/clamd_custom.log");
        
        //Get values log for blacklist
        if (file_exists("/tmp/clamd_work")) {
            unlink("/tmp/clamd_work");
        }
        mwexec("tail -n10000 /var/log/clamav/clamd_custom.log > /tmp/clamd_work");

        //Get FOUND clamd
        $values_blacklist = [];
        if (file_exists("/var/log/clamav/clamd_custom.log")) {
            foreach (array_filter(explode("\n", shell_exec("grep ' FOUND' /tmp/clamd_work | awk -F\": \" '{ print $1 }' | grep '/var/log' | grep -v tmp"))) as $line_work) {
                if (is_file($line_work) && file_exists($line_work)) {
                    $values_blacklist[] = end(explode("/", $line_work));
                }
            }
        }
        //White list lookup
        if (file_exists("/tmp/find_threads_use_black")) {
            foreach (array_filter(explode("\n", file_get_contents("/tmp/find_threads_use_black"))) as $line_work) {
                if (is_file($line_work) && file_exists($line_work)) {
                    $values_blacklist[] = end(explode("/", $line_work));
                }
            }
            unlink("/tmp/find_threads_use_black");
        }
        
        //Get OK clamd
        $values_whitelist = [];
        if (file_exists("/var/log/clamav/clamd_custom.log")) {
            foreach (array_filter(explode("\n", shell_exec("grep ' OK' /tmp/clamd_work | awk -F\": \" '{ print $1 }' | grep '/var/log' | grep -v tmp"))) as $line_work) {
                if (is_file($line_work) && file_exists($line_work)) {
                    $values_whitelist[] = end(explode("/", $line_work));
                }
            }
        }

        //White list lookup
        if (file_exists("/tmp/find_threads_use_white")) {
            foreach (array_filter(explode("\n", file_get_contents("/tmp/find_threads_use_white"))) as $line_work) {
                if (is_file($line_work) && file_exists($line_work)) {
                    $values_whitelist[] = end(explode("/", $line_work));
                }
            }
            unlink("/tmp/find_threads_use_white");
        }

        //Unique values
        $values_blacklist = array_filter(array_unique($values_blacklist));    
        $values_whitelist = array_filter(array_unique($values_whitelist));    

        //Create a blacklist clean
        //-------------------------------------------------------------------------
        foreach ($values_blacklist as $values_blacklist_now) {
            file_put_contents("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt", "\n{$values_blacklist_now}\n", FILE_APPEND);
        }
        //-------------------------------------------------------------------------

        //Create a white clean
        //-------------------------------------------------------------------------
        foreach ($values_whitelist as $values_whitelist_now) {
            file_put_contents("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt", "\n{$values_whitelist_now}\n", FILE_APPEND);
        }
        //-------------------------------------------------------------------------

        //Clean values variables lists
        unset($values_blacklist);
        unset($values_whitelist);

        //Clean files
        mwexec("uniq /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt.tmp"); 
        mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt");

        mwexec("uniq /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt.tmp");
        mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt");
        
        mwexec("uniq /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp");
        mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt");

        mwexec("uniq /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp");
        mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt");

        //Bring the updated values
        $values_blacklist_file_custom = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt'))));
        $values_whitelist_file_custom = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt'))));
        $values_blacklist_file = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt'))));
        $values_whitelist_file = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt'))));

        //Create a black clean - merge
        //-------------------------------------------------------------------------
        foreach ($values_blacklist_file_custom as $values_blacklist_now) {
            if (!in_array($values_blacklist_now, $values_whitelist_file)) {
                if (!in_array($values_blacklist_now, $values_blacklist_file)) {
                    file_put_contents("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt", "{$values_blacklist_now}\n", FILE_APPEND);
                }
            }
        }
        //-------------------------------------------------------------------------

        //Create a black clean - merge
        //-------------------------------------------------------------------------
        foreach ($values_whitelist_file_custom as $values_whitelist_now) {
            if (!in_array($values_whitelist_now, $values_blacklist_file)) {
                if (!in_array($values_whitelist_now, $values_whitelist_file)) {
                    file_put_contents("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt", "{$values_whitelist_now}\n", FILE_APPEND);
                }
            }
        }
        //-------------------------------------------------------------------------

        //Clear variables
        unset($values_blacklist_file_custom);
        unset($values_whitelist_file_custom);
        unset($values_blacklist_file);
        unset($values_whitelist_file);

        mwexec("uniq /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp");
        mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt");
        
        mwexec("uniq /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp");
        mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt");

        mwexec("cp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt");
        mwexec("cp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt");
    
        mwexec_bg("sh /usr/local/www/active_protection/av/generatePersistentEve.sh");

    }

}