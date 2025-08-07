<?php
/* $Id$ */
/*
	squid.inc
	Copyright (C) 2006-2009 Scott Ullrich
	Copyright (C) 2006 Fernando Lemos
	Copyright (C) 2012 Martin Fuchs
	Copyright (C) 2012-2014 Marcello Coutinho
	Copyright (C) 2013 Gekkenhuis
	Copyright (C) 2014 Silvio Giunge
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

require_once('globals.inc');
require_once('config.inc');
require_once('util.inc');
require_once("bluepex/util.inc");
require_once('system.inc');
require_once('pfsense-utils.inc');
require_once('pkg-utils.inc');
require_once('service-utils.inc');
require_once('services.inc');
require_once('interfaces.inc');
require_once('extensions.inc');
require_once('cg2_util.inc');
require_once("nf_defines.inc");

if (!function_exists("filter_configure"))
	require_once("filter.inc");

define('SQUID_BASE', '/usr/local');
define('SQUID_LOCALBASE', '/usr/local');
define('SQUID_UID', 'squid');
define('SQUID_GID', 'squid');
define('SQUID_EXTRA_GID', 'squid');

define('SQUID_CONFBASE', SQUID_LOCALBASE .'/etc/squid');
define('SQUID_CONFFILE', SQUID_CONFBASE . '/squid0.conf');
define('SQUID_ACLDIR', '/var/squid/acl');
define('SQUID_PASSWD', '/var/etc/squid.passwd');
define('SQUID_SSL_DB','/var/squid/lib/ssl_db');

$valid_acls = array();

$uname=posix_uname();
if ($uname['machine']=='amd64')
	ini_set('memory_limit', '250M');

/* Squid reverse proxy */
require_once('/usr/local/pkg/squid_reverse.inc');
/* Squid javascript helpers */
require_once('/usr/local/pkg/squid_js.inc');
/* Squid antivirus intergration features helpers */
require_once('/usr/local/pkg/squid_antivirus.inc');

if (!isset($config['system']['webfilter']['instance']) || !is_array($config['system']['webfilter']['instance'])) {
	$config['system']['webfilter']['instance'] = array();
}

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instances = &$config['system']['webfilter']['instance']['config'];

/*
 * Utility functions
 */

/* Handle base64 encoding and linebreaks in textarea configuration fields */
function sq_text_area_decode($text) {
	return preg_replace('/\r\n/', "\n",base64_decode($text));
}

/* Get interface IP and netmask for Squid interfaces */
function squid_get_real_interface_address($iface) {
	if (!function_exists("get_interface_ip"))
		require_once("interfaces.inc");

	return array(get_interface_ip($iface), gen_subnet_mask(get_interface_subnet($iface)));
}

/* Check whether ACL is valid */
function squid_is_valid_acl($acl) {
	global $valid_acls;

	if (!is_array($valid_acls))
		return;

	return in_array($acl, $valid_acls);
}

/* Recursively change ownership of directories */
function squid_chown_recursive($dir, $user, $group) {
	if (empty($dir) || ($dir == '/') || ($dir == '/usr/local') || !is_dir($dir)) {
		log_error(dgettext("BluePexWebFilter", "[squid] Attempted to chown an invalid directory: '{$dir}'"));
		return;
	}

	chown($dir, $user);
	chgrp($dir, $group);
	$handle = opendir($dir) ;
	while (($item = readdir($handle)) !== false) {
		if (($item != ".") && ($item != "..")) {
			$path = "$dir/$item";
			// Recurse unless it's the cache dir, that is slow and rarely necessary.
			if (is_dir($path) && (basename($dir) != "cache")) {
				squid_chown_recursive($path, $user, $group);
			} elseif (is_file($path)) {
				chown($path, $user);
				chgrp($path, $group);
			}
		}
	}
}

function check_antivirus_enabled() {
	global $config, $wf_instances;

	if (!isset($config['system']['webfilter']['squidantivirus']['config'][0]['enable']) ||
		$config['system']['webfilter']['squidantivirus']['config'][0]['enable'] != "on") {
		return false;
	}
	$av_enabled_instances = explode(",", $config['system']['webfilter']['squidantivirus']['config'][0]['instances']);
	foreach ($wf_instances as $instance_id => $instance_config) {
		if (squid_enabled($instance_id) && in_array($instance_id, $av_enabled_instances)) {
			return true;
		}
	}
	return false;
}

/* Check whether Squid is enabled */
function squid_enabled($instance_id) {
	global $config, $wf_instances;

	$settings = $wf_instances[$instance_id]['server'];

	// check whether Squid is enabled ...
	if ($settings['enable_squid'] == "on") {
		// ... and has at least one interface configured ...
		if (!empty($settings['active_interface'])) {
			return true;
		// ... or whether Squid reverse proxy is enabled
		} elseif (squid_reverse_enabled()) {
			return true;
		}
	}
}

/*
 * Squid cache setup
 */

 /* Create Squid disk cache directories */
function squid_dash_z($instance_id = '', $cache_action='none') {
	global $config, $wf_instances;

	$instance_server = $wf_instances[$instance_id]['server'];

	if (isset($instance_server['enable_cache']) && $instance_server['enable_cache'] != "on") {
		log_error(dgettext("BluePexWebFilter", "[squid] Local cache is disable for this instance"));
		return;
	}

	//Do nothing if there is no cache config
	if (!is_array($config['system']['webfilter']['squidcache']['config'])) {
		log_error(dgettext("BluePexWebFilter", "[squid] 'Local Cache' not configured, disk cache will be disabled."));
		return;
	}

	$settings = $config['system']['webfilter']['squidcache']['config'][0];
	$cachedir = ($settings['harddisk_cache_location'] ? $settings['harddisk_cache_location'] . "{$instance_id}" : "/var/squid/cache{$instance_id}");

	// If the cache system is null, there is no need to initialize the (irrelevant) cache dir.
	// If it already exists, delete it.
	if ($cachesettings['harddisk_cache_system'] == "null") {
		if (is_dir($cachedir)) {
			if (substr($cachedir, 0, 11) === "/var/squid/") {
				log_error(dgettext("BluePexWebFilter", "[squid] Deleting cache dir '{$cachedir}' since 'Hard Disk Cache System' is set to null..."));
				// cannot nuke disk cache while Squid is running
				if (is_service_running('squid')) {
					stop_service("squid");
				}
				@rename($cachedir, "{$cachedir}.old");
				mwexec_bg("/bin/rm -rf {$cachedir}.old");
				squid_restart_services($instance_id);
			} else {
				log_error(dgettext("BluePexWebFilter", "[squid] 'Hard Disk Cache System' is set to null."));
				log_error(dgettext("BluePexWebFilter", "[squid] Will NOT delete cache dir '{$cachedir}' since it is not located under /var/squid. Delete manually if required."));
			}
		}
		return;
	}

	if ($cache_action == "clean" && file_exists($cachedir)) {
		rename ($cachedir, "{$cachedir}.old");
		mwexec_bg("/bin/rm -rf {$cachedir}.old");
	}

	if (!is_dir($cachedir)) {
		log_error(dgettext("BluePexWebFilter", "Creating Squid cache dir {$cachedir}"));
		@mkdir($cachedir, 0750, true);
		@chown($cachedir, SQUID_UID);
		@chgrp($cachedir, SQUID_GID);
	}

	if (!is_dir($cachedir.'/00')) {
		log_error(dgettext("BluePexWebFilter", "Creating squid cache subdirs in $cachedir"));
		mwexec(SQUID_BASE . "/sbin/squid -k shutdown -f /usr/local/etc/squid/squid{$instance_id}.conf");
		sleep(5);
		mwexec(SQUID_BASE . "/sbin/squid -k kill -f /usr/local/etc/squid/squid{$instance_id}.conf");
		// Double check permissions here, should be safe to recurse cache dir if it's small here.
		mwexec("/usr/sbin/chown -R " . SQUID_UID . ":" . SQUID_GID . " $cachedir");
		mwexec(SQUID_BASE . "/sbin/squid -z -f /usr/local/etc/squid/squid{$instance_id}.conf");
	}

	// Re-create the cachedir if clean is forced by manually,
	// or if the cachedir changed, or level1_subdirs don't exist or the number of level1_subdirs changed
	if ($cache_action == "clean" || !is_dir($cachedir) || !is_dir($cachedir . '/00') || $numdirs != $currentdirs) {
		// cannot nuke disk cache while Squid is running
		if (is_service_running('squid')) {
			mwexec(SQUID_BASE . "/sbin/squid -k kill -f /usr/local/etc/squid/squid{$instance_id}.conf");
		}
		if (is_dir($cachedir)) {
			if (substr($cachedir, 0, 11) === "/var/squid/") {
				@rename($cachedir, "{$cachedir}.old");
				mwexec_bg("/bin/rm -rf {$cachedir}.old");
			} else {
				log_error(dgettext("BluePexWebFilter", "[squid] Will NOT delete cache dir '{$cachedir}' since it is not located under /var/squid. Delete manually if required."));
			}
		}

		squid_create_cachedir($instance_id);
		squid_restart_services($instance_id);

	}
}

/* Helper function for squid_dash_z() */
function squid_create_cachedir($instance_id) {
	global $config, $wf_instances;

	$cachesettings = array();
	if (is_array($config['system']['webfilter']['squidcache'])) {
		$cachesettings = $config['system']['webfilter']['squidcache']['config'][0];
	}

	$cachedir = ($cachesettings['harddisk_cache_location'] ? $cachesettings['harddisk_cache_location'] . $instance_id : "/var/squid/cache{$instance_id}");

	if (!is_dir($cachedir)) {
		log_error(dgettext("BluePexWebFilter", "[squid] Creating cache dir '{$cachedir}' ..."));
		safe_mkdir($cachedir, 0755);
		@chown($cachedir, SQUID_UID);
		@chgrp($cachedir, SQUID_GID);
	}

	if (!is_dir($cachedir . '/00')) {
		log_error(dgettext("BluePexWebFilter", "[squid] Creating Squid cache subdirs in {$cachedir} ..."));
		mwexec(SQUID_BASE. "/sbin/squid -z -f /usr/local/etc/squid/squid{$instance_id}.conf");
		// Double check permissions here, should be safe to recurse cache dir if it's small here.
		squid_chown_recursive($cachedir, SQUID_UID, SQUID_GID);
	}

}

/*
 * Squid package install/uninstall
 */

function squid_install_command() {
	global $config, $g;

	// Fix user/group entry permissions
	squid_fixup_user();

	// migrate configuration from old versions
	squid_upgrade_config();

	/* make sure pinger is executable and suid root */
	// XXX: Bug #5114
	if (file_exists(SQUID_LOCALBASE . "/libexec/squid/pinger")) {
		chgrp(SQUID_LOCALBASE . "/libexec/squid/pinger", SQUID_GID);
	}

	if (SQUID_BASE != '/usr/local' &&
	    file_exists('/usr/local/bin/check_ip.php') &&
	    !file_exists(SQUID_BASE . '/bin/check_ip.php'))
		symlink("/usr/local/bin/check_ip.php", SQUID_BASE . "/bin/check_ip.php");

	// antivirus intergration
	squid_antivirus_install_command();

	foreach (array(	SQUID_CONFBASE,
			SQUID_ACLDIR,
			SQUID_SSL_DB ) as $dir) {
		@mkdir($dir, 0755, true);
		squid_chown_recursive($dir, SQUID_UID, SQUID_GID);
	}

	if (!file_exists(SQUID_CONFBASE . '/mime.conf') && file_exists(SQUID_CONFBASE . '/mime.conf.default'))
		copy(SQUID_CONFBASE . '/mime.conf.default', SQUID_CONFBASE . '/mime.conf');

	if (!is_service_running('squid')) {
		log_error(dgettext("BluePexWebFilter", "Starting Squid"));
		mwexec_bg(SQUID_BASE. "/sbin/squid -f " . SQUID_CONFFILE);
	} else {
		log_error(dgettext("BluePexWebFilter", "Reloading Squid for configuration sync"));
		mwexec_bg(SQUID_BASE. "/sbin/squid -k reconfigure -f " . SQUID_CONFFILE);
	}

	// remove unwanted rc script
	unlink_if_exists("/usr/local/etc/rc.d/squid");

	// remove broken cronjobs possibly left over after 'Clear Cache on Log Rotate' misfeature
	install_cron_job("/usr/local/pkg/swapstate_check.php clean;", false);
	install_cron_job("/bin/rm /var/squid/cache/swap.state;", false);

	filter_configure();
}

function squid_deinstall_command() {
	global $config, $g;

	/* remove cronjobs */
	squid_install_cron(false);

	update_status("Finishing package cleanup.");
	mwexec("ps awux | grep \"squid\" | grep -v \"grep\" | awk '{ print $2 }' | xargs kill");
	mwexec("ps awux | grep \"dnsserver\" | grep -v \"grep\" | awk '{ print $2 }' | xargs kill");
	mwexec("ps awux | grep \"unlinkd\" | grep -v \"grep\" | awk '{ print $2 }' | xargs kill");
	update_status("Reloading filter...");

	$plswait_txt = "This operation may take quite some time, please be patient.  Do not press stop or attempt to navigate away from this page during this process.";
	if (is_array($config['system']['webfilter']['squidcache']))
		$settings = $config['system']['webfilter']['squidcache']['config'][0];
	else
		$settings = array();
	$cachedir =($settings['harddisk_cache_location'] ? $settings['harddisk_cache_location'] : '/var/squid/cache');
	$logdir = ($settings['log_dir'] ? $settings['log_dir'] : '/var/squid/logs');
	if (!$settings['remove_cache'] || $settings['remove_cache'] == "off") {
		update_status("Removing cache ... One moment please...");
		update_output_window("$plswait_txt");
		mwexec("mv {$cachedir} /var/squid/cache_bkp");
		mwexec_bg("rm -rf /var/squid/cache_bkp");
		mwexec("rm -rf {$logdir}");
	}

	if (is_array($config['system']['webfilter']['squid'])) {
		$squidsettings = $config['system']['webfilter']['squid']['config'][0];
	} else {
		$squidsettings = array();
	}
	$logdir = ($squidsettings['log_dir'] ? $squidsettings['log_dir'] : '/var/squid/logs');
	$keep = ($squidsettings['keep_squid_data'] ? true : false);

	if (!$keep) {
		if (is_dir("{$cachedir}")) {
			if (substr($cachedir, 0, 11) === "/var/squid/") {
				mwexec_bg("/bin/rm -rf {$cachedir}");
			} else {
				log_error(dgettext("BluePexWebFilter", "[squid] Will NOT delete Squid cache dir '{$cachedir}' since it is not located under /var/squid. Delete manually if required."));
			}
		}
		if (is_dir("{$logdir}")) {
			if (substr($logdir, 0, 11) === "/var/squid/") {
				mwexec("/bin/rm -rf {$logdir}");
			} else {
				log_error(dgettext("BluePexWebFilter", "[squid] Will NOT delete Squid log dir '{$logdir}' since it is not located under /var/squid. Delete manually if required."));
			}
		}
		$dirs = array("/var/run/squid", "/var/squid");
		foreach ($dirs as $dir) {
			if (is_dir("{$dir}")) {
				mwexec("/bin/rm -rf {$dir}");
			}
		}
	}

	// remove antivirus integration features
	squid_antivirus_deinstall_command();

	filter_configure();

	/* Remove package settings from config if 'Keep Settings/Data' is disabled */
	if (!$keep) {
		log_error(dgettext("BluePexWebFilter", "[squid] Removing all webfilter settings since 'Keep Settings/Data' is disabled..."));
		if (is_array($config['system']['webfilter']['webfilter'])) {
			unset($config['system']['webfilter']['webfilter']);
		}
		if (is_array($config['system']['webfilter']['squidantivirus'])) {
			unset($config['system']['webfilter']['squidantivirus']);
		}
		if (is_array($config['system']['webfilter']['squidcache'])) {
			unset($config['system']['webfilter']['squidcache']);
		}
		if (is_array($config['system']['webfilter']['squidnac'])) {
			unset($config['system']['webfilter']['squidnac']);
		}
		if (is_array($config['system']['webfilter']['squidreverse'])) {
			unset($config['system']['webfilter']['squidreverse']);
		}
		if (is_array($config['system']['webfilter']['squidreversegeneral'])) {
			unset($config['system']['webfilter']['squidreversegeneral']);
		}
		if (is_array($config['system']['webfilter']['squidreversepeer'])) {
			unset($config['system']['webfilter']['squidreversepeer']);
		}
		if (is_array($config['system']['webfilter']['squidreverseredir'])) {
			unset($config['system']['webfilter']['squidreverseredir']);
		}
		if (is_array($config['system']['webfilter']['squidreverseuri'])) {
			unset($config['system']['webfilter']['squidreverseuri']);
		}
		if (is_array($config['system']['webfilter']['squidsync'])) {
			unset($config['system']['webfilter']['squidsync']);
		}
		if (is_array($config['system']['webfilter']['squidtraffic'])) {
			unset($config['system']['webfilter']['squidtraffic']);
		}
		if (is_array($config['system']['webfilter']['squidremote'])) {
			unset($config['system']['webfilter']['squidremote']);
		}
		if (is_array($config['system']['webfilter']['squidusers'])) {
			unset($config['system']['webfilter']['squidusers']);
		}
	}
}

/* Migrate configuration from god knows which Squid package versions */
/* None of these ever existed with Squid 3.4 package and this cruft should be most likely just removed */
function squid_upgrade_config() {
	global $config, $g, $wf_instances;

	/* migrate existing csv config fields */
	if (is_array($config['system']['webfilter']['webfilter']['config'][0]['authsettings'])) {
		$settingsauth = $config['system']['webfilter']['webfilter']['config'][0]['authsettings'];
	}
	if (is_array($config['system']['webfilter']['squidcache']['config'])) {
		$settingscache = $config['system']['webfilter']['squidcache']['config'][0];
	}
	if (is_array($config['system']['webfilter']['squidnac']['config'])) {
		$settingsnac = $config['system']['webfilter']['squidnac']['config'][0];
	}

	/* migrate auth settings */
	if (!empty($settingsauth['no_auth_hosts']) && strstr($settingsauth['no_auth_hosts'], ",")) {
		$settingsauth['no_auth_hosts'] = base64_encode(implode("\n", explode(",", $settingsauth['no_auth_hosts'])));
		$config['system']['webfilter']['webfilter']['config'][0]['authsettings']['no_auth_hosts'] = $settingsauth['no_auth_hosts'];
	}

	/* Set storage system */
	if ($g['platform'] == "nanobsd") {
		$config['system']['webfilter']['squidcache']['config'][0]['harddisk_cache_system'] = 'null';
	}

	/* migrate cache settings */
	if (!empty($settingscache['donotcache']) && strstr($settingscache['donotcache'], ",")) {
		$settingscache['donotcache'] = base64_encode(implode("\n", explode(",", $settingscache['donotcache'])));
		$config['system']['webfilter']['squidcache']['config'][0]['donotcache'] = $settingscache['donotcache'];
	}
	/* unset broken dynamic caching patterns removed since Squid3 package v0.4.3 */
	if (!empty($config['system']['webfilter']['squidcache']['config']['refresh_patterns'])) {
		unset($config['system']['webfilter']['squidcache']['config']['refresh_patterns']);
	}

	/* migrate nac settings */
	if (!empty($settingsnac['allowed_subnets']) && strstr($settingsnac['allowed_subnets'], ",")) {
		$settingsnac['allowed_subnets'] = base64_encode(implode("\n", explode(",", $settingsnac['allowed_subnets'])));
		$config['system']['webfilter']['squidnac']['config'][0]['allowed_subnets'] = $settingsnac['allowed_subnets'];
	}

	if (!empty($settingsnac['banned_hosts']) && strstr($settingsnac['banned_hosts'], ",")) {
		$settingsnac['banned_hosts'] = base64_encode(implode("\n", explode(",", $settingsnac['banned_hosts'])));
		$config['system']['webfilter']['squidnac']['config'][0]['banned_hosts'] = $settingsnac['banned_hosts'];
	}

	if (!empty($settingsnac['banned_macs']) && strstr($settingsnac['banned_macs'], ",")) {
		$settingsnac['banned_macs'] = base64_encode(implode("\n", explode(",", $settingsnac['banned_macs'])));
		$config['system']['webfilter']['squidnac']['config'][0]['banned_macs'] = $settingsnac['banned_macs'];
	}

	if (!empty($settingsnac['unrestricted_hosts']) && strstr($settingsnac['unrestricted_hosts'], ",")) {
		$settingsnac['unrestricted_hosts'] = base64_encode(implode("\n", explode(",", $settingsnac['unrestricted_hosts'])));
		$config['system']['webfilter']['squidnac']['config'][0]['unrestricted_hosts'] = $settingsnac['unrestricted_hosts'];
	}

	if (!empty($settingsnac['unrestricted_macs']) && strstr($settingsnac['unrestricted_macs'], ",")) {
		$settingsnac['unrestricted_macs'] = base64_encode(implode("\n", explode(",", $settingsnac['unrestricted_macs'])));
		$config['system']['webfilter']['squidnac']['config'][0]['unrestricted_macs'] = $settingsnac['unrestricted_macs'];
	}

	if (!empty($settingsnac['whitelist']) && strstr($settingsnac['whitelist'], ",")) {
		$settingsnac['whitelist'] = base64_encode(implode("\n", explode(",", $settingsnac['whitelist'])));
		$config['system']['webfilter']['squidnac']['config'][0]['whitelist'] = $settingsnac['whitelist'];
	}

	if (!empty($settingsnac['blacklist']) && strstr($settingsnac['blacklist'], ",")) {
		$settingsnac['blacklist'] = base64_encode(implode("\n", explode(",", $settingsnac['blacklist'])));
		$config['system']['webfilter']['squidnac']['config'][0]['blacklist'] = $settingsnac['blacklist'];
	}

	if (!empty($settingsnac['block_user_agent']) && strstr($settingsnac['block_user_agent'], ",")) {
		$settingsnac['block_user_agent'] = base64_encode(implode("\n", explode(",", $settingsnac['block_user_agent'])));
		$config['system']['webfilter']['squidnac']['config'][0]['block_user_agent'] = $settingsnac['block_user_agent'];
	}

	if (!empty($settingsnac['block_reply_mime_type']) && strstr($settingsnac['block_reply_mime_type'], ",")) {
		$settingsnac['block_reply_mime_type'] = base64_encode(implode("\n", explode(",", $settingsnac['block_reply_mime_type'])));
		$config['system']['webfilter']['squidnac']['config'][0]['block_reply_mime_type'] = $settingsnac['block_reply_mime_type'];
	}

	/*Migrate reverse settings*/
	if (is_array($config['system']['webfilter']['squidreverse'])) {
		$old_reverse_settings=$config['system']['webfilter']['squidreverse']['config'][0];

		//Settings
		if (!is_array($config['system']['webfilter']['squidreversegeneral'])) {
			$config['system']['webfilter']['squidreversegeneral']['config'][0]=$old_reverse_settings;
			unset ($config['system']['webfilter']['squidreversegeneral']['config'][0]['reverse_cache_peer']);
			unset ($config['system']['webfilter']['squidreversegeneral']['config'][0]['reverse_uri']);
			unset ($config['system']['webfilter']['squidreversegeneral']['config'][0]['reverse_acl']);
		}

		//PEERS
		if (!is_array($config['system']['webfilter']['squidreversepeer'])) {
			foreach (explode("\n",sq_text_area_decode($old_reverse_settings['reverse_cache_peer'])) as $cache_peers) {
				foreach (explode(";",$cache_peers) as $cache_peer) {
					$config['system']['webfilter']['squidreversepeer']['config'][] = array(
						'description' => 'migrated',
						'enable' => 'on',
						'name' => $cache_peer[0],
						'port' => $cache_peer[1],
						'protocol' => $cache_peer[2]
					);
				}
			}
		}

		//MAPPINGS
		if (!is_array($config['system']['webfilter']['squidreverseuri'])) {
			foreach (explode("\n",sq_text_area_decode($old_reverse_settings['reverse_acl'])) as $acls) {
				foreach (explode(";",$acls) as $acl) {
					array_push(${'peer_'.$acl[0]},$acl[1]);
				}
			}
			foreach (explode("\n",sq_text_area_decode($old_reverse_settings['reverse_uri'])) as $uris) {
				foreach (explode(";",$uris) as $uri) {
					$peer_list=(is_array(${'peer_'.$uri[0]})?implode(",",${'peer_'.$uri[0]}):"");
					$config['system']['webfilter']['squidreverseuri']['config'][] = array(
						'description' => 'migrated',
						'enable' => 'on',
						'name' => $uri[0],
						'uri' => $uri[1],
						'vhost' => $uri[2],
						'peers' => $peer_list
					);
				}
			}
		}
	}

	$squid_conf = $config['system']['webfilter']['webfilter']['config'][0];

	/* XXX: broken Captive Portal patch (Bug #5594) */
	if (isset($squid_conf['patch_cp'])) {
		if ($squid_conf['patch_cp'] == "on") {
			$cp_msg = "Patch Captive Portal feature was removed - see Bug #5594!";
			log_error(dgettext("BluePexWebFilter", "[squid] {$cp_msg}"));
			unset($cp_msg);
		}
		unset($squid_conf['patch_cp']);
	}

	// Migrate squid config for the proxy instances
	if (!isset($wf_instances[0]['server']) && isset($config['system']['webfilter']['webfilter']['config'][0])) {
		$wf_instances[0]['server'] = $config['system']['webfilter']['webfilter']['config'][0];
		$wf_instances[0]['server']['name'] = "Instance 1";
		$wf_instances[0]['server']['authsettings'] = $config['system']['webfilter']['webfilter']['config'][0]['authsettings'];
		unset($config['system']['webfilter']['webfilter']['config'][0]);
	}
	// Migrate squidnac for the proxy instances
	if (!isset($wf_instances[0]['squidnac']) && isset($config['system']['webfilter']['squidnac']['config'][0])) {
		$wf_instances[0]['squidnac'] = $config['system']['webfilter']['squidnac']['config'][0];
		unset($config['system']['webfilter']['squidnac']);
	}

	/* unset broken antivirus settings */
	squid_antivirus_upgrade_config();

	write_config("Applied general changes under squid");
}

function squid_before_form_general(&$pkg, &$input_errors) {

	$values = array("en", "pt-br");

	foreach($values as $value) {
		if (!is_dir(SQUID_CONFBASE . "/errors/{$value}"))
			$input_errors[] = dgettext("BluePexWebFilter", "Language {$value} not found.");
	}

	$name = array();
	foreach ($values as $value)
		$names[] = implode(" ", explode("_", $value));

	$i = 0;
	foreach ($pkg['fields']['field'] as $field) {
		if ($field['fieldname'] == 'error_language')
			break;
		$i++;
	}
	$field = &$pkg['fields']['field'][$i];

	for ($i = 0; $i < count($values) - 1; $i++)
		$field['options']['option'][] = array('name' => $names[$i], 'value' => $values[$i]);
}

/*
 * Squid input validation
 */

/* Proxy Server: General Settings input validation */
function squid_validate_general($post, &$input_errors) {
	global $config, $wf_instances;

	$settings = array();
	if (isset($wf_instances[$post['instance_id']]['server'])) {
		$settings = $wf_instances[$post['instance_id']]['server'];
	}

	if (!preg_match("/^[a-z0-9\_]+$/i", $post['instance_name'])) {
		$input_errors[] = dgettext("BluePexWebFilter", "Only english letters, numbers and underscore are valid characters for the instance name!");
	}

	$port = ($settings['proxy_port'] ? $settings['proxy_port'] : 3128);
	$port = $post['proxy_port'] ? $post['proxy_port'] : $port;

	// force users to select at least one proxy or reverse proxy interface when enabling Squid
	if ($post['enable_squid'] == "on") {
		// if reverse proxy is configured, perhaps the user wants to use the reverse proxy features only
		if (!squid_reverse_enabled()) {
			if (empty($post['active_interface'])) {
				$input_errors[] = dgettext("BluePexWebFilter", "You must select at least one interface under 'Proxy Interface(s)' to enable Squid proxy.");
				$input_errors[] = dgettext("BluePexWebFilter", "If you intend to use Squid as reverse proxy ONLY, then visit Services: Squid Proxy Server: General, configure and save the reverse proxy settings first.");
			}
		} else {
			log_error(dgettext("BluePexWebFilter", "[squid] Enabled as reverse proxy ONLY. If this is not what you intended, visit Services: Squid Proxy Server: General and configure proxy interfaces."));
		}
	}

	$icp_port = trim($post['icp_port']);
	if (!empty($icp_port) && !is_port($icp_port))
		$input_errors[] = dgettext("BluePexWebFilter", 'You must enter a valid port number in the \'ICP port\' field');

	$webgui_port = $config['system']['webgui']['port'];

	if (($config['system']['webgui']['port'] == "") && ($config['system']['webgui']['protocol'] == "http")) {
		$webgui_port = 80;
	}
	if (($config['system']['webgui']['port'] == "") && ($config['system']['webgui']['protocol'] == "https")) {
		$webgui_port = 443;
	}

	if (($post['transparent_proxy'] != 'on') && ($port == $webgui_port)) {
		$input_errors[] = dgettext("BluePexWebFilter", "You can not run squid on the same port as the webgui");
	}

	if ($post['ssl_proxy'] == 'on') {
		if ($post['dca'] == 'none') {
			$input_errors[] = dgettext("BluePexWebFilter", "SSL interception cannot be enabled without a CA.");
		}
		if (empty($post['ssl_active_interface'])) {
			$input_errors[] = dgettext("BluePexWebFilter", "You must select at least one interface under 'SSL Intercept Interface(s)' when 'HTTPS/SSL Interception' is enabled.");
		} else {
			// allow HTTPS/SSL Interception only on interfaces where Squid is actually running to keep configuration sane
			$a_ifaces = $post['active_interface'] ?: array();
			$s_ifaces = $post['ssl_active_interface'];
			foreach ($s_ifaces as $s_iface) {
				if (!in_array($s_iface, $a_ifaces)) {
					$err_iface = convert_friendly_interface_to_friendly_descr($s_iface);
					$input_errors[] = dgettext("BluePexWebFilter", "'SSL Intercept Interface(s)' may only contain interfaces also selected in 'Proxy Interface(s)' above. '{$err_iface}' is not valid.");
					unset($err_iface);
				}
			}
			unset($a_ifaces, $s_ifaces, $s_iface);
		}
	}

	foreach (array('defined_ip_proxy_off') as $hosts) {
		foreach (explode(";", $post[$hosts]) as $host) {
			$host = trim($host);
			if (!empty($host) && !is_ipaddr($host) && !is_alias($host) && !is_hostname($host) && !is_subnet($host))
				$input_errors[] = dgettext("BluePexWebFilter", "The entry '$host' is not a valid IP address, hostname, or alias");
		}
	}
	foreach (array('defined_ip_proxy_off_dest') as $hosts) {
		foreach (explode(";", $post[$hosts]) as $host) {
			$host = trim($host);
			if (!empty($host) && !is_ipaddr($host) && !is_alias($host) && !is_hostname($host) && !is_subnet($host))
				$input_errors[] = dgettext("BluePexWebFilter", "The entry '$host' is not a valid IP address, hostname, or alias");
		}
	}

	if (!empty($post['dns_nameservers'])) {
		$altdns = explode(";", ($post['dns_nameservers']));
		foreach ($altdns as $dnssrv) {
			if (!is_ipaddr($dnssrv)) {
				$input_errors[] = dgettext("BluePexWebFilter", 'You must enter a valid IP address in the \'Alternate DNS servers\' field');
				break;
			}
		}
	}
}

/* Proxy Server: Remote Proxy Settings input validation */
function squid_validate_upstream($post, &$input_errors) {
	$addr = trim($post['proxyaddr']);
	if (empty($addr)) {
		$input_errors[] = dgettext("BluePexWebFilter", 'The field \'Hostname\' is required');
	} else {
		if (!is_ipaddr($addr) && !is_domain($addr))
			$input_errors[] = dgettext("BluePexWebFilter", 'You must enter a valid IP address or host name in the \'Proxy hostname\' field');
	}

	foreach (array('proxyport' => 'TCP port', 'icpport' => 'ICP port') as $field => $name) {
		$port = trim($post[$field]);
		if (empty($port)) {
			$input_errors[] = dgettext("BluePexWebFilter", "The field '$name' is required");
		} else {
			if (!is_port($port))
				$input_errors[] = dgettext("BluePexWebFilter", "The field '$name' must contain a valid port number, between 0 and 65535");
		}
	}
}

/* Proxy Server: Cache Management input validation */
function squid_validate_cache($post, &$input_errors, $instance_id) {
	$num_fields = array(
		'harddisk_cache_size' => 'Hard disk cache size',
		'memory_cache_size' => 'Memory cache size',
		'maximum_object_size' => 'Maximum object size',
	);

	foreach ($num_fields as $field => $name) {
		$value = trim($post[$field]);
		if (!is_numericint($value))
			$input_errors[] = dgettext("BluePexWebFilter", "You must enter a valid value for '$field'");
	}

	$value = trim($post['minimum_object_size']);
	if (!is_numericint($value))
		$input_errors[] = dgettext("BluePexWebFilter", 'You must enter a valid value for \'Minimum object size\'');

	if (!empty($post['cache_swap_low'])) {
		$value = trim($post['cache_swap_low']);
		if (!is_numericint($value) || ($value > 100))
			$input_errors[] = dgettext("BluePexWebFilter", 'You must enter a valid value for \'Low-water-mark\'');
	}

	if (!empty($post['cache_swap_high'])) {
		$value = trim($post['cache_swap_high']);
		if (!is_numericint($value) || ($value > 100))
			$input_errors[] = dgettext("BluePexWebFilter", 'You must enter a valid value for \'High-water-mark\'');
	}

	if ($post['donotcache'] != "") {
		foreach (explode("\n", $post['donotcache']) as $host) {
			$host = trim($host);
			if (!is_ipaddr($host) && !is_domain($host))
				$input_errors[] = dgettext("BluePexWebFilter", "The host '$host' is not a valid IP or host name");
		}
	}

	squid_dash_z($instance_id);
}

/* Proxy Server: Access Control input validation */
function squid_validate_nac($post, &$input_errors) {
	$allowed_subnets = explode("\n", $post['allowed_subnets']);
	foreach ($allowed_subnets as $subnet) {
		$subnet = trim($subnet);
		if (!empty($subnet) && !is_subnet($subnet))
			$input_errors[] = dgettext("BluePexWebFilter", "The subnet '$subnet' is not a valid CIDR range");
	}

	foreach (array('unrestricted_hosts', 'banned_hosts') as $hosts) {
		if (!preg_match_all("@([0-9.]+)(/[0-9.]+|)@", $_POST[$hosts], $matches)) {
			continue;
		}
		for ($x=0; $x < count($matches[1]); $x++) {
			if ($matches[2][$x] == "") {
				if (!is_ipaddr($matches[1][$x]))
					$input_errors[] = dgettext("BluePexWebFilter", "'{$matches[1][$x]}' is not a valid IP address");
			} else {
				if (!is_subnet($matches[0][$x]))
					$input_errors[] = dgettext("BluePexWebFilter", "The subnet '{$matches[0][$x]}' is not a valid CIDR range");
			}
		}
	}

	foreach (array('unrestricted_macs', 'banned_macs') as $macs) {
		foreach (explode("\n", $post[$macs]) as $mac) {
			$mac = trim($mac);
			if (!empty($mac) && !is_macaddr($mac))
				$input_errors[] = dgettext("BluePexWebFilter", "The mac '$mac' is not a valid MAC address");
		}
	}

	foreach (explode(",", $post['timelist']) as $time) {
		$time = trim($time);
		if (!empty($time) && !squid_is_timerange($time))
			$input_errors[] = dgettext("BluePexWebFilter", "The time range '$time' is not a valid time range");
	}

	if (!empty($post['ext_cachemanager'])) {
		$extmgr = explode(";", ($post['ext_cachemanager']));
		foreach ($extmgr as $mgr) {
			if (!is_ipaddr($mgr))
				$input_errors[] = dgettext("BluePexWebFilter", 'You must enter a valid IP address in the \'External Cache Manager\' field');
		}
	}
}

/* Proxy server: Traffic Management input validation */
function squid_validate_traffic($post, &$input_errors) {
	$num_fields = array(
		'max_download_size' => 'Maximum download size',
		'max_upload_size' => 'Maximum upload size',
		'perhost_throttling' => 'Per-host bandwidth throttling',
		'overall_throttling' => 'Overall bandwidth throttling',
	);

	foreach ($num_fields as $field => $name) {
		$value = trim($post[$field]);
		if (!is_numericint($value))
			$input_errors[] = dgettext("BluePexWebFilter", "The field '$name' must contain a positive number");
	}

	if (!empty($post['quick_abort_min'])) {
		$value = trim($post['quick_abort_min']);
		if (!is_numericint($value))
			$input_errors[] = dgettext("BluePexWebFilter", "The field 'Finish when remaining KB' must contain a positive number");
	}

	if (!empty($post['quick_abort_max'])) {
		$value = trim($post['quick_abort_max']);
		if (!is_numericint($value))
			$input_errors[] = dgettext("BluePexWebFilter", "The field 'Abort when remaining KB' must contain a positive number");
	}

	if (!empty($post['quick_abort_pct'])) {
		$value = trim($post['quick_abort_pct']);
		if (!is_numericint($value) || ($value > 100))
			$input_errors[] = dgettext("BluePexWebFilter", "The field 'Finish when remaining %' must contain a percentage");
	}
}

/* Proxy Server: Authentication input validation */
function squid_validate_auth($post, &$input_errors) {
	$num_fields = array(
		array('auth_processes', 'Authentication processes', 1),
		array('auth_ttl', 'Authentication TTL', 0),
	);

	foreach ($num_fields as $field) {
		$value = trim($post[$field[0]]);
		if (!empty($value) && (!is_numeric($value) || ($value < $field[2])))
			$input_errors[] = dgettext("BluePexWebFilter", "The field '{$field[1]}' must contain a valid number greater than {$field[2]}");
	}

	$auth_method = $post['auth_method'];
	if (($auth_method != 'none') && ($auth_method != 'local') && ($auth_method != 'cp')) {
		$server = trim($post['auth_server']);
		if (empty($server))
			$input_errors[] = dgettext("BluePexWebFilter", 'The field \'Authentication server\' is required');
		else if (!is_ipaddr($server) && !is_domain($server))
			$input_errors[] = dgettext("BluePexWebFilter", 'The field \'Authentication server\' must contain a valid IP address or domain name');

		$port = trim($post['auth_server_port']);
		if (!empty($port) && !is_port($port))
			$input_errors[] = dgettext("BluePexWebFilter", 'The field \'Authentication server port\' must contain a valid port number');

		switch ($auth_method) {
		case 'ldap':
			$user = trim($post['ldap_user']);
			if (empty($user))
				$input_errors[] = dgettext("BluePexWebFilter", 'The field \'LDAP server user DN\' is required');
			else if (!$user)
				$input_errors[] = dgettext("BluePexWebFilter", 'The field \'LDAP server user DN\' must be a valid domain name');
			break;
		case 'radius':
			$secret = trim($post['radius_secret']);
			if (empty($secret))
				$input_errors[] = dgettext("BluePexWebFilter", 'The field \'RADIUS secret\' is required');
			break;
		case 'msnt':
			foreach (explode(",", trim($post['msnt_secondary'])) as $server) {
				if (!empty($server) && !is_ipaddr($server) && !is_domain($server))
					$input_errors[] = dgettext("BluePexWebFilter", "The host '$server' is not a valid IP address or domain name");
			}
			break;
		}

		$no_auth = explode("\n", $post['no_auth_hosts']);
		foreach ($no_auth as $host) {
			$host = trim($host);
			if (!empty($host) && !is_subnet($host))
				$input_errors[] = dgettext("BluePexWebFilter", "The host '$host' is not a valid CIDR range");
		}
	}
}

/*
 * rc scripts, services and cronjobs
 */

/* Handle cronjob install/uninstall */
function squid_install_cron($should_install) {
	global $config, $g, $wf_instances;

	if ($g['booting']==true)
		return;
	$rotate_is_installed = false;
	$swapstate_is_installed = false;

	if (!$config['cron']['item'] || empty($wf_instances))
		return;

	$settings = array();
	if (is_array($config['system']['webfilter']['squidcache'])) {
		$settings = $config['system']['webfilter']['squidcache']['config'][0];
	}

	$x=0;
	$rotate_job_id=-1;
	$swapstate_job_id=-1;
	$cron_cmd=($settings['clear_cache']=='on' ?  "/usr/local/pkg/swapstate_check.php clean; " : "");
	$cron_cmd .= SQUID_BASE."/sbin/squid -k rotate -f " . SQUID_CONFFILE;
	$need_write = false;
	foreach ($config['cron']['item'] as $item) {
		if (strstr($item['task_name'], "squid_rotate_logs")) {
			$rotate_job_id = $x;
			if ($item['command'] != $cron_cmd) {
				$config['cron']['item'][$x]['command']=$cron_cmd;
				$need_write = true;
			}
		} elseif (strstr($item['task_name'], "squid_check_swapstate")) {
			$swapstate_job_id = $x;
		}
		$x++;
	}
	if ($should_install) {
		$cachedir =($settings['harddisk_cache_location'] ? $settings['harddisk_cache_location'] : '/var/squid/cache');
		if ($rotate_job_id < 0) {
			$cron_item['command']=($settings['clear_cache']=='on' ?  "/usr/local/pkg/swapstate_check.php clean; " : "");
			$cron_item = array();
			$cron_item['task_name'] = "squid_rotate_logs";
			$cron_item['minute'] = "0";
			$cron_item['hour'] = "0";
			$cron_item['mday'] = "*";
			$cron_item['month'] = "*";
			$cron_item['wday'] = "*";
			$cron_item['who'] = "root";
			$cron_item['command'] .= $cron_cmd;
			/* Add this cron_item as a new entry at the end of the item array. */
			$config['cron']['item'][] = $cron_item;
			$need_write = true;
		}
		if ($swapstate_job_id < 0) {
			$cron_item = array();
			$cron_item['task_name'] = "squid_check_swapstate";
			$cron_item['minute'] = "*/15";
			$cron_item['hour'] = "*";
			$cron_item['mday'] = "*";
			$cron_item['month'] = "*";
			$cron_item['wday'] = "*";
			$cron_item['who'] = "root";
			$cron_item['command'] = "/usr/local/pkg/swapstate_check.php";
			/* Add this cron_item as a new entry at the end of the item array. */
			$config['cron']['item'][] = $cron_item;
			$need_write = true;
		}
		if ($need_write) {
			parse_config(true);
			write_config("Adding Squid Cron Jobs");
		}
	} else {
		if ($rotate_job_id >= 0) {
			unset($config['cron']['item'][$rotate_job_id]);
			$need_write = true;
		}
		if ($swapstate_job_id >= 0) {
			unset($config['cron']['item'][$swapstate_job_id]);
			$need_write = true;
		}
		if ($need_write) {
			parse_config(true);
			write_config("Removing Squid Cron Jobs");
		}
	}
	configure_cron();
}

/* Start and/or stop services according to Squid configuration */
function squid_restart_services($ref_instance_id = null) {
	global $config, $wf_instances;

	// do not (re)start squid services on boot
	if (platform_booting()) {
		return;
	}

	foreach ($wf_instances as $instance_id => $instance_config) {
		if (is_numeric($ref_instance_id)) {
			if ($ref_instance_id != $instance_id) {
				continue;
			}
		}
		$settings = $wf_instances[$instance_id]['server'];

		if (squid_enabled($instance_id)) {
			$squid_conf_file = "squid{$instance_id}.conf";
			if (!file_exists(SQUID_CONFBASE . "/" . $squid_conf_file)) {
				continue;
			}
			if (!is_process_running_match($squid_conf_file)) {
				log_error(dgettext("BluePexWebFilter", "[squid] Starting service '{$settings['name']}'..."));
				$command = SQUID_BASE . "/sbin/squid -f " . SQUID_CONFBASE . "/" . $squid_conf_file;
			} else {
				log_error(dgettext("BluePexWebFilter", "[squid] Reloading for configuration sync '{$settings['name']}'..."));
				$command = SQUID_BASE . "/sbin/squid -k reconfigure -f " . SQUID_CONFBASE . "/" . $squid_conf_file;
			}
			$gc = exec($command, $error, $out);
			if ($error == 1) {
				log_error(sprintf(dgettext("BluePexWebFilter", "Error: %s"), implode("\n", $error)));
			}
			// sleep for a couple seconds to give squid a chance to fire up fully.
			for ($i = 0; $i < 10; $i++) {
				if (!is_process_running_match($squid_conf_file)) {
					sleep(1);
				}
			}
		} else {
			log_error(dgettext("BluePexWebFilter", "[squid] Stopping instace '{$settings['name']}'..."));
		}
	}
}

/*
 * Change the squid OS user to match what is required for squid to run and have
 * access to the files and devices it needs. See #5869
 */
function squid_fixup_user() {
	$squid_userinfo = posix_getpwnam(SQUID_UID);
	$squid_groupinfo = posix_getgrgid($squid_userinfo['gid']);
	$squid_exgrpinfo = posix_getgrnam(SQUID_EXTRA_GID);

	/* If the squid user group is not what we want, fix it. */
	if ($squid_groupinfo['name'] != SQUID_GID) {
		mwexec('/usr/sbin/pw usermod -n ' . escapeshellarg(SQUID_UID) . ' -g ' . escapeshellarg(SQUID_GID));
	}
	/* Ensure the squid user is also a member of the desired 'extra' group */
	if (!in_array(SQUID_UID, $squid_exgrpinfo['members'])) {
		mwexec('/usr/sbin/pw groupmod -n ' . escapeshellarg(SQUID_EXTRA_GID) . ' -m ' . escapeshellarg(SQUID_UID));
	}
}

/* Handle root CA certificates bundle */
function squid_check_ca_hashes() {
	global $config,$g;

	// check certificates
	$cert_count = 0;
	if (is_dir(SQUID_LOCALBASE. '/share/certs')) {
		if ($handle = opendir(SQUID_LOCALBASE.'/share/certs')) {
			while (false !== ($file = readdir($handle))) {
				if (preg_match ("/\d+.0/",$file))
					$cert_count++;
			}
			closedir($handle);
		}
	}
	if ($cert_count < 10) {
		conf_mount_rw();
		// create ca-root hashes from ca-root-nss package
		log_error(dgettext("BluePexWebFilter", "Creating root certificate bundle hashes from the Mozilla Project"));
		$cas=file(SQUID_LOCALBASE.'/share/certs/ca-root-nss.crt');
		$cert=0;
		foreach ($cas as $ca) {
			if (preg_match("/--BEGIN CERTIFICATE--/",$ca))
				$cert=1;
			if ($cert == 1)
				$crt.=$ca;
			if (preg_match("/-END CERTIFICATE-/",$ca)) {
				file_put_contents("/tmp/cert.pem",$crt, LOCK_EX);
				$cert_hash=array();
				exec("/usr/bin/openssl x509 -hash -noout -in /tmp/cert.pem",$cert_hash);
				file_put_contents(SQUID_LOCALBASE."/share/certs/".$cert_hash[0].".0",$crt,LOCK_EX);
				$crt="";
				$cert=0;
			}
		}
	}
}

/* Proxy Server: General Settings configuration handler */
function squid_resync_general($instance_id) {
	global $g, $config, $valid_acls, $wf_instances;

	$settings = $wf_instances[$instance_id]['server'];

	$conf = "# This file is automatically generated by Webfilter\n";
	$conf .= "# Do not edit manually !\n\n";
	// Check ssl interception
	if (($settings['ssl_proxy'] == 'on')) {
		squid_check_ca_hashes();
		$srv_cert = lookup_ca($settings["dca"]);
		if ($srv_cert != false) {
			if (base64_decode($srv_cert['prv'])) {
				// check if ssl_db was initilized by Squid
				if (!file_exists(SQUID_SSL_DB . "/serial")) {
					if (is_dir(SQUID_SSL_DB)) {
						mwexec("/bin/rm -rf " . SQUID_SSL_DB);
					}
					//mwexec(SQUID_LOCALBASE . "/libexec/squid/ssl_crtd -c -s " . SQUID_SSL_DB);
					mwexec(SQUID_LOCALBASE . "/libexec/squid/security_file_certgen -c -s " . SQUID_SSL_DB . " -M 4MB");
				}
				// force squid user permission on /var/squid/lib/ssl_db/
				squid_chown_recursive(SQUID_SSL_DB, SQUID_UID, SQUID_GID);
				// cert, key, version, cipher, options, clientca, cafile, capath, crlfile, dhparams, sslflags, sslcontext
				$crt_pk = SQUID_CONFBASE . "/serverkey.pem";
				$crt_capath = SQUID_LOCALBASE . "/share/certs/";
				/* XXX: Bug #4453
				 * http://wiki.squid-cache.org/ConfigExamples/Intercept/SslBumpExplicit#Modern_DH.2Fciphers_usage
				 */
				//$sslproxy_cipher = "EECDH+ECDSA+AESGCM:EECDH+aRSA+AESGCM:EECDH+ECDSA+SHA384:EECDH+ECDSA+SHA256:EECDH+aRSA+SHA384:EECDH+aRSA+SHA256:EECDH+aRSA+RC4:EECDH:EDH+aRSA:!RC4:!aNULL:!eNULL:!LOW:!3DES:!MD5:!EXP:!PSK:!SRP:!DSS";
				$sslproxy_cipher = "EECDH+ECDSA+AESGCM:EECDH+aRSA+AESGCM:EECDH+ECDSA+SHA384:EECDH+ECDSA+SHA256:EECDH+aRSA+SHA384:EECDH+aRSA+SHA256:EECDH+aRSA+RC4:EECDH:EDH+aRSA:HIGH:!RC4:!aNULL:!eNULL:!LOW:!3DES:!MD5:!EXP:!PSK:!SRP:!DSS";
				$sslproxy_dhparams = "/etc/dh-parameters.2048";
				$sslproxy_options = "NO_SSLv2,NO_SSLv3,SINGLE_DH_USE";
				file_put_contents($crt_pk, base64_decode($srv_cert['prv']) . base64_decode($srv_cert['crt']));
				$sslcrtd_children = ($settings['sslcrtd_children'] ? $settings['sslcrtd_children'] : 5);
				$ssl_interception .= "ssl-bump generate-host-certificates=on dynamic_cert_mem_cache_size=" . ($sslcrtd_children*2) . "MB cert={$crt_pk} capath={$crt_capath} cipher={$sslproxy_cipher} dhparams={$sslproxy_dhparams} options={$sslproxy_options}\n";
				//$interception_checks = "sslcrtd_program " . SQUID_LOCALBASE . "/libexec/squid/ssl_crtd -s " . SQUID_SSL_DB . " -M 4MB -b 2048\n";
				$interception_checks = "sslcrtd_program " . SQUID_LOCALBASE . "/libexec/squid/security_file_certgen -s " . SQUID_SSL_DB . " -M 4MB -b 2048\n";
				$interception_checks .= "sslcrtd_children {$sslcrtd_children}\n";
				//$interception_checks .= "sslproxy_capath {$crt_capath}\n";
				//$interception_checks .= "sslproxy_options {$sslproxy_options}\n";
				//$interception_checks .= "sslproxy_cipher {$sslproxy_cipher}\n";
				if (preg_match("/sslproxy_cert_error/", $settings["interception_checks"])) {
					$interception_checks .= "sslproxy_cert_error allow all\n";
				}
				//if (preg_match("/sslproxy_flags/", $settings["interception_checks"])) {
				//	$interception_checks .= "sslproxy_flags DONT_VERIFY_PEER\n";
				//}
				if ($settings["interception_adapt"] != "") {
					foreach (explode(",", $settings["interception_adapt"]) as $adapt) {
						$interception_checks .= "sslproxy_cert_adapt {$adapt} all\n";
					}
				}
			}
		}
	}
	$port = ($settings['proxy_port'] ? $settings['proxy_port'] : 3128);
	$ssl_port = ($settings['ssl_proxy_port'] ? $settings['ssl_proxy_port'] : 3127);

	// Read assigned interfaces
	$real_ifaces = array();

	if ($settings['active_interface']) {
		$proxy_ifaces = explode(",", $settings['active_interface']);
	} else {
		$proxy_ifaces = array("lan");
	}

	if ($settings['transparent_proxy'] == "on") {
		exec("sed -nr '/^own.*pf.*root:squid/,/^perm.*pf.*0640/p' /etc/devfs.conf", $perms);
		if (empty($perms)) {
			file_put_contents("/etc/devfs.conf", "\nown\tpf\troot:squid\nperm\tpf\t0640\n", FILE_APPEND);
			mwexec("/etc/rc.d/devfs restart");
		}
		$transparent_ifaces = explode(",", $settings['transparent_active_interface']);
		foreach ($transparent_ifaces as $t_iface) {
			$t_iface_ip = squid_get_real_interface_address($t_iface);
			if ($t_iface_ip[0]) {
				$real_ifaces[]=$t_iface_ip;
			}
		}
	} else {
		$transparent_ifaces = array();
	}

	if ($settings['ssl_proxy'] == "on") {
		$ssl_ifaces = explode(",", $settings['ssl_active_interface']);
		foreach ($ssl_ifaces as $s_iface) {
			$s_iface_ip = squid_get_real_interface_address($s_iface);
			if ($s_iface_ip[0]) {
				$real_ifaces[]=$s_iface_ip;
			}
		}
	} else {
		$ssl_ifaces = array();
	}

	// check all proxy interfaces selected
	foreach ($proxy_ifaces as $iface) {
		$iface_ip = squid_get_real_interface_address($iface);
		if ($iface_ip[0]) {
			// do not add loopback twice when transparent proxy is enabled
			if ($iface_ip[0] == "127.0.0.1" && $settings['transparent_proxy'] == "on") {
				continue;
			} else {
				$real_ifaces[] = $iface_ip;
				if (in_array($iface, $ssl_ifaces)) {
					$conf .= "http_port {$iface_ip[0]}:{$port} {$ssl_interception}\n";
				} else {
					$conf .= "http_port {$iface_ip[0]}:{$port}\n";
				}
			}
		}
	}

	if (($settings['transparent_proxy'] == 'on')) {
		if ($settings['ssl_proxy'] == "on" && count($ssl_ifaces) > 0) {
			$conf .= "http_port 127.0.0.1:{$port} intercept {$ssl_interception}\n";
			$conf .= "https_port 127.0.0.1:{$ssl_port} intercept {$ssl_interception}\n";
		} else {
			$conf .= "http_port {$port} intercept\n";
		}
	}
	$icp_port = ($settings['icp_port'] ? $settings['icp_port'] : 0);
	$dns_v4_first = ($settings['dns_v4_first'] == "on" ? "on" : "off");
	if (!is_dir("{$g['varrun_path']}/squid")) {
		mkdir("{$g['varrun_path']}/squid");
		squid_chown_recursive("{$g['varrun_path']}/squid", SQUID_UID, SQUID_GID);
	}
	$piddir = "{$g['varrun_path']}/squid";
	$pidfile = "{$piddir}/squid{$instance_id}.pid";
	if (!is_dir($piddir)) {
		safe_mkdir($piddir, 0755);
	}
	squid_chown_recursive($piddir, SQUID_UID, 'wheel');
	$language = ($settings['error_language'] ? $settings['error_language'] : 'en');
	$icondir = SQUID_CONFBASE . '/icons';
	$hostname = ($settings['visible_hostname'] ? $settings['visible_hostname'] : 'localhost');
	$email = ($settings['admin_email'] ? $settings['admin_email'] : 'admin@localhost');

	$logdir = ($settings['log_dir'] ? $settings['log_dir'] : '/var/squid/logs');
	if (!is_dir($logdir)) {
		safe_mkdir($logdir, 0755);
	}
	squid_chown_recursive($logdir, SQUID_UID, SQUID_GID);
	$logdir_cache = $logdir . "/cache{$instance_id}.log";
	$logdir_access = $settings['log_enabled'] == 'on' ? "access_log syslog:LOCAL2.INFO bp_log" : "access_log stdio:/dev/null";
	$pinger_helper = ($settings['disable_pinger']) =='off' ? 'on' : 'off';
	$pinger_program=SQUID_LOCALBASE."/libexec/squid/pinger";
	$squid_uid = SQUID_UID;
	$squid_gid = SQUID_GID;

	if ($settings['authsettings']['auth_method'] == 'entraid') {
		$logline = "logformat bp_log %ts %6tr %>a %Ss/%03>Hs %<st %rm %ru %{Referer}>h %[un %Sh/%<a %mt %{email}>h";
	} else {
		$logline = "logformat bp_log %ts %6tr %>a %Ss/%03>Hs %<st %rm %ru %{Referer}>h %[un %Sh/%<a %mt";
	}

	$conf .= <<< EOD
icp_port {$icp_port}
#dns_v4_first {$dns_v4_first}
pid_filename {$pidfile}
cache_effective_user {$squid_uid}
cache_effective_group {$squid_gid}
error_default_language {$language}
error_directory /usr/local/www/webfilter/squid_errors/{$language}
icon_directory {$icondir}
visible_hostname {$hostname}
cache_mgr {$email}
{$logline}
{$logdir_access}
cache_log {$logdir_cache}
cache_store_log none
netdb_filename {$logdir}/netdb.state
pinger_enable {$pinger_helper}
pinger_program {$pinger_program}
{$interception_checks}

EOD;

	// Per squid docs, setting logfile_rotate to 0 is safe and causes a simple close/reopen.
	$rotate = empty($settings['log_rotate']) ? 0 : $settings['log_rotate'];
	$conf .= "logfile_rotate {$rotate}\n";
	$conf .= "debug_options rotate={$rotate}\n";
	squid_install_cron(true);

	$conf .= <<< EOD
shutdown_lifetime 3 seconds

EOD;

	if ($settings['allow_interface'] == 'on') {
		$src = '';
		foreach ($real_ifaces as $iface) {
			list($ip, $mask) = $iface;
			$ip = long2ip(ip2long($ip) & ip2long($mask));
			$mask = 32 - log((ip2long($mask) ^ ip2long('255.255.255.255')) +1, 2);
			if (!preg_match("@$ip/$mask@", $src)) {
				// XXX: Do not add invalid subnets (Bug #4331, Bug #4526)
				if (is_subnet("{$ip}/{$mask}")) {
					$src .= " $ip/$mask";
				} else {
					log_error("[squid] 'Allow Users on Interface' ACL skipped for '{$ip}/{$mask}' since it is not a valid subnet.");
				}
			}
		}
		if (!empty($src)) {
			$conf .= "\n# Allow local network(s) on interface(s)\n";
			$conf .= "acl localnet src $src\n";
			$valid_acls[] = 'localnet';
		}
	}

	if ($settings['disable_xforward']) {
		$conf .= "forwarded_for delete\n";
	} else {
		// only used for first run
		$conf .= "forwarded_for on\n";
	}

	if ($settings['disable_via']) {
		$conf .= "via off\n";
	}

	if ($settings['disable_squidversion']) {
		$conf .= "httpd_suppress_version_string on\n";
	}

	if (!empty($settings['uri_whitespace'])) {
		$conf .= "uri_whitespace {$settings['uri_whitespace']}\n";
	} else {
		// only used for first run
		$conf .= "uri_whitespace strip\n";
	}

	if (!empty($settings['dns_nameservers'])) {
		$altdns = explode(";", ($settings['dns_nameservers']));
		$conf .= "dns_nameservers ";
		foreach ($altdns as $dnssrv) {
			$conf .= $dnssrv . " ";
		}
	}

    if(!empty($settings['disabled_multilogin'])) {
	$multilogin_ttl = $settings['disabled_multilogin_ttl'];
	$conf .= <<<EOF
\n# Multi User Login
authenticate_ip_ttl $multilogin_ttl minutes
acl multilogin max_user_ip -s 1
http_access deny multilogin\n
EOF;
    }

	return $conf;
}

/* Proxy Server: Cache Management configuration handler */
function squid_resync_cache($instance_id) {
	global $config, $g, $wf_instances;

	$instance_server = $wf_instances[$instance_id]['server'];

	if (is_array($config['system']['webfilter']['squidcache']))
		$settings = $config['system']['webfilter']['squidcache']['config'][0];
	else
		$settings = array();

	//apply cache settings
	$cachedir =($settings['harddisk_cache_location'] ? $settings['harddisk_cache_location'] . $instance_id : "/var/squid/cache{$instance_id}");
	$disk_cache_size = ($settings['harddisk_cache_size'] ? $settings['harddisk_cache_size'] : 100);
	$level1 = ($settings['level1_subdirs'] ? $settings['level1_subdirs'] : 16);
	$memory_cache_size = ($settings['memory_cache_size'] ? $settings['memory_cache_size'] : 8);
	$max_objsize = ($settings['maximum_object_size'] ? $settings['maximum_object_size']." KB" : "10 KB");
	$min_objsize = ($settings['minimum_object_size'] ? $settings['minimum_object_size'] : 0);
	$max_objsize_in_mem = ($settings['maximum_objsize_in_mem'] ? $settings['maximum_objsize_in_mem'] : 32);
	$cache_policy = ($settings['cache_replacement_policy'] ? $settings['cache_replacement_policy'] : 'heap LFUDA');
	$memory_policy = ($settings['memory_replacement_policy'] ? $settings['memory_replacement_policy'] : 'heap GDSF');
	$offline_mode = ($settings['enable_offline'] == 'on' ? 'on' : 'off');
	$conf = '';
	if (!isset($settings['harddisk_cache_system'])) {
		if ($g['platform'] == "nanobsd" || !is_array ($config['system']['webfilter']['squidcache']['config'])) {
			$disk_cache_system = 'null';
		} else {
			$disk_cache_system = 'ufs';
		}
	} else {
		$disk_cache_system = $settings['harddisk_cache_system'];
	}
	// 'null' storage type dropped. In-memory cache is always present. Remove all cache_dir options to prevent on-disk caching.
	if ($disk_cache_system != "null") {
		if (isset($instance_server['enable_cache']) && $instance_server['enable_cache'] == "on") {
			$cache_swap_state = "cache_swap_state {$settings['harddisk_log_location']}/swap.state{$instance_id}";
			$disk_cache_opts =	"cache_dir {$disk_cache_system} {$cachedir} {$disk_cache_size} {$level1} 256";
		}
	}
	//check dynamic content
	if (empty($settings['cache_dynamic_content'])) {
		$conf.='acl dynamic urlpath_regex cgi-bin \?'."\n";
		$conf.="cache deny dynamic\n";
	} else if (preg_match('/youtube/',$settings['refresh_patterns'])) {
		$conf.=<<< EOC
# Break HTTP standard for flash videos. Keep them in cache even if asked not to.
refresh_pattern -i \.flv$ 10080 90% 999999 ignore-no-cache override-expire ignore-private

# Let the clients favorite video site through with full caching
acl youtube dstdomain .youtube.com
cache allow youtube

EOC;
	}
	if (preg_match('/windows/',$settings['refresh_patterns'])) {
		$conf.=<<< EOC

# Windows Update refresh_pattern
range_offset_limit -1
refresh_pattern -i microsoft.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims
refresh_pattern -i windowsupdate.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims
refresh_pattern -i windows.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims

EOC;
	}

	if (preg_match('/symantec/',$settings['refresh_patterns'])) {
		$conf.=<<< EOC

# Symantec refresh_pattern
range_offset_limit -1
refresh_pattern liveupdate.symantecliveupdate.com/.*\.(cab|exe|dll|msi) 10080 100% 43200 reload-into-ims
refresh_pattern symantecliveupdate.com/.*\.(cab|exe|dll|msi) 10080 100% 43200 reload-into-ims

EOC;
	}
	if (preg_match('/avast/',$settings['refresh_patterns'])) {
		$conf.=<<< EOC

# Avast refresh_pattern
range_offset_limit -1
refresh_pattern avast.com/.*\.(vpu|cab|stamp|exe) 10080 100% 43200 reload-into-ims

EOC;
	}
	if (preg_match('/avira/',$settings['refresh_patterns'])) {
		$conf.=<<< EOC

# Avira refresh_pattern
range_offset_limit -1
refresh_pattern personal.avira-update.com/.*\.(cab|exe|dll|msi|gz) 10080 100% 43200 reload-into-ims

EOC;
	}
	$refresh_conf=<<< EOC

# Add any of your own refresh_pattern entries above these.
refresh_pattern ^ftp:    1440  20%  10080
refresh_pattern ^gopher:  1440  0%  1440
refresh_pattern -i (/cgi-bin/|\?) 0  0%  0
refresh_pattern .    0  20%  4320

EOC;

	if ($settings['custom_refresh_patterns'] !="")
		$conf .= sq_text_area_decode($settings['custom_refresh_patterns'])."\n";

	$conf .= <<< EOD

cache_mem $memory_cache_size MB
maximum_object_size_in_memory {$max_objsize_in_mem} KB
memory_replacement_policy {$memory_policy}
cache_replacement_policy {$cache_policy}
$cache_swap_state
$disk_cache_opts
minimum_object_size {$min_objsize} KB
maximum_object_size {$max_objsize}
offline_mode {$offline_mode}

EOD;

	if (!empty($settings['cache_swap_low'])) $conf .= "cache_swap_low {$settings['cache_swap_low']}\n";
	if (!empty($settings['cache_swap_high'])) $conf .= "cache_swap_high {$settings['cache_swap_high']}\n";

	$donotcache = sq_text_area_decode($settings['donotcache']);
	if (!empty($donotcache)) {
		file_put_contents(SQUID_ACLDIR . '/donotcache.acl', $donotcache);
		$conf .= 'acl donotcache dstdomain "' . SQUID_ACLDIR . "/donotcache.acl\"\n";
		$conf .= "cache deny donotcache\n";
	}
	elseif (file_exists(SQUID_ACLDIR . '/donotcache.acl')) {
		unlink(SQUID_ACLDIR . '/donotcache.acl');
	}
	$conf .= "cache allow all\n";

	return $conf.$refresh_conf;
}

/* Proxy Server: Remote Proxy Settings configuration handler */
function squid_resync_upstream() {
	global $config;

	if (!is_array($config['system']['webfilter']['squidremote']['config']))
		$config['system']['webfilter']['squidremote']['config'] = array();

	$conf = "\n#Remote proxies\n";
	foreach ($config['system']['webfilter']['squidremote']['config'] as $settings) {
		if ($settings['enable'] == 'on') {
			$conf .= "cache_peer {$settings['proxyaddr']} {$settings['hierarchy']} {$settings['proxyport']} ";
			if ($settings['icpport'] == '7')
				$conf .= "{$settings['icpport']} {$settings['icpoptions']} {$settings['peermethod']} {$settings['allowmiss']} ";
			else
				$conf .= "{$settings['icpport']} ";
			// auth settings
			if (!empty($settings['username']) && !empty($settings['password'])) {
				$conf .= " login={$settings['username']}:{$settings['password']}";
			} else {
				$conf .= "{$settings['authoption']} ";
			}
			// other options settings
			if (!empty($settings['weight']))
				$conf .= "weight={$settings['weight']} ";
			if (!empty($settings['basetime']))
				$conf .= "basetime={$settings['basetime']} ";
			if (!empty($settings['ttl']))
				$conf .= "ttl={$settings['ttl']} ";
			if (!empty($settings['nodelay']))
				$conf .= "no-delay";
		}
		$conf .= "\n";
	}
	return $conf;
}

function squid_resync_redirector($instance_id) {
	global $config, $wf_instances;

	if (!isset($wf_instances[$instance_id]) ||
		!isset($wf_instances[$instance_id]['nf_content_settings'])) {
		return;
	}
	$settings = $wf_instances[$instance_id]['server'];

	if (isset($settings['parent_rules']) && is_numeric($settings['parent_rules'])) {
		$netfilter_config = $wf_instances[$settings['parent_rules']]['nf_content_settings'];
		$rules_conf_file = NETFILTER_CONF_DIR . "/rules{$settings['parent_rules']}.conf";
	} else {
		$netfilter_config = $wf_instances[$instance_id]['nf_content_settings'];
		$rules_conf_file = NETFILTER_CONF_DIR . "/rules{$instance_id}.conf";
	}
	$redirector_conf_file = NETFILTER_CONF_DIR . "/redirector{$instance_id}.conf";

	if ($netfilter_config['enable'] != "on" || !file_exists($redirector_conf_file)) {
		return "# No redirector configured";
	}

	$conf = "# Configure redirector\n";
	$conf .= "url_rewrite_program /usr/local/bin/redirector -c {$redirector_conf_file} -r {$rules_conf_file}\n";
	if (!empty($netfilter_config['content_filter_processes']))
		$conf .= "url_rewrite_children " . $netfilter_config['content_filter_processes'] . "\n";

	if ($netfilter_config['enable_emergency'] == 'on')
		$conf .= "url_rewrite_bypass on";

	return $conf;
}

/* Proxy Server: Access Control configuration handler */
function squid_resync_nac($instance_id) {
	global $config, $valid_acls, $wf_instances;

	$settings = $wf_instances[$instance_id]['server'];
	$squidnac = array();
	if (isset($wf_instances[$instance_id]['squidnac'])) {
		$squidnac = $wf_instances[$instance_id]['squidnac'];
	}

	$webgui_port = $config['system']['webgui']['port'];
	$addtl_ports = $squidnac['addtl_ports'];
	$addtl_sslports = $squidnac['addtl_sslports'];
	$port = ($settings['proxy_port'] ? $settings['proxy_port'] : 3128);
	$ssl_port = ($settings['ssl_proxy_port'] ? $settings['ssl_proxy_port'] : 3127);
	$conf = <<< EOD

# Setup some default acls
# From 3.2 further configuration cleanups have been done to make things easier and safer. The manager, localhost, and to_localhost ACL definitions are now built-in.
# acl localhost src 127.0.0.1/32
acl allsrc src all
acl safeports port 21 70 80 210 280 443 488 563 591 631 777 901 $webgui_port $port $ssl_port 1025-65535 $addtl_ports
acl sslports port 443 563 $webgui_port $addtl_sslports

# From 3.2 further configuration cleanups have been done to make things easier and safer. The manager, localhost, and to_localhost ACL definitions are now built-in.
#acl manager proto cache_object

acl purge method PURGE
acl connect method CONNECT

# Define protocols used for redirects
acl HTTP proto HTTP
acl HTTPS proto HTTPS

EOD;

	$allowed_subnets = preg_replace("/\s+/"," ", base64_decode($squidnac['allowed_subnets']));
	if (!empty($allowed_subnets)) {
		$conf .= "acl allowed_subnets src $allowed_subnets\n";
		$valid_acls[] = 'allowed_subnets';
	}

	$options = array(
		'unrestricted_hosts' => 'src',
		'banned_hosts' => 'src',
		'whitelist' => 'dstdom_regex -i',
		'blacklist' => 'dstdom_regex -i',
		'block_user_agent' => 'browser -i',
		'block_reply_mime_type' => 'rep_mime_type -i',
	);

	foreach ($options as $option => $directive) {
		$contents = base64_decode($squidnac[$option]);
		if (!empty($contents)) {
			file_put_contents(SQUID_ACLDIR . "/$option.acl", $contents);
			$conf .= "acl $option $directive \"" . SQUID_ACLDIR . "/$option.acl\"\n";
			$valid_acls[] = $option;
		}
		elseif (file_exists(SQUID_ACLDIR . "/$option.acl")) {
			unlink(SQUID_ACLDIR . "/$option.acl");
		}
	}

	$conf .= <<< EOD
http_access allow manager localhost

EOD;

	if (is_array($config['system']['webfilter']['squidcache'])) {
		$settings_ch = $config['system']['webfilter']['squidcache']['config'][0];
		if (!empty($settings_ch['ext_cachemanager'])) {
			$extmgr = explode(";", ($settings_ch['ext_cachemanager']));
			$conf .= "\n# Allow external cache managers\n";
			foreach ($extmgr as $mgr) {
				$conf .= "acl ext_manager src {$mgr}\n";
			}
			$conf .= "http_access allow manager ext_manager\n";
		}
	}

	// If using parent rules
	$parent_instance_id = $instance_id;
	if (is_numeric($wf_instances[$instance_id]['server']['parent_rules'])) {
		$parent_instance_id = $wf_instances[$instance_id]['server']['parent_rules'];
	}

	if (isset($wf_instances[$parent_instance_id]['wf_block_ext']) &&
	    !empty($wf_instances[$parent_instance_id]['wf_block_ext'])) {
		$ext_files = get_ext_files();
		$wf_block_ext = $wf_instances[$parent_instance_id]['wf_block_ext'];
		$save_ext_file = array();

		if (isset($wf_block_ext['ext_group']) && !empty($wf_block_ext['ext_group'])) {
			foreach (explode(",", $wf_block_ext['ext_group']) as $group_ext){
				foreach($ext_files[$group_ext] as $ext => $descr){
					$save_ext_file[] = "\.{$ext}$\n";
				}
			}
		}

		if (isset($wf_block_ext['ext_single']) && !empty($wf_block_ext['ext_single'])) {
			foreach(explode(",", $wf_block_ext['ext_single']) as $ext)
				$save_ext_file[] = "\.{$ext}$\n";
		}

		if (!empty($save_ext_file))
			file_put_contents("/var/squid/acl/extensions.acl", $save_ext_file);
		else
			@unlink_if_exists("/var/squid/acl/extensions.acl");

		unset($save_ext_file);

		if (file_exists("/var/squid/acl/extensions.acl")){
			$conf .= <<<EOD

# Deny file extensions than was seted in the extensions.acl fileads_block_active
acl blockextensions urlpath_regex -i "/var/squid/acl/extensions.acl"
http_access deny blockextensions

EOD;
		}
	}
	$conf .= <<< EOD

http_access deny manager
http_access allow purge localhost
http_access deny purge
http_access deny !safeports
http_access deny CONNECT !sslports

# Always allow localhost connections
# From 3.2 further configuration cleanups have been done to make things easier and safer.
# The manager, localhost, and to_localhost ACL definitions are now built-in.
# http_access allow localhost

EOD;

	$urls_dataclick = array("dataclick.bluepex.com\n","webservice.bluepex.com\n");
	$webgui_port = $config['system']['webgui']['port'] != '' ? $config['system']['webgui']['port'] : '';
	$proxy_interfaces = explode(",", $wf_instances[$instance_id]['server']['active_interface']);
	foreach($proxy_interfaces as $p_if){
		$p_ip = get_interface_ip($p_if);
		$url_to_push = $webgui_port != '' ? "$p_ip:$webgui_port/dataclick_data_openvpn.php\n" : "$p_ip/dataclick_data_openvpn.php\n";
		array_push($urls_dataclick, $url_to_push);
	}
	@file_put_contents('/var/squid/acl/dataclick.acl', $urls_dataclick);
	$conf .= "\n#Add custom acl to allow allow access for dataclick's urls\n";
	$conf .= "acl AllowDataclickUrls url_regex -i \"/var/squid/acl/dataclick.acl\"\n";
	$conf .= "http_access allow AllowDataclickUrls\n";
	//Create acl bluepex_dstdom
	$conf .= "\n#Add acl bluepex_dstdom\n";
	$conf .= "acl bluepex_dstdom dstdom_regex -i \"/usr/local/etc/bluepex_dstdom.acl\"\n";
	$conf .= "http_access allow bluepex_dstdom\n";

	if (isset($wf_instances[$parent_instance_id]['ads_allowed']['ads_block_active']) &&
	    $wf_instances[$parent_instance_id]['ads_allowed']['ads_block_active'] == "on") {
		$conf .= <<<EOD

# Deny ads server
acl blockads urlpath_regex -i "/var/squid/acl/ads_server_list.acl"
http_access deny blockads

EOD;
	}

	return $conf;
}

/* Proxy server: Traffic Management configuration handler */
function squid_resync_traffic($instance_id) {
	global $config, $valid_acls, $wf_instances;

	if (!is_array($valid_acls))
		return;

	$settings = array();
	if (isset($wf_instances[$instance_id]['squidtraffic'])) {
		$settings = $wf_instances[$instance_id]['squidtraffic'];
	}

	$conf = '';

	if ($settings['enable_load_balance'] == "yes") {
		$lb_acls = "";
		$lb_addrs = "";
		foreach ($settings['lb_gateways']['item'] as $item) {
			$lb_acls .= "acl {$item['gateway_name']} random 0.{$item['weight']}\n";
			$lb_addrs .= "tcp_outgoing_address {$item['ip']} {$item['gateway_name']}\n";
		}
		$conf .= $lb_acls;
		$conf .= $lb_addrs;
		$conf .= "\n";
	}

	if (!empty($settings['quick_abort_min']) || ($settings['quick_abort_min']) == "0")
		$conf .= "quick_abort_min {$settings['quick_abort_min']} KB\n";
	if (!empty($settings['quick_abort_max']) || ($settings['quick_abort_max']) == "0")
		$conf .= "quick_abort_max {$settings['quick_abort_max']} KB\n";
	if (!empty($settings['quick_abort_pct']))
		$conf .= "quick_abort_pct {$settings['quick_abort_pct']}\n";

	$up_limit = ($settings['max_upload_size'] ? $settings['max_upload_size'] : 0);
	$down_limit = ($settings['max_download_size'] ? $settings['max_download_size'] : 0);
	$conf .= "request_body_max_size $up_limit KB\n";
	if ($down_limit != 0)
		$conf .= 'reply_body_max_size ' . $down_limit . " KB allsrc \n";

	// Only apply throttling past 10MB
	$threshold = 10485760;
	$overall = $settings['overall_throttling'];
	if (!isset($overall) || ($overall == 0))
		$overall = -1;
	else
		$overall *= 1024;
	$perhost = $settings['perhost_throttling'];
	if (!isset($perhost) || ($perhost == 0))
		$perhost = -1;
	else
		$perhost *= 1024;
	$conf .= <<< EOD
delay_pools 1
delay_class 1 2
delay_parameters 1 $overall/$overall $perhost/$perhost
delay_initial_bucket_level 100

EOD;

	if (!empty($settings['unrestricted_hosts'])) {
		foreach (array('unrestricted_hosts') as $item) {
			if (in_array($item, $valid_acls)) {
				$conf .= "# Do not throttle unrestricted hosts\n";
				$conf .= "delay_access 1 deny $item\n";
			}
		}
	}

	if ($settings['throttle_specific'] == 'on') {
		$exts = array();
		$binaries = 'bin,cab,sea,ar,arj,tar,tgz,gz,tbz,bz2,zip,7z,exe,com';
		$cdimages = 'iso,bin,mds,nrg,gho,bwt,b5t,pqi';
		$multimedia = 'aiff?,asf,avi,divx,mov,mp3,mp4,wmv,mpe?g,qt,ra?m';
		foreach (array(	'throttle_binaries' => $binaries,
				'throttle_cdimages' => $cdimages,
				'throttle_multimedia' => $multimedia) as $field => $set) {
			if ($settings[$field] == 'on')
				$exts = array_merge($exts, explode(",", $set));
		}

		foreach (explode(",", $settings['throttle_others']) as $ext) {
			if (!empty($ext))
				$exts[] = $ext;
		}

		$contents = '';
		foreach ($exts as $ext)
			$contents .= "\.$ext\$\n";
		file_put_contents(SQUID_ACLDIR . '/throttle_exts.acl', $contents);

		$conf .= "# Throttle extensions matched in the url\n";
		$conf .= "acl throttle_exts urlpath_regex -i \"" . SQUID_ACLDIR . "/throttle_exts.acl\"\n";
		$conf .= "delay_access 1 allow throttle_exts\n";
		$conf .= "delay_access 1 deny allsrc\n";
	} else {
		$conf .= "delay_access 1 allow allsrc\n";
	}

	return $conf;
}

function squid_get_server_certs() {
	global $config;
	$cert_arr = array();
	$cert_arr[] = array('refid' => 'none', 'descr' => 'none');
	foreach ($config['cert'] as $cert) {
		$cert_arr[] = array('refid' => $cert['refid'], 'descr' => $cert['descr']);
	}
	return $cert_arr;
}

/* Get list of certificates for SSL proxy */
function squid_resync_auth($instance_id) {
	global $config, $valid_acls, $wf_instances;

	if (isset($wf_instances[$instance_id]['server']['authsettings']['parent_sso'])) {
		$parent_sso_instance = $wf_instances[$instance_id]['server']['authsettings']['parent_sso'];
		if (empty($wf_instances[$parent_sso_instance]['server']['authsettings']) ||
		    $wf_instances[$parent_sso_instance]['server']['authsettings']['auth_method'] != "ntlm") {
			return;
		}
		$authsettings = $wf_instances[$wf_instances[$instance_id]['server']['authsettings']['parent_sso']]['server']['authsettings'];
	} else {
		$authsettings = $wf_instances[$instance_id]['server']['authsettings'];
	}

	$settingsconfig = $wf_instances[$instance_id]['server'];

	$settingsnac = array();
	if (isset($wf_instances[$instance_id]['squidnac'])) {
		$settingsnac = $wf_instances[$instance_id]['squidnac'];
	}

	$conf = '';

	// SSL interception acl options part 1
	if ($settingsconfig['ssl_proxy'] == "on" && ! empty($settingsnac['whitelist'])) {
		$conf .= "always_direct allow whitelist\n";
		$conf .= "ssl_bump none whitelist\n";
	}

	// Package integration
	if (!empty($settingsconfig['custom_options'])) {
		$co_preg[0]='/;/';
		$co_rep[0]="\n";
		$co_preg[1]="/redirect_program/";
		$co_rep[1]="url_rewrite_program";
		$co_preg[2]="/redirector_bypass/";
		$co_rep[2]="url_rewrite_bypass";
		$conf.="# Package Integration\n".preg_replace($co_preg,$co_rep,$settingsconfig['custom_options'])."\n\n";
	}

	// Custom User Options before authentication acls
	$conf .= "# Custom options before auth\n".sq_text_area_decode($settingsconfig['custom_options_squid3'])."\n\n";

	// Deny the banned guys before allowing the good guys
	if (!empty($settingsnac['banned_hosts'])) {
		if (squid_is_valid_acl('banned_hosts')) {
			$conf .= "# These hosts are banned\n";
			$conf .= "http_access deny banned_hosts\n";
		}
	}
	if (!empty($settingsnac['banned_macs'])) {
		if (squid_is_valid_acl('banned_macs')) {
			$conf .= "# These macs are banned\n";
			$conf .= "http_access deny banned_macs\n";
		}
	}

	// Unrestricted hosts take precedence over blacklist
	if (!empty($settingsnac['unrestricted_hosts'])) {
		if (squid_is_valid_acl('unrestricted_hosts') && $authsettings['unrestricted_auth']!= "on") {
			$conf .= "# These hosts do not have any restrictions\n";
			$conf .= "http_access allow unrestricted_hosts\n";
		}
	}
	if (!empty($settingsnac['unrestricted_macs'])) {
		if (squid_is_valid_acl('unrestricted_macs')) {
			$conf .= "# These hosts do not have any restrictions\n";
			$conf .= "http_access allow unrestricted_macs\n";
		}
	}

	// Whitelist and blacklist also take precedence over other allow rules
	if (!empty($settingsnac['whitelist'])) {
		if (squid_is_valid_acl('whitelist')) {
			$conf .= "# Always allow access to whitelist domains\n";
			$conf .= "http_access allow whitelist\n";
		}
	}
	if (!empty($settingsnac['blacklist'])) {
		if (squid_is_valid_acl('blacklist')) {
			$conf .= "# Block access to blacklist domains\n";
			$conf .= "http_access deny blacklist\n";
		}
	}
	if (!empty($settingsnac['block_user_agent'])) {
		if (squid_is_valid_acl('block_user_agent')) {
			$conf .= "# Block access with user agents and browsers\n";
			$conf .= "http_access deny block_user_agent\n";
		}
	}
	if (!empty($settingsnac['block_reply_mime_type'])) {
		if (squid_is_valid_acl('block_reply_mime_type')) {
			$conf .= "# Block access with mime type in the reply\n";
			$conf .= "http_reply_access deny block_reply_mime_type\n";
		}
	}

	// Include squidguard denied acl log in squid
	if ($settingsconfig['log_sqd'])
		$conf .= "acl sglog url_regex -i sgr=ACCESSDENIED\n";

	$transparent_proxy = ($settingsconfig['transparent_proxy'] == 'on');
	if ($transparent_proxy) {
		if (preg_match ("/(none|cp|kerberos|entraid)/", $authsettings['auth_method']))
			$auth_method = $authsettings['auth_method'];
		else
			$auth_method="none";
	} else {
		$auth_method = $authsettings['auth_method'];
	}

	if ($auth_method == 'cp') {
		$conf .= "external_acl_type bp_captive ttl=60 children-startup=5 children-max=10 %SRC /usr/local/bin/check_captive_portal_user.py\n";
		$conf .= "acl existing_users external bp_captive\n";
		$conf .= "http_access allow existing_users\n";
		$conf .= "\n";
	}

	if ($auth_method == 'kerberos') {
		$processes = ($authsettings['auth_processes'] ? $authsettings['auth_processes'] : 10);
		$keep_alive = (isset($authsettings['keep_alive']) && $authsettings['keep_alive'] == "off") ? "off" : "on";
		$conf .= "auth_param negotiate program /usr/local/libexec/squid/negotiate_kerberos_auth -r -d -i\n";
		$conf .= "auth_param negotiate children $processes\n";
		$conf .= "auth_param negotiate keep_alive $keep_alive\n";
		$conf .= "acl authenticated_user proxy_auth REQUIRED\n";
		$conf .= "http_access deny !authenticated_user\n";
		$conf .= "\n";

		squid_kerberos_conf($instance_id);
	}

	// Allow the remaining ACLs if no authentication is set
	if ($auth_method == 'none' || $auth_method == 'cp' || $auth_method == 'kerberos') {
		// Include squidguard denied acl log in squid
		if ($settingsconfig['log_sqd'])
			$conf .="http_access deny sglog\n";
	}

	if ($auth_method == 'entraid') {
		mwexec("/bin/pkill -9 -af squid");

		$settings = $wf_instances[$instance_id]['server'];
		$ssl_ifaces = explode(",", $settings['active_interface']);
		foreach ($ssl_ifaces as $s_iface) {
			$s_iface_ip = squid_get_real_interface_address($s_iface);
		}

		/* if port is empty lets rely on the protocol selection */
		$port = "";
		if (isset($config['system']['webgui']['port']) &&
		    !empty($config['system']['webgui']['port'])) {
			$port = $config['system']['webgui']['port'];
		}
		if ($port == "") {
			$port = ($config['system']['webgui']['protocol'] == "http") ? 80 : 443;
		}

		$url_utm = "https://".$s_iface_ip[0].":".$port."/";

		$extip = $wf_instances[$instance_id]['server']['entra_id_extip'];

		$blocks = [];
		foreach (explode("\n", trim(file_get_contents("/usr/local/etc/netfilter/blacklist{$instance_id}.acl"))) as $values) {
			$blocks[] = trim($values);
		}
		$blocks = implode(" ", array_filter(array_unique($blocks)));

		$conf .= "acl permittedext dst ".$s_iface_ip[0]." \n";
		$conf .= "acl permittedext2 dstdomain {$extip}\n";
		$conf .= "acl url_azure dstdomain login.microsoftonline.com aadcdn.msftauth.net microsoftonline.com account.activedirectory.windowsazure.com mysignins.microsoft.com\n";
		$conf .= "http_access allow permittedext\n";
		$conf .= "http_access allow permittedext2\n";
		$conf .= "http_access allow url_azure\n";
		$conf .= "\n";
		$conf .= "acl block dstdomain ".$blocks." \n";
		$conf .= "\n";
		$conf .= "# Error page for users who do not authenticate\n";
		$conf .= "deny_info 307:https://".$s_iface_ip[0].":".$port."/url_blocked/ block\n";
		$conf .= "\n";
		$conf .= "# Allow only the HTTP replies that have been verified against the database with the helper\n";
		$conf .= "http_reply_access allow !block\n";
		$conf .= "deny_info 307:https://".$s_iface_ip[0].":".$port."/url_blocked/ helper_verified\n";
		$conf .= "\n";
		$conf .= "external_acl_type check_auth ttl=5 %SRC /usr/local/bin/check_auth.py\n";
		$conf .= "acl authenticated external check_auth\n";
		$conf .= "deny_info https://".$s_iface_ip[0].":".$port."/entraid_saml/ authenticated\n";
		$conf .= "http_access deny !authenticated\n";
		$conf .= "\n";
		$conf .= "# Cache configuration for better performance\n";
		$conf .= "auth_param basic credentialsttl 6 hours\n";
		$conf .= "auth_param basic casesensitive off\n";
		$conf .= "authenticate_cache_garbage_interval 3 hour\n";
		$conf .= "\n";
		$conf .= "# Allow access only if authentication is successful\n";
		$conf .= "http_access allow authenticated\n";
		$conf .= "\n";

		if (!file_exists('/var/log/entra_id.log')) {
			file_put_contents('/var/log/entra_id.log', '');
		}

		chmod('/var/log/entra_id.log', 0666);
	}

	if ($auth_method == 'kerberos') {
		mwexec("/bin/pkill -9 -af squid");

		$settings = $wf_instances[$instance_id]['server'];
		$ssl_ifaces = explode(",", $settings['ssl_active_interface']);
		foreach ($ssl_ifaces as $s_iface) {
			$s_iface_ip = squid_get_real_interface_address($s_iface);
		}

		/* if port is empty lets rely on the protocol selection */
		$port = "";
		if (isset($config['system']['webgui']['port']) &&
		    !empty($config['system']['webgui']['port'])) {
			$port = $config['system']['webgui']['port'];
		}
		if ($port == "") {
			$port = ($config['system']['webgui']['protocol'] == "http") ? 80 : 443;
		}

		$url_utm = "https://".$s_iface_ip[0].":".$port."/";

		$extip = "";
		if (isset($wf_instances[$instance_id]['server']['entra_id_extip']) &&
		    !empty($wf_instances[$instance_id]['server']['entra_id_extip'])) {
			$extip = $wf_instances[$instance_id]['server']['entra_id_extip'];
		}

		$blocks = [];
		foreach (explode("\n", trim(file_get_contents("/usr/local/etc/netfilter/blacklist{$instance_id}.acl"))) as $values) {
			$blocks[] = trim($values);
		}
		$blocks = implode(" ", array_filter(array_unique($blocks)));

		$conf .= "#Block Blacklist Redirect\n";
		$conf .= "acl block dstdomain ".$blocks." \n";
		$conf .= "\n";
		$conf .= "# Error page for users who do not authenticate\n";
		$conf .= "deny_info 307:https://".$s_iface_ip[0].":".$port."/url_blocked/ block\n";
		$conf .= "\n";
		$conf .= "# Allow only the HTTP replies that have been verified against the database with the helper\n";
		$conf .= "http_reply_access allow !block\n";
		$conf .= "deny_info 307:https://".$s_iface_ip[0].":".$port."/url_blocked/ helper_verified\n";
		$conf .= "\n";
	}

	if ($auth_method == 'cp') {
		// Include squidguard denied acl log in squid
		if ($settingsconfig['log_sqd'])
			$conf .="http_access deny sglog\n";
	} else if ($auth_method == 'none'  ) {
		// SSL interception acl options part 2 without authentication
		if ($settingsconfig['ssl_proxy'] == "on") {
			$conf .= "always_direct allow all\n";
			$conf .= "ssl_bump server-first all\n";
		}
		$conf .="# Setup allowed acls\n";
		$allowed = array('allowed_subnets');
		if ($settingsconfig['allow_interface'] == 'on') {
			$conf .= "# Allow local network(s) on interface(s)\n";
			$allowed[] = "localnet";
		}
		$allowed = array_filter($allowed, 'squid_is_valid_acl');
		foreach ($allowed as $acl)
			$conf .= "http_access allow $acl\n";
	} else if ($auth_method == 'kerberos') {
		// SSL interception acl options part 2 without authentication
		if ($settingsconfig['ssl_proxy'] == "on") {
			$conf .= "always_direct allow all\n";
			$conf .= "ssl_bump server-first all\n";
		}
		$conf .="# Setup allowed acls\n";
		$allowed = array('allowed_subnets');
		if ($settingsconfig['allow_interface'] == 'on') {
			$conf .= "# Allow local network(s) on interface(s)\n";
			$allowed[] = "localnet";
		}
		$allowed = array_filter($allowed, 'squid_is_valid_acl');
		foreach ($allowed as $acl)
			$conf .= "http_access allow $acl\n";
	} else {
		$noauth = implode(' ', explode("\n", base64_decode($authsettings['no_auth_hosts'])));
		if (!empty($noauth)) {
			$conf .= "acl noauth src $noauth\n";
			$valid_acls[] = 'noauth';
		}

		// Set up the external authentication programs
		$auth_ttl = ($authsettings['auth_ttl'] ? $authsettings['auth_ttl'] : 1440);
		$processes = ($authsettings['auth_processes'] ? $authsettings['auth_processes'] : 5);
		$keep_alive = (isset($authsettings['keep_alive']) && $authsettings['keep_alive'] == "off") ? "off" : "on";
		$prompt = ($authsettings['auth_prompt'] ? $authsettings['auth_prompt'] : 'Please enter your credentials to access the proxy');
		$auth_method = $authsettings['auth_method'];
		switch ($auth_method) {
			case 'usermanager':
				$conf .= "auth_param basic program /usr/local/bin/webfilter_auth.php\n";
				break;
			case 'ntlm':
				$conf .= <<<EOF
auth_param ntlm program /usr/local/bin/ntlm_auth --helper-protocol=squid-2.5-ntlmssp
auth_param ntlm children $processes
auth_param ntlm keep_alive $keep_alive
auth_param basic program /usr/local/bin/ntlm_auth --helper-protocol=squid-2.5-basic
authenticate_cache_garbage_interval 1 hour\n
EOF;
				if (isset($authsettings['sso_parent'])) {
					squid_resync_ntlm($authsettings['sso_parent']);
				} else {
					squid_resync_ntlm($instance_id);
				}
				break;
			case 'entraid':
				$conf .= "auth_param basic program /usr/local/bin/python3.8 /usr/local/bin/entraid.py\n";
				break;
			default:
				break;
		}

		if (!in_array($auth_method, ["cp", "entraid", "kerberos", "none"])) {
		$conf .= <<< EOD
auth_param basic children $processes
auth_param basic realm $prompt
auth_param basic credentialsttl $auth_ttl minutes
acl password proxy_auth REQUIRED

EOD;
}

		// Custom User Options after authentication definition
		$conf .= "# Custom options after auth\n".sq_text_area_decode($settingsconfig['custom_options2_squid3'])."\n\n";

		// SSL interception acl options part 2
		if ($settingsconfig['ssl_proxy'] == "on") {
			$conf .= "always_direct allow all\n";
			$conf .= "ssl_bump server-first all\n";
		}

		// Onto the ACLs
		$password = array('localnet', 'allowed_subnets');
		$passwordless = array('unrestricted_hosts');
		if ($authsettings['unrestricted_auth'] == 'on') {
			// Even the unrestricted hosts should authenticate
			$password = array_merge($password, $passwordless);
			$passwordless = array();
		}
		$passwordless[] = 'noauth';
		$password = array_filter($password, 'squid_is_valid_acl');
		$passwordless = array_filter($passwordless, 'squid_is_valid_acl');

		// Allow the ACLs that don't need to authenticate
		foreach ($passwordless as $acl)
			$conf .= "http_access allow $acl\n";

		// Include squidguard denied acl log in squid
		if ($settingsconfig['log_sqd'])
			$conf .="http_access deny password sglog\n";

		// Allow the other ACLs as long as they authenticate
		if (!in_array($auth_method, ["cp", "entraid", "kerberos", "none"])) {
			foreach ($password as $acl)
				$conf .= "http_access allow password $acl\n";
		}
	}

	$conf .= "# Default block all to be sure\n";
	$conf .= "http_access deny allsrc\n";

	squid_generate_rules("nat");

	return $conf;
}

function squid_resync_nf_users () {
	global $config;

	$users = $config['system']['webfilter']['squidusers']['config'];
	$nf_users = $config['system']['webfilter']["nf_users_users"]["element0"]["item"];

	if (is_array($users)) {
		$nf_clean_users = array();
		foreach ($nf_users as $nf_user)
			if ( !isset( $nf_user["is_squid_user"] ) )
				$nf_clean_users[] = $nf_user;
		$config['system']['webfilter']["nf_users_users"]["element0"]["item"] = $nf_clean_users;

		foreach ($users as $user)
			$config['system']['webfilter']["nf_users_users"]["element0"]["item"][] =
			array(
				"username"          => $user['username'],
				"description"       => $user['username'],
				"cg2_save_button"   => "cg2_save_button",
				"cg2_cancel_button" => true,
				"is_squid_user"     => true,
				);

	}elseif(($config['system']['webfilter']['webfilter']['config'][0]['authsettings']['auth_method'] == 'local') & ($users == "")) {
		for($cont=0; $cont < count($config['system']['webfilter']["nf_users_users"]["element0"]); $cont++) {
			unset($config['system']['webfilter']["nf_users_users"]["element0"]["item"][$cont]);
		}
	}
	write_config("Applied squid resync nf to users changes");
}

/* Proxy server: Local users configuration handler */
function squid_resync_users() {
	global $config;

	$users = $config['system']['webfilter']['squidusers']['config'];
	$contents = '';
	if (is_array($users)) {
		foreach ($users as $user)
			$contents .= $user['username'] . ':' . crypt($user['password'], base64_encode($user['password'])) . "\n";
	}
	file_put_contents(SQUID_PASSWD, $contents);
	chown(SQUID_PASSWD, SQUID_UID);
	chmod(SQUID_PASSWD, 0600);
}

function squid_resync_ntlm($instance_id) {
	global $config, $wf_instances;

	$settings = array();
	if (is_array($wf_instances[$instance_id]['server']['authsettings'])) {
		$settings = $wf_instances[$instance_id]['server']['authsettings'];
	}

	$authserver = $settings['auth_server'];
	$ntdomain = $settings['auth_ntdomain'];
	$username = $settings['ntlm_user'];
	$password = $settings['ntlm_password'];
	$nameserver = $settings['name_server'];
	$config['system']['timeservers'] = $authserver;
	write_config("Applied squid resync ntlm changes");

	$krbldomain = strtolower($ntdomain);
	$krbudomain = strtoupper($ntdomain);
	$krbconf = <<<EOF
[libdefaults]
	default_realm = $krbudomain
	clockskew = 300
	dns_lookup_kdc = no
	dns_lookup_realm = no
	ticket_lifetime = 24h

[realms]
	$krbudomain = {
		kdc = $nameserver
		default_domain = $krbldomain
		admin_server = $nameserver
	}
[logging]
	kdc = FILE:/var/log/samba4/krb5kdc.log
	admin_server = FILE:/var/log/samba4/kadmind.log
	default = SYSLOG:LOG_DAEMON

[domain_realm]
	.$krbldomain = $krbudomain

[appdefaults]
pam = {
	ticket_lifetime = 1d
	renew_lifetime = 1d
	forwardable = true
	proxiable = false
	retain_after_close = false
	minimum_uid = 1
}
EOF;

	file_put_contents("/etc/krb5.conf",$krbconf);
	chown("/etc/krb5.conf", 'squid');
	chmod("/etc/krb5.conf", 0600);

	$password = str_replace(array("$", "\"", "'", "(", ")"), array("\\$", "\\\"", "\\'", "\\(", "\\)"), $password);
	mwexec("echo {$password} | /usr/local/bin/kinit -a {$username}@{$krbudomain}");

	$workgroup = $settings['auth_workgroup'];
	$netbiosname = $config['system']['hostname'];

	$samba_logdir = "/var/log/samba4";
	$samba_piddir = "/var/run/samba4";
	$samba4_rundir = "/var/run/samba4";

	$auth_idmap_uid = !empty($settings['auth_idmap_uid']) ? $settings['auth_idmap_uid'] : "10000-20000";

	if(!is_dir($samba_logdir)) mkdir($samba_logdir,0755,true);
	if(!is_dir($samba_piddir)) mkdir($samba_piddir,0755,true);
	if(!is_dir($samba4_rundir)) mkdir($samba4_rundir,0755,true);

	mwexec("chown -R root:squid /var/db/samba4/");

	$log_detail = !empty($settings['log_detail']) ? "log level = all:{$settings['log_detail']}" : "";

	$smb4_file = "/usr/local/etc/smb4.conf";
	$sha256_smb4_old_file = (file_exists($smb4_file)) ? hash_file('sha256', $smb4_file) : "";

	$samba_conf = <<<EOF
[global]
	workgroup = $workgroup
	netbios name = $netbiosname
	server string = PROXY SERVER
	load printers = no
	log file = $samba_logdir/log.%m
	pid directory = $samba_piddir
	max log size = 500
	realm = $ntdomain
	security = ads
	auth methods = winbind
	encrypt passwords = yes
	winbind separator = |
	winbind cache time = 300
	winbind nss info = rfc2307
	winbind trusted domains only = no
	winbind enum users = yes
	winbind enum groups = yes
	winbind use default domain = yes
	winbind offline logon = false
	idmap config *:backend = tdb
	idmap config * : range = $auth_idmap_uid
	local master = no
	os level = 233
	domain master = no
	preferred master = no
	domain logons = no
	wins server = $authserver
	wins support = no
	dns proxy = no
	ldap ssl = no
	server signing = auto
	client signing = auto
	$log_detail
EOF;

	file_put_contents($smb4_file, $samba_conf);
	chown($smb4_file, 'squid');
	chmod($smb4_file, 0600);

	if (!file_exists("/etc/rc.conf.local"))
		mwexec("/usr/bin/touch /etc/rc.conf.local");

	$rcconf = file_get_contents("/etc/rc.conf.local");
	if(!preg_match("/samba_server_enable/",$rcconf))
		file_put_contents("/etc/rc.conf.local","samba_server_enable=\"YES\"\n",FILE_APPEND);
	if(!preg_match("/winbindd_enable/",$rcconf))
		file_put_contents("/etc/rc.conf.local","winbindd_enable=\"YES\"\n",FILE_APPEND);
	if(!preg_match("/smbd_enable/",$rcconf))
		file_put_contents("/etc/rc.conf.local","smbd_enable=\"YES\"\n",FILE_APPEND);
	if(!preg_match("/nmbd_enable/",$rcconf))
		file_put_contents("/etc/rc.conf.local","nmbd_enable=\"YES\"\n",FILE_APPEND);

	// Cleanup old option
	if(preg_match("/winbind_enable/",$rcconf))
		mwexec("/usr/bin/sed -i '' -e '/winbind_enable/d' /etc/rc.conf.local", true);

	$name_server = strtolower($settings['name_server']);

	$add_host_dns = array(
		'host' => $name_server,
		'domain' => $krbldomain,
		'ip' => $authserver,
		'descr' => $krbudomain,
		'aliases' => ''
		);

	if(is_array($config['unbound']) && isset($config['unbound']['enable'])){
		if(is_array($config['unbound']['hosts'])){
			$host_added = false;
			foreach($config['unbound']['hosts'] as $idx => &$unbound_host){
				if($unbound_host['host'] == $add_host_dns['host']){
					$config['unbound']['hosts'][$idx] = $add_host_dns;
					$host_added = true;
					break;
				}
			}
			if(!$host_added)
				$config['unbound']['hosts'][] = $add_host_dns;
		} else {
			$config['unbound']['hosts'] = array($add_host_dns);
		}
		write_config(dgettext("BluePexWebFilter","Save the name server for Single SignOn"));
	} elseif(is_array($config['dnsmasq']) && isset($config['dnsmasq']['enable'])){
		if(is_array($config['dnsmasq']['hosts'])){
			$host_added = false;
			foreach($config['dnsmasq']['hosts'] as $idx => $dnsmasq_host){
				if($dnsmasq_host['host'] == $add_host_dns['host']){
					$config['dnsmasq']['hosts'][$idx] = $add_host_dns;
					$host_added = true;
					break;
				}
			}
			if(!$host_added)
				$config['dnsmasq']['hosts'][] = $add_host_dns;
		} else {
			$config['dnsmasq']['hosts'] = array($add_host_dns);
		}
		write_config(dgettext("BluePexWebFilter","Save the name server for Single SignOn"));
	}

	system_hosts_generate();

	mwexec("/usr/local/bin/net ads join -S {$name_server} -U {$username}%{$password}");
	exec("/usr/local/etc/rc.d/samba_server onestatus", $out, $err);
	$sha256_smb4_new_file = (file_exists($smb4_file)) ? hash_file('sha256', $smb4_file) : "";
	$parameter_execute = (!empty($err) || (!empty($sha256_smb4_old_file) && ($sha256_smb4_old_file != $sha256_smb4_new_file))) ? "restart" : "reload";
	mwexec("/usr/local/etc/rc.d/samba_server {$parameter_execute}");
}

function squid_kerberos_conf($instance_id) {
	global $config, $wf_instances;

	$settings = array();
	if (is_array($wf_instances[$instance_id]['server']['authsettings'])) {
		$settings = $wf_instances[$instance_id]['server']['authsettings'];
	}

	$ntdomain = $settings['domain_auth'];
	$nameserver = $settings['hostname_ad'].".".$ntdomain;

	write_config("Applied squid resync ntlm changes");

	$krbldomain = strtolower($ntdomain);
	$krbudomain = strtoupper($ntdomain);
	$krbconf = <<<EOF
[libdefaults]
	default_realm = $krbudomain
	clockskew = 300
	dns_lookup_kdc = no
	dns_lookup_realm = no
	ticket_lifetime = 24h
	default_keytab_name = /etc/krb5.keytab
[realms]
	$krbudomain = {
		kdc = $nameserver
		default_domain = $krbldomain
		admin_server = $nameserver
	}
[logging]
	kdc = FILE:/var/log/samba4/krb5kdc.log
	admin_server = FILE:/var/log/samba4/kadmind.log
	default = SYSLOG:LOG_DAEMON

[domain_realm]
	.$krbldomain = $krbudomain

[appdefaults]
pam = {
	ticket_lifetime = 1d
	renew_lifetime = 1d
	forwardable = true
	proxiable = false
	retain_after_close = false
	minimum_uid = 1
}
EOF;

	file_put_contents("/etc/krb5.conf",$krbconf);
	chown("/etc/krb5.conf", 'squid');
	chmod("/etc/krb5.conf", 0600);
}

/* Wrapper function to sync whole Squid configuration */
function squid_resync($resync_instance_id = "", $via_rpc = "no") {
	global $config, $wf_instances;

	if (empty($wf_instances)) {
		return;
	}

	if (is_process_running_match("wf_monitor")) {
		mwexec("/bin/pkill -9 -af wf_monitor");
		mwexec_bg("/usr/local/bin/wf_monitor.py");
	}

	// resync update squid-erros pages url's protocol and ip
	squid_configure_error_pages();

	// detect boot process
	if (is_array($_POST)) {
		if (!platform_booting()) {
			unset($boot_process);
		} else {
			$boot_process = "on";
		}
	}

	log_error(dgettext("BluePexWebFilter", "[Squid] - Squid_resync function call pr:".is_process_running('squid')." bp:".isset($boot_process)." rpc:".$via_rpc));

	if (is_process_running('squid') && isset($boot_process) && $via_rpc == "no") {
		return;
	}

	conf_mount_rw();

	// Fix user/group entry permissions
	squid_fixup_user();

	foreach (array(SQUID_CONFBASE, SQUID_ACLDIR, SQUID_SSL_DB) as $dir) {
		safe_mkdir($dir, 0755);
		squid_chown_recursive($dir, SQUID_UID, SQUID_GID);
	}

	$instances_av_enabled = array();
	if (isset($config['system']['webfilter']['squidantivirus']['config'][0]['enable']) &&
	    $config['system']['webfilter']['squidantivirus']['config'][0]['enable'] == "on") {
		$instances_av_enabled = explode(",", $config['system']['webfilter']['squidantivirus']['config'][0]['enable']['instances']);
	}

	foreach ($wf_instances as $instance_id => $instance_config) {
		if (is_numeric($resync_instance_id) && $resync_instance_id != $instance_id) {
			continue;
		}
		$conf = squid_resync_general($instance_id) . "\n";
		$conf .= squid_resync_cache($instance_id) . "\n";
		$conf .= squid_resync_redirector($instance_id) . "\n";
		//$conf .= squid_resync_upstream() . "\n";
		$conf .= squid_resync_nac($instance_id) . "\n";
		$conf .= squid_resync_traffic($instance_id) . "\n";
		$conf .= squid_resync_reverse() . "\n";
		$conf .= squid_resync_auth($instance_id) . "\n";

		if (in_array($instance_id, $instances_av_enabled)) {
			$conf .= squid_resync_antivirus();
		}

		// XMLRPC Sync Webfilter
		if (!isset($boot_process) || $via_rpc == "yes") {
			webfilter_sync_on_changes();
		}

		// write config file
		$squid_conf_file = "squid{$instance_id}.conf";
		file_put_contents(SQUID_CONFBASE . "/{$squid_conf_file}", $conf);

		$log_dir="";
		// check if squid is enabled
		if (squid_enabled($instance_id)) {
			$log_dir = $instance_config['server']['log_dir'].'/';
		}
		// check if squidreverse is enabled
		else if (is_array($config['system']['webfilter']['squidreversegeneral']['config'])) {
			if ($config['system']['webfilter']['squidreversegeneral']['config'][0]['reverse_interface'] != "")
				$log_dir="/var/squid/logs/";
		}
		// do not start squid if there is no log dir
		if ($log_dir != "") {
			if (!is_dir($log_dir)) {
				log_error(dgettext("BluePexWebFilter", "Creating squid log dir $log_dir"));
				@mkdir($log_dir, 0755, true);
				squid_chown_recursive($log_dir, SQUID_UID, SQUID_GID);
			}
		}

		squid_dash_z($instance_id);

		// restart Squid if enabled and reconfigure filter
		squid_restart_services($instance_id);
	}

	// Resync Antivirus files
	squid_resync_antivirus_files();

	/* make sure pinger is executable and suid root */
	// XXX: Bug #5114
	if (file_exists(SQUID_LOCALBASE . "/libexec/squid/pinger")) {
		chgrp(SQUID_LOCALBASE . "/libexec/squid/pinger", SQUID_GID);
	}

	filter_configure();
	conf_mount_ro();
}

/*
 * Squid firewall rules configuration
 */

function squid_generate_rules($type) {
	global $config, $wf_instances;

	if (!isset($config['system']['webfilter']['instance']['config']) ||
	    !is_array($config['system']['webfilter']['instance']['config'])) {
		return;
	}
	$wf_instances = $config['system']['webfilter']['instance']['config'];
	foreach ($wf_instances as $instance_id => $instance_config) {

		$squid_conf = $instance_config['server'];

		// do not install any firewall rules if Squid is disabled or used as reverse proxy only
		if (!squid_enabled($instance_id)) {
			log_error(dgettext("BluePexWebFilter", "[squid] Installed but disabled. Not installing '{$type}' rules."));
			continue;
		} elseif (empty($squid_conf['active_interface'])) {
			log_error(dgettext("BluePexWebFilter", "[squid] Configured as reverse proxy only. Not installing '{$type}' rules."));
			continue;
		}

		//check captive portal option
		$cp_file='/etc/inc/captiveportal.inc';
		$pfsense_version=preg_replace("/\s/","",file_get_contents("/etc/version"));
		$port = ($settings['proxy_port'] ? $settings['proxy_port'] : 3128);
		$cp_inc = file($cp_file);
		$new_cp_inc="";
		$found_rule=0;
		foreach ($cp_inc as $line) {
			$new_line=$line;
			//remove applied squid patch
			if (preg_match('/skipto 65314 ip/',$line)) {
				$found_rule++;
				$new_line ="";
			}

			if (substr($pfsense_version,0,3) > 2.0) {
				if (preg_match('/255.255.255.255/',$line) && $squid_conf['patch_cp']) {
					$found_rule++;
					$new_line .= "\t".'$cprules .= "add {$rulenum} skipto 65314 ip from any to {$ips} '.$port.' in\n";'."\n";
					$new_line .= "\t".'$cprules .= "add {$rulenum} skipto 65314 ip from {$ips} '.$port.' to any out\n";'."\n";
				}
			} else {
				//add squid patch option based on current config
				if (preg_match('/set 1 pass ip from any to/',$line) && $squid_conf['patch_cp']) {
					$found_rule++;
					$new_line = "\t".'$cprules .= "add {$rulenum} set 1 skipto 65314 ip from any to {$ips} '.$port.' in\n";'."\n";
					$new_line .= $line;
				}
				if (preg_match('/set 1 pass ip from {/',$line) && $squid_conf['patch_cp']) {
					$found_rule++;
					$new_line = "\t".'$cprules .= "add {$rulenum} set 1 skipto 65314 ip from {$ips} '.$port.' to any out\n";'."\n";
					$new_line .= $line;
				}
			}
			$new_cp_inc .= $new_line;
		}
		if (!file_exists('/root/'.$pfsense_version.'.captiveportal.inc.backup')) {
			copy ($cp_file,'/root/'.$pfsense_version.'.captiveportal.inc.backup');
		}
		if ($found_rule > 0) {
			file_put_contents($cp_file,$new_cp_inc, LOCK_EX);
		}

		//normal squid rule check
		if (($squid_conf['transparent_proxy'] != 'on') || ($squid_conf['allow_interface'] != 'on')) {
			continue;
		}

		if (!is_service_running('squid')) {
			log_error(dgettext("BluePexWebFilter", "[squid] Installed but not started.  Not installing \"{$type}\" rules."));
			continue;
		}
		// Read assigned interfaces
		$proxy_ifaces = explode(",", $squid_conf['active_interface']);
		$proxy_ifaces = array_map('convert_friendly_interface_to_real_interface_name', $proxy_ifaces);
		if ($squid_conf['transparent_proxy'] == "on") {
			$transparent_ifaces = explode(",", $squid_conf['transparent_active_interface']);
			$transparent_ifaces = array_map('convert_friendly_interface_to_real_interface_name', $transparent_ifaces);
		} else {
			$transparent_ifaces = array();
		}
		if ($squid_conf['ssl_proxy'] == "on") {
			$ssl_ifaces = explode(",", $squid_conf['ssl_active_interface']);
			$ssl_ifaces = array_map('convert_friendly_interface_to_real_interface_name', $ssl_ifaces);
		} else {
			$ssl_ifaces=array();
		}

		$port = ($squid_conf['proxy_port'] ? $squid_conf['proxy_port'] : 3128);
		$ssl_port = ($squid_conf['ssl_proxy_port'] ? $squid_conf['ssl_proxy_port'] : 3127);

		$fw_aliases = filter_generate_aliases();
		if (strstr($fw_aliases, "pptp =")) {
			$PPTP_ALIAS = "\$pptp";
		} else {
			$PPTP_ALIAS = "\$PPTP";
		}
		if (strstr($fw_aliases, "PPPoE =")) {
			$PPPOE_ALIAS = "\$PPPoE";
		} else {
			$PPPOE_ALIAS = "\$pppoe";
		}

		// define ports based on transparent options and ssl filtering
		$pf_rule_port = ($squid_conf['ssl_proxy'] == "on" ? "{80,443}" : "80");
		switch($type) {
		case 'nat':
			$rules .= "\n# Setup Squid proxy redirect\n";
			if ($squid_conf['private_subnet_proxy_off'] == 'on') {
				foreach ($transparent_ifaces as $iface) {
					$pf_transparent_rule_port=(in_array($iface,$ssl_ifaces) ? "{80,443}" : "80");
					$rules .= "no rdr on $iface proto tcp from any to { 192.168.0.0/16, 172.16.0.0/12, 10.0.0.0/8 } port {$pf_transparent_rule_port}\n";
				}
				/* Handle PPPOE case */
				if (($config['pppoe']['mode'] == "server" && $config['pppoe']['localip']) || (function_exists("is_pppoe_server_enabled") && is_pppoe_server_enabled())) {
					$rules .= "no rdr on $PPPOE_ALIAS proto tcp from any to { 192.168.0.0/16, 172.16.0.0/12, 10.0.0.0/8 } port {$pf_rule_port}\n";
				}
				/* Handle PPTP case */
				if ($config['pptpd']['mode'] == "server" && $config['pptpd']['localip']) {
					$rules .= "no rdr on $PPTP_ALIAS proto tcp from any to { 192.168.0.0/16, 172.16.0.0/12, 10.0.0.0/8 } port {$pf_rule_port}\n";
				}
			}
			if (!empty($squid_conf['defined_ip_proxy_off'])) {
				$defined_ip_proxy_off = explode(";", $squid_conf['defined_ip_proxy_off']);
				$exempt_ip = "";
				foreach ($defined_ip_proxy_off as $ip_proxy_off) {
					if (!empty($ip_proxy_off)) {
						$ip_proxy_off = trim($ip_proxy_off);
						if (is_alias($ip_proxy_off))
							$ip_proxy_off = '$'.$ip_proxy_off;
						$exempt_ip .= ", $ip_proxy_off";
					}
				}
				$exempt_ip = substr($exempt_ip,2);
				foreach ($transparent_ifaces as $iface) {
					$pf_transparent_rule_port=(in_array($iface,$ssl_ifaces) ? "{80,443}" : "80");
					$rules .= "no rdr on $iface proto tcp from { $exempt_ip } to any port {$pf_transparent_rule_port}\n";
				}
				/* Handle PPPOE case */
				if (($config['pppoe']['mode'] == "server" && $config['pppoe']['localip']) || (function_exists("is_pppoe_server_enabled") && is_pppoe_server_enabled())) {
					$rules .= "no rdr on $PPPOE_ALIAS proto tcp from { $exempt_ip } to any port {$pf_rule_port}\n";
				}
				/* Handle PPTP case */
				if ($config['pptpd']['mode'] == "server" && $config['pptpd']['localip']) {
					$rules .= "no rdr on $PPTP_ALIAS proto tcp from { $exempt_ip } to any port {$pf_rule_port}\n";
				}
			}
			if (!empty($squid_conf['defined_ip_proxy_off_dest'])) {
				$defined_ip_proxy_off_dest = explode(";", $squid_conf['defined_ip_proxy_off_dest']);
				$exempt_dest = "";
				foreach ($defined_ip_proxy_off_dest as $ip_proxy_off_dest) {
					if (!empty($ip_proxy_off_dest)) {
						$ip_proxy_off_dest = trim($ip_proxy_off_dest);
						if (is_alias($ip_proxy_off_dest))
							$ip_proxy_off_dest = '$'.$ip_proxy_off_dest;
						$exempt_dest .= ", $ip_proxy_off_dest";
					}
				}
				$exempt_dest = substr($exempt_dest,2);
				foreach ($transparent_ifaces as $iface) {
					$pf_transparent_rule_port=(in_array($iface,$ssl_ifaces) ? "{80,443}" : "80");
					$rules .= "no rdr on $iface proto tcp from any to { $exempt_dest } port {$pf_transparent_rule_port}\n";
				}
				/* Handle PPPOE case */
				if (($config['pppoe']['mode'] == "server" && $config['pppoe']['localip']) || (function_exists("is_pppoe_server_enabled") && is_pppoe_server_enabled())) {
					$rules .= "no rdr on $PPPOE_ALIAS proto tcp from any to { $exempt_dest } port {$pf_rule_port}\n";
				}
				/* Handle PPTP case */
				if ($config['pptpd']['mode'] == "server" && $config['pptpd']['localip']) {
					$rules .= "no rdr on $PPTP_ALIAS proto tcp from any to { $exempt_dest } port {$pf_rule_port}\n";
				}
			}
			foreach ($transparent_ifaces as $t_iface) {
				$pf_transparent_rule_port=(in_array($t_iface,$ssl_ifaces) ? "{80,443}" : "80");
				$rules .= "rdr on $t_iface proto tcp from any to !($t_iface) port 80 -> 127.0.0.1 port {$port}\n";
				if (in_array($t_iface,$ssl_ifaces))
					$rules .= "rdr on $t_iface proto tcp from any to !($t_iface) port 443 -> 127.0.0.1 port {$ssl_port}\n";
			}
			/* Handle PPPOE case */
			if (($config['pppoe']['mode'] == "server" && $config['pppoe']['localip']) || (function_exists("is_pppoe_server_enabled") && is_pppoe_server_enabled())) {
				$rules .= "rdr on $PPPOE_ALIAS proto tcp from any to !127.0.0.1 port {$pf_rule_port} -> 127.0.0.1 port {$port}\n";
			}
			/* Handle PPTP case */
			if ($config['pptpd']['mode'] == "server" && $config['pptpd']['localip']) {
				$rules .= "rdr on $PPTP_ALIAS proto tcp from any to !127.0.0.1 port {$pf_rule_port} -> 127.0.0.1 port {$port}\n";
			}
			$rules .= "\n";
			break;
		case 'filter':
		case 'rule':
			foreach ($transparent_ifaces as $iface) {
				$pf_transparent_rule_port=(in_array($iface,$ssl_ifaces) ? "{80,443,{$port},{$ssl_port}}" : "{80,{$port}}");
				$rules .= "# Setup squid pass rules for proxy\n";
				$rules .= "pass in quick on $iface proto tcp from any to !($iface) port {$pf_transparent_rule_port} flags S/SA keep state\n";
				// $rules .= "pass in quick on $iface proto tcp from any to !($iface) port {$port} flags S/SA keep state\n";
				$rules .= "\n";
			};
			if ($config['pppoe']['mode'] == "server" && $config['pppoe']['localip']) {
				$rules .= "pass in quick on $PPPOE_ALIAS proto tcp from any to !127.0.0.1 port {$port} flags S/SA keep state\n";
			}
			if ($config['pptpd']['mode'] == "server" && $config['pptpd']['localip']) {
				$rules .= "pass in quick on $PPTP_ALIAS proto tcp from any to !127.0.0.1 port {$port} flags S/SA keep state\n";
			}
			break;
		default:
			break;
		}
	}
	return $rules;
}

function webfilter_sync_on_changes() {
	global $config, $g;

	if (is_array($config['system']['webfilter']['squidsync']['config'])) {

		$squid_sync = $config['system']['webfilter']['squidsync']['config'][0];
		$synconchanges = $squid_sync['synconchanges'];
		$synctimeout = $squid_sync['synctimeout'];
		$notsync = array();

		if(!empty($squid_sync['notsyncsection']))
						$notsync = explode(",",$squid_sync['notsyncsection']);

		switch ($synconchanges) {
			case "manual":
				if (is_array($squid_sync[row]))
					$rs=$squid_sync[row];
				else {
					log_error(dgettext("BluePexWebFilter", "[webfilter] xmlrpc sync is enabled but there is no hosts to push on webfilter config."));
					return;
				}
				break;
			case "auto":
				if (is_array($config['system']['webfilter']['carpsettings']['config'])) {
					$system_carp=$config['system']['webfilter']['carpsettings']['config'][0];
					$rs[0]['ipaddress'] = $system_carp['synchronizetoip'];
					$rs[0]['username']  = $system_carp['username'];
					$rs[0]['password']  = $system_carp['password'];
				} else {
					log_error(dgettext("BluePexWebFilter", "[webfilter] xmlrpc sync is enabled but there is no system backup hosts to push webfilter config."));
					return;
				}
				break;
			default:
				return;
				break;
		}
		if (is_array($rs)) {
			log_error(dgettext("BluePexWebFilter", "[webfilter] xmlrpc sync is starting."));
			foreach($rs as $sh) {
				$sync_to_ip = $sh['ipaddress'];
				$password = $sh['password'];
				$username = !empty($sh['username']) ? $sh['username'] : "admin";

				if($password && $sync_to_ip)
					webfilter_do_xmlrpc_sync($sync_to_ip, $username, $password, $synctimeout, $notsync);
			}
			log_error(dgettext("BluePexWebFilter", "[webfilter] xmlrpc sync is ending."));
		}
	}
}

/* Set status XMLRPC sync */
function webfilter_set_status_sync($sync_to_ip, $status) {
	global $config;

	$squidsync = &$config['system']['webfilter']['squidsync']['config'][0];
	foreach ($squidsync['row'] as $idx => $sync) {
		if ($sync['ipaddress'] == $sync_to_ip)
			$squidsync['row'][$idx]['status'] = $status;
	}
	write_config(dgettext("BluePexWebFilter", "[webfilter] xmlrpc sync status updated."));
}

/* Do the actual XMLRPC sync */
function webfilter_do_xmlrpc_sync($sync_to_ip, $username, $password, $synctimeout, $notsync) {
	global $config, $g;

	if(!$username || !$password || !$sync_to_ip)
		return;

	if(!$synctimeout)
		$synctimeout=250;

	$xmlrpc_sync_neighbor = $sync_to_ip;
	if($config['system']['webgui']['protocol'] != "")
		$synchronizetoip = $config['system']['webgui']['protocol']."://";

	/* if port is empty lets rely on the protocol selection */
	$port = $config['system']['webgui']['port'];
	if($port == "")
		$port = ($config['system']['webgui']['protocol'] == "http") ? 80 : 443;

	$xml = array();

	$webfilter_sections = array("nf_content_rules", "nf_content_whitelist", "nf_content_blacklist", "nf_content_custom", "nf_content_settings", "nf_reports_settings", "webfilter", "squidremote", "squidcache", "squidnac", "squidtraffic", "squidantivirus", "quarantine", "bluepexdataclickagent", "squidreversegeneral", "squidreversepeer", "squidreverseuri", "squidreverseredir");

	foreach ($webfilter_sections as $wf_section) {
		if (in_array($wf_section, $notsync) || !isset($config['system']['webfilter'][$wf_section])) {
			continue;
		}
		$xml[$wf_section] = $config['system']['webfilter'][$wf_section];
	}

	require_once("xmlrpc.inc");

	/* assemble xmlrpc payload */
	$params = array(
		XML_RPC_encode($password),
		XML_RPC_encode($xml)
	);

	/* set a few variables needed for sync code borrowed from filter.inc */
	$url = $synchronizetoip.$sync_to_ip;
	log_error(dgettext("BluePexWebFilter", "Beginning webfilter XMLRPC sync to ").$url.":".$port);
	$method = 'bluepexutm.merge_webfilter_package_xmlrpc';
	$msg = new XML_RPC_Message($method, $params);
	$cli = new XML_RPC_Client('/xmlrpc.php', $url, $port);
	$cli->setCredentials($username, $password);
	if($g['debug'])
		$cli->setDebug(1);
	/* send our XMLRPC message and timeout after defined sync timeout value*/
	$resp = $cli->send($msg, $synctimeout);
	if(!$resp) {
		$error = dgettext("BluePexWebFilter", "A communications error occurred while attempting webfilter XMLRPC sync with ").$url.":".$port;
		webfilter_set_status_sync($sync_to_ip, "error");
		log_error($error);
		file_notice("sync_settings", $error, dgettext("BluePexWebFilter", "webfilter Settings Sync"), "");
	} elseif($resp->xv->me['string'] === "Authentication failed") {
		$error = dgettext("BluePexWebFilter", "Authentication failed while attempting webfilter XMLRPC sync with ").$url.":".$port;
		webfilter_set_status_sync($sync_to_ip, "error");
		log_error($error);
		file_notice("sync_settings", $error, dgettext("BluePexWebFilter", "webfilter Settings Sync"), "");
	} elseif($resp->faultCode()) {
		$cli->setDebug(1);
		$resp = $cli->send($msg, $synctimeout);
		$error = dgettext("BluePexWebFilter", "An error code was received while attempting webfilter XMLRPC sync with ")
				. $url .":" . $port . dgettext("BluePexWebFilter", " - Code ") . $resp->faultCode() . ": " . $resp->faultString();
		webfilter_set_status_sync($sync_to_ip, "error");
		log_error($error);
		file_notice("sync_settings", $error, dgettext("BluePexWebFilter", "webfilter Settings Sync"), "");
	} else {
		webfilter_set_status_sync($sync_to_ip, "success");
		log_error(dgettext("BluePexWebFilter", "webfilter XMLRPC sync successfully completed with ") . $url . ":" . $port);
	}

	/* tell squid to reload our settings on the destination sync host. */
	$method = 'pfsense.exec_php';
	$execcmd  = "require_once('/usr/local/pkg/squid.inc');\n";
	$execcmd .= "squid_resync();";
	/* assemble xmlrpc payload */
	$params = array(
		XML_RPC_encode($password),
		XML_RPC_encode($execcmd)
	);

	log_error(dgettext("BluePexWebFilter", "webfilter XMLRPC reload data ") . $url . ":" . $port);
	$msg = new XML_RPC_Message($method, $params);
	$cli = new XML_RPC_Client('/xmlrpc.php', $url, $port);
	$cli->setCredentials($username, $password);
	$resp = $cli->send($msg, $synctimeout);
	if(!$resp) {
		$error = dgettext("BluePexWebFilter", "A communications error occurred while attempting webfilter XMLRPC sync with ") . $url . ":" . $port;
		log_error($error);
		file_notice("sync_settings", $error, "squid Settings Sync", "");
	} elseif($resp->xv->me['string'] === "Authentication failed") {
		$error = dgettext("BluePexWebFilter", "Authentication failed while attempting webfilter XMLRPC sync with ").$url.":".$port;
		log_error($error);
		file_notice("sync_settings", $error, dgettext("BluePexWebFilter", "webfilter Settings Sync"), "");
	} elseif($resp->faultCode()) {
		$cli->setDebug(1);
		$resp = $cli->send($msg, $synctimeout);
		$error = dgettext("BluePexWebFilter", "An error code was received while attempting webfilter XMLRPC sync with ")
					. $url . ":" . $port . dgettext("BluePexWebFilter", " - Code ") . $resp->faultCode() . ": " . $resp->faultString();
		log_error($error);
		file_notice("sync_settings", $error, dgettext("BluePexWebFilter", "webfilter Settings Sync"), "");
	} else
		log_error(dgettext("BluePexWebFilter", "webfilter XMLRPC reload data success with ") . $url . ":" . $port);
}

function squid_configure_error_pages() {
	global $config;

	$squid_pages = array('ERR_ACCESS_DENIED', 'ERR_DNS_FAIL');
	$directory = "/usr/local/www/webfilter/squid_errors/pt-br/";
	$protocol = $config['system']['webgui']['protocol'] . "://";
	$ip = get_interface_ip("lan");

	// Verify ipv4 is valid and is online
	if (!is_ipaddrv4($ip) && !arp_get_mac_by_ip($ip)) {
		$ip = "http://www.bluepex.com";
	}

	$ip = $protocol . $ip;
	$pattern = '@/*(http|https?):\/\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}@';
	foreach($squid_pages as $squidpage) {
		$contentEN = file_get_contents("{$directory}en/{$squidpage}");
		$newcontentEN = preg_replace($pattern, $ip, $contentEN);
		file_put_contents("{$directory}en/{$squidpage}", $newcontentEN);

		$contentBR = file_get_contents("{$directory}pt-br/{$squidpage}");
		$newcontentBR = preg_replace($pattern, $ip, $contentBR);
		file_put_contents("{$directory}pt-br/{$squidpage}", $newcontentBR);
	}
}
?>
