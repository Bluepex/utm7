#!/usr/local/bin/php -f
<?php

/*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */

require_once('pfsense-utils.inc');
require_once('nf_defines.inc');
require_once('squid.inc');

global $g;

/*
conf_mount_rw();
@unlink_if_exists(NETFILTER_CONTENT_LISTS_READY_FILE);
@unlink_if_exists(NETFILTER_CONTENT_LISTS_ERROR_FILE);
touch(NETFILTER_CONTENT_LISTS_IN_PROGRESS_FILE);

$d = NETFILTER_LISTS_DIR;
if ( !is_dir( $d ) )
    safe_mkdir($d);

$res = 0;
$out = array();
exec("/usr/local/bin/rsync --size-only --progress rsync://updates.bluepex.com/wfcategories/* $d", $out, $res);

if ($res == 0) {
    exec('/usr/local/etc/rc.d/interface reload_lists');
    $config = parse_config();
    $nf_config = get_element_config('nf_content_settings');
    $nf_config['enable'] = 'on';
    set_element_config('nf_content_settings', 0, $nf_config);
    write_config('Reloaded lists in the interface');
    squid_resync();
    touch(NETFILTER_CONTENT_LISTS_READY_FILE);
}
else {
    touch(NETFILTER_CONTENT_LISTS_ERROR_FILE);
}
@unlink_if_exists(NETFILTER_CONTENT_LISTS_IN_PROGRESS_FILE);

file_put_contents('/tmp/wfcategories_rsync.log', implode("\n", $out));
conf_mount_ro();
*/

// Resolvendo problema do rsync travar no meio do processo
/*
if (!is_dir(NETFILTER_LISTS_DIR)) {
        mkdir(NETFILTER_LISTS_DIR, 0755, true);
} else {
        mwexec("rm -rf /usr/local/etc/netfilter");
        mkdir(NETFILTER_LISTS_DIR, 0755, true);
}
*/

try {
        download_file("http://updates.bluepex.com/webfilter/categories.tbz", "/usr/local/pkg/categories.tbz");

        mwexec("tar -C " . NETFILTER_LISTS_DIR . " -xzf /usr/local/pkg/categories.tbz");

        unlink(NETFILTER_LISTS_DIR . "/block_PORNAMOROO.key");

        //mwexec("pkill -9 -af 'wf|squid|interface|redirector|mysql|nc'");
        mwexec("/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py");
} catch (Exception $e) {
        print("Falha ao tentar baixar a base de dados da netfilter");
}

?>
