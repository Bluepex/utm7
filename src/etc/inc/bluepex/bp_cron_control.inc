<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2023
 *
 * ====================================================================
 *
 */

require_once("config.inc");
require_once("util.inc");
require_once("services.inc");

function bp_cron_2af_action($disabled = false) {
	global $config;
	/*
	Only activated when there are users with 2af ​​activated
	Page action: ./system_usermanager.php:
	*/
	$status_2af_users_vpn = false;
	$status_2af_users_mfa = false;

	if (isset($config['system']['user'])) {
		$status_2af_users_vpn = in_array("yes", array_filter(array_unique(array_column($config['system']['user'], 'statusenable2fa'))));
		$status_2af_users_mfa = in_array("yes", array_filter(array_unique(array_column($config['system']['user'], 'mfa'))));
	}

	// Expire 2AF no licence
	$state_write = ($disabled) ? false : $status_2af_users_vpn;
	install_cron_job("/usr/local/bin/php /usr/local/www/ajax_send_qrCode.php revoke_qrcode_expired 0", $state_write, "55", "23", "*", "*", "*");

	// Confer MFA set YES
	$state_write = ($disabled) ? false : $status_2af_users_mfa;
	install_cron_job("sleep 10 && /usr/local/bin/php /usr/local/www/ajax_send_qrCode.php check_mfa_status", $state_write, "*/1", "*", "*", "*", "*");
	install_cron_job("sleep 30 && /usr/local/bin/php /usr/local/www/ajax_send_qrCode.php check_mfa_status", $state_write, "*/1", "*", "*", "*", "*");
	install_cron_job("sleep 50 && /usr/local/bin/php /usr/local/www/ajax_send_qrCode.php check_mfa_status", $state_write, "*/1", "*", "*", "*", "*");
}

function bp_cron_acp_fapp_action($disabled = false) {
	global $config;
	/*
	Only activated when suricata interfaces are listed

	Page action: Add
	* /usr/local/www/active_protection/ap_services.php
	* /usr/local/www/firewallapp/services.php
	Page action: Removed
	* /usr/local/www/firewallapp/firewallapp_interfaces.php
	* /usr/local/www/active_protection/acp_interfaces.php
	*/
	$state_write = (isset($config['installedpackages']['suricata']['rule']) && count($config['installedpackages']['suricata']['rule']) > 0);
	$state_write = ($disabled) ? false : $state_write;
	install_cron_job("/usr/local/bin/php /usr/local/www/firewallapp/get_rules_lists_webservice.php update", $state_write, "*/50", "*", "*", "*", "*");
	install_cron_job("/usr/local/bin/php /usr/local/www/firewallapp/get_rules_lists_webservice.php for_30m", $state_write, "*/30", "*", "*", "*", "*");
	install_cron_job("/usr/local/bin/python3.8 /usr/local/sbin/fapprotated 2", $state_write, "59", "23", "*", "*", "*");
	install_cron_job("/usr/local/bin/php /usr/local/www/active_protection/run_ameacas.php", $state_write, "*/59", "*", "*", "*", "*");

	// Reports ACP/FAPP
	install_cron_job("/usr/local/bin/php /usr/local/www/active_protection/tacp.php", false, "*/35", "*", "*", "*", "*");
	install_cron_job("/usr/local/bin/php /usr/local/www/active_protection/tw.php", false, "*/50", "*", "*", "*", "*");

	install_cron_job("/usr/local/bin/php /usr/local/www/active_protection/tacp.php", $state_write, "*/59", "*", "*", "*", "*");
	install_cron_job("/usr/local/bin/php /usr/local/www/active_protection/tw.php", $state_write, "*/59", "*", "*", "*", "*");

	install_cron_job("/usr/local/bin/php /usr/local/www/active_protection/relatorios_acp/generateReportPDF.php", $state_write, "*/5", "2-6", "*", "*", "*");
}

function bp_cron_generic_actions($states = false) {
	install_cron_job("/usr/local/bin/php -f /usr/local/bin/pack_version.php", $states, "*/15", "*", "*", "*", "*");
	install_cron_job("/usr/local/bin/php /usr/local/www/check_data.php", $states, "*/5", "*", "*", "*", "*");
	install_cron_job("/usr/local/bin/php /usr/local/sbin/bp_update_license.php", $states, "*/15", "*", "*", "*", "*");

	// Report SSH/WEB accesses
	if (file_exists("/usr/local/sbin/bp_send_filtered_logs_ssh_to_api.php")) {
		install_cron_job("/usr/local/bin/php -f /usr/local/sbin/bp_send_filtered_logs_ssh_to_api.ph", false, "*/59", "*", "*", "*", "*");
		install_cron_job("/usr/local/bin/php -f /usr/local/sbin/bp_send_filtered_logs_ssh_to_api.php", $states, "*/59", "*", "*", "*", "*");
	}
}

function bp_cron_disabled_all() {
	bp_cron_generic_actions();
	bp_cron_2af_action(true);
	bp_cron_acp_fapp_action(true);
}

function bp_cron_active_all() {
	bp_cron_generic_actions(true);
	bp_cron_2af_action();
	bp_cron_acp_fapp_action();
}
