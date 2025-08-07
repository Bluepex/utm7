<?php
/*
 * netify-fwa_defs.inc
 *
 * Netify FWA for pfSense
 * Copyright (C) 2015-2019 eGloo Incorporated
 *
 * This program comes with ABSOLUTELY NO WARRANTY.
 *
 * This is free software, and you are welcome to redistribute it
 * under certain conditions according to the GNU General Public
 * License version 3, or (at your option) any later version.
 */

if (! defined('NETIFY_FWA_PREFIX'))
    define('NETIFY_FWA_PREFIX', '/usr/local/');

if (! defined('NETIFY_FWA_SBINDIR'))
    define('NETIFY_FWA_SBINDIR', NETIFY_FWA_PREFIX . 'sbin/');

if (! defined('NETIFY_FWA_CONFDIR'))
    define('NETIFY_FWA_CONFDIR', NETIFY_FWA_PREFIX . 'etc/netify-fwa/');

if (! defined('NETIFY_FWA_VOLATILE_STATEDIR')) {
    define('NETIFY_FWA_VOLATILE_STATEDIR', NETIFY_FWA_PREFIX . '/var/run/netify-fwa/');
}

if (! defined('NETIFY_FWA_VERSION')) {
    $version = exec_command(
        NETIFY_FWA_SBINDIR . 'netify-fwa --version 2>&1 | cut -dv -f2'
    );
    define('NETIFY_FWA_VERSION', $version);
}

if (! define('NETIFY_FWA_JSON_RULES'))
    define('NETIFY_FWA_JSON_RULES', NETIFY_FWA_CONFDIR . 'netify-fwa.json');

if (! define('NETIFY_FWA_JSON_APP_PROTO_DATA'))
    define('NETIFY_FWA_JSON_APP_PROTO_DATA', NETIFY_FWA_CONFDIR . 'app-proto-data.json');

if (! define('NETIFY_FWA_JSON_STATUS'))
    define('NETIFY_FWA_JSON_STATUS', NETIFY_FWA_VOLATILE_STATEDIR . 'status.json');

if (! define('NETIFY_FWA_JSON_STATUS_MATCHES')) {
    #define('NETIFY_FWA_JSON_STATUS_MATCHES', NETIFY_FWA_VOLATILE_STATEDIR . 'matches.json');
    define('NETIFY_FWA_JSON_STATUS_MATCHES', '/usr/local/etc/netify-fwa/matches.json');
}

if (! define('NETIFY_FWA_WWWDIR'))
    define('NETIFY_FWA_WWWDIR', NETIFY_FWA_PREFIX . 'www/netify-fwa/');

if (! define('NETIFY_FWA_WWW_ICONS'))
    define('NETIFY_FWA_WWW_ICONS', NETIFY_FWA_WWWDIR. 'icons/');
?>
